<?php

/**
 * Two-factor authentication class.
 * 
 * Handles two-factor authentication via a Yubikey hardware token, available from yubico.com.
 * Set up requires obtaining a Client ID and secret key from Yubico, please refer to the manual for
 * instructions on how to set it up.
 * 
 * Do not attempt to use this file without reading the manual.
 * 
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		core
 */

if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishYubikeyAuthenticator
{
    
    // Input.
    /** @var int $_id ID of the Yubikey hardware token (first 12 characters of output). */
    private $_id;
    
    /** @var string $_signatureKey Yubikey API key obtained from https://upgrade.yubico.com/getapikey/ */
    private $_signatureKey;

    // Output.
    /** @var string $_response Response message from last verification attempt */
    private $_response;

    // Internal.
    /** @var array $_curlResult Response from cURL request to Yubico authentication server. */
    private $_curlResult;
    
    /** @var string $_curlError Error message. */
    private $_curlError;
    
    /** @var int $_timestampTolerance Timeout limit (expiry) for authentication requests. */
    private $_timestampTolerance;
    
    /** @var int $_curlTimeout Timeout limit when contacting Yubico authentication server. */
    private $_curlTimeout;

    /** Initialise default property values and unset unneeded ones. */
	public function __construct()
    {
        if (defined("TFISH_YUBIKEY_ID")) {
            $this->_id = (int)TFISH_YUBIKEY_ID;
        }
        
        if (defined("TFISH_YUBIKEY_SIGNATURE_KEY")) {
            
            if (mb_strlen(TFISH_YUBIKEY_SIGNATURE_KEY, "UTF-8") == 28)
            {
                $this->_signatureKey = base64_decode (TFISH_YUBIKEY_SIGNATURE_KEY);
            }
        } else {
            trigger_error(TFISH_YUBIKEY_NO_SIGNATURE_KEY, E_USER_ERROR);
            return false;
            exit;
        }
        
        if (defined("TFISH_YUBIKEY_TIMESTAMP_TOLERANCE")) {
            $this->_timestampTolerance = (int)TFISH_YUBIKEY_TIMESTAMP_TOLERANCE;
        }
        
        if (defined("TFISH_YUBIKEY_CURL_TIMEOUT")) {
            $this->_curlTimeout = (int)TFISH_YUBIKEY_CURL_TIMEOUT;
        }
	}

	/////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////
	//	Yubikey API methods, by Tom Corwine (yubico@corwine.org)
    //	
    //	@license GNU General Public License (GPL) V2
	//	
	//	verify(string) - Accepts otp from Yubikey. Returns TRUE for authentication success, otherwise FALSE.
	//	getLastResponse() - Returns response message from verification attempt.
	//	getTimestampTolerance() - Gets the tolerance (+/-, in seconds) for timestamp verification
	//	setTimestampTolerance(int) - Sets the tolerance (in seconds, 0-86400) - default 600 (10 minutes).
	//		Returns TRUE on success and FALSE on failure.
	//	getCurlTimeout() - Gets the timeout (in seconds) CURL uses before giving up on contacting Yubico's server.
	//	setCurlTimeout(int) - Sets the CURL timeout (in seconds, 0-600, 0 means indefinitely) - default 10.
	//		Returns TRUE on success and FALSE on failure.
	//////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////

    /**
     * Returns the timestamp tolerance (seconds).
     * 
     * Timestamp tolerance is how long an authentication request will be accepted after it is
     * generated. You need to allow some time for discrepancies between clocks and user delays.
     * Default: 10 minutes.
     * 
     * @return int
     */
	public function getTimestampTolerance()
	{
		return $this->_timestampTolerance;
	}

    /**
     * Set the timestamp tolerance.
     * 
     * @param int $int
     * @return bool
     */
	public function setTimestampTolerance($int)
	{
		if ($int > 0 && $int < 86400)
		{
			$this->_timestampTolerance = $int;
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

    /**
     * Get the timeout for cURL requests, in seconds.
     * 
     * @return int
     */
	public function getCurlTimeout()
	{
		return $this->_curlTimeout;
	}

    /**
     * Set the cURL timeout.
     * 
     * @param int $int
     * @return bool
     */
	public function setCurlTimeout($int)
	{
		if ($int > 0 && $int < 600)
		{
			$this->_curlTimeout = $int;
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

    /**
     * Returns response message from last verification attempt.
     * 
     * @return string
     */
	public function getLastResponse()
	{
		return $this->_response;
	}

    /**
     * Authenticate using a Yubikey one-time password.
     * 
     * @param string $otp one time password generated by Yubikey hardware token
     * @return bool true for authentication pass false for fail
     */
	public function verify($otp)
	{
		unset ($this->_response);
		unset ($this->_curlResult);
		unset ($this->_curlError);

		$otp = strtolower ($otp);
		if (!$this->_id)
		{
			$this->_response = "ID NOT SET";
			return FALSE;
		}

		if (!$this->otpIsProperLength($otp))
		{
			$this->_response = "BAD OTP LENGTH";
			return FALSE;
		}

		if (!$this->otpIsModhex($otp))
		{
			$this->_response = "OTP NOT MODHEX";
			return FALSE;
		}

		$urlParams = "id=".$this->_id."&otp=".$otp;
		$url = $this->createSignedRequest($urlParams);

		if ($this->curlRequest($url)) //Returns 0 on success
		{
			$this->_response = "ERROR CONNECTING TO YUBICO - ".$this->_curlError;
			return FALSE;
		}

		foreach ($this->_curlResult as $param)
		{
			if (substr ($param, 0, 2) == "h=") $signature = substr (trim ($param), 2);
			if (substr ($param, 0, 2) == "t=") $timestamp = substr (trim ($param), 2);
			if (substr ($param, 0, 7) == "status=") $status = substr (trim ($param), 7);
		}

		// Concatenate string for signature verification
		$signedMessage = "status=".$status."&t=".$timestamp;

		if (!$this->resultSignatureIsGood($signedMessage, $signature))
		{
			$this->_response = "BAD RESPONSE SIGNATURE";
			return FALSE;
		}
		if (!$this->resultTimestampIsGood($timestamp))
		{
			$this->_response = "BAD TIMESTAMP";
			return FALSE;
		}
		if ($status != "OK")
		{
			$this->_response = $status;
			return FALSE;
		}
		// Everything went well - We pass
		$this->_response = "OK";
		return TRUE;
	}

    /**
     * Create URL with embedded and signed authentication request for Yubico authentication server.
     * 
     * @param string $urlParams
     * @return string
     */
	protected function createSignedRequest($urlParams)
	{
		if ($this->_signatureKey)
		{
			$hash = urlencode (base64_encode (hash_hmac ("sha1", $urlParams, $this->_signatureKey,
					TRUE)));
			return "https://api.yubico.com/wsapi/verify?".$urlParams."&h=".$hash;
		}
		else
		{
			return "https://api.yubico.com/wsapi/verify?".$urlParams;
		}
	}

    /**
     * Make cURL request.
     * 
     * @param string $url
     * @return string error message
     */
	protected function curlRequest($url)
	{
		$ch = curl_init ($url);

		curl_setopt ($ch, CURLOPT_TIMEOUT, $this->_curlTimeout);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $this->_curlTimeout);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, FALSE);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, TRUE);

		$this->_curlResult = explode ("\n", curl_exec($ch));

		$this->_curlError = curl_error ($ch);
		$error = curl_errno ($ch);

		curl_close ($ch);

		return $error;
	}

    /**
     * Check Yubikey one time password is expected length.
     * 
     * @param string $otp
     * @return bool
     */
	protected function otpIsProperLength($otp)
	{
		if (strlen ($otp) == 44)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

    /**
     * Check Yubikey one time password is modhex encoded.
     * 
     * @param string $otp
     * @return bool
     */
	protected function otpIsModhex($otp)
	{
		$modhexChars = array ("c","b","d","e","f","g","h","i","j","k","l","n","r","t","u","v");

		foreach (str_split ($otp) as $char)
		{
			if (!in_array ($char, $modhexChars)) return FALSE;
		}

		return TRUE;
	}

    /**
     * Check timestamp is within tolerance.
     * 
     * @param int $timestamp
     * @return bool
     */
	protected function resultTimestampIsGood($timestamp)
	{
		// Turn times into 'seconds since Unix Epoch' for easy comparison
		$now = date ("U");
		$timestampSeconds = (date_format (date_create (substr ($timestamp, 0, -4)), "U"));

		// If date() functions above fail for any reason, so do we
		if (!$timestamp || !$now) return FALSE;

		if (($timestampSeconds + $this->_timestampTolerance) > $now &&
		    ($timestampSeconds - $this->_timestampTolerance) < $now)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

    /**
     * Validate result signature.
     * 
     * @param string $signedMessage
     * @param string $signature
     * @return bool
     */
	protected function resultSignatureIsGood($signedMessage, $signature)
	{
		if (!$this->_signatureKey) return TRUE;

		if (base64_encode (hash_hmac ("sha1", $signedMessage, $this->_signatureKey, TRUE))
				== $signature)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	///////////////////////////////////////////////////////////////////////
	///// END Yubikey API methods by Tom Corwine (yubico@corwine.org) /////
	///////////////////////////////////////////////////////////////////////
    
}