<?php

/**
 * TfYubikeyAuthenticator class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     security
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) 
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Two-factor authentication class.
 * 
 * Handles two-factor authentication via a Yubikey hardware token, available from yubico.com.
 * Set up requires obtaining a Client ID and secret key from Yubico, please refer to the manual for
 * instructions on how to set it up.
 * 
 * Note that the Yubikey authentication methods within this class are Copyright Tom Corwine and
 * distributed under the GPL (V2). Please see the separate copyright block within the class file.
 * 
 * Do not attempt to use this file without reading the manual.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     security
 */
class TfYubikeyAuthenticator
{
    
    /** @var TfValidator $validator Instance of the Tuskfish data validator class. */
    protected $validator;

    // Input.
    /** @var int $_id ID of the Yubikey hardware token (first 12 characters of output). */
    private $_id;

    /** @var string $_signatureKey Yubikey API key obtained from
     * https://upgrade.yubico.com/getapikey/ */
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
    public function __construct(TfValidator $validator)
    {
        $this->validator = $validator;
        
        if (defined("TFISH_YUBIKEY_ID")) {
            $this->_id = (int) TFISH_YUBIKEY_ID;
        }

        if (defined("TFISH_YUBIKEY_SIGNATURE_KEY")) {

            if (mb_strlen(TFISH_YUBIKEY_SIGNATURE_KEY, "UTF-8") === 28) {
                $this->_signatureKey = base64_decode(TFISH_YUBIKEY_SIGNATURE_KEY);
            }
        } else {
            trigger_error(TFISH_YUBIKEY_NO_SIGNATURE_KEY, E_USER_ERROR);
            return false;
            exit;
        }

        if (defined("TFISH_YUBIKEY_TIMESTAMP_TOLERANCE")) {
            $this->_timestampTolerance = (int) TFISH_YUBIKEY_TIMESTAMP_TOLERANCE;
        }

        if (defined("TFISH_YUBIKEY_CURL_TIMEOUT")) {
            $this->_curlTimeout = (int) TFISH_YUBIKEY_CURL_TIMEOUT;
        }
    }

    /////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////
    //	Yubikey API methods, by Tom Corwine (yubico@corwine.org)
    //	
    //	@license GNU General Public License (GPL) V2
    //	
    //	verify(string) - Accepts otp from Yubikey. Returns TRUE for authentication success,
    //	otherwise FALSE.
    //	getLastResponse() - Returns response message from verification attempt.
    //	getTimestampTolerance() - Gets the tolerance (+/-, in seconds) for timestamp
    //	verification
    //	setTimestampTolerance(int) - Sets the tolerance (in seconds, 0-86400) - default 600
    //	(10 minutes). Returns TRUE on success and FALSE on failure.
    //	getCurlTimeout() - Gets the timeout (in seconds) CURL uses before giving up on contacting
    //	Yubico's server.
    //	setCurlTimeout(int) - Sets the CURL timeout (in seconds, 0-600, 0 means indefinitely)
    //	- default 10.Returns TRUE on success and FALSE on failure.
    //////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////

    /**
     * Create URL with embedded and signed authentication request for Yubico authentication server.
     * 
     * @param string $urlParams URL parameters.
     * @return string URL to Yubico authentication server with query string parameters attached.
     */
    protected function createSignedRequest(string $urlParams)
    {
        $urlParams = $this->validator->trimString($urlParams);
        
        if ($this->_signatureKey) {
            $hash = urlencode(base64_encode(hash_hmac("sha1", $urlParams, $this->_signatureKey,
                    true)));
            return "https://api.yubico.com/wsapi/verify?" . $urlParams . "&h=" . $hash;
        } else {
            return "https://api.yubico.com/wsapi/verify?" . $urlParams;
        }
    }

    /**
     * Make cURL request.
     * 
     * @param string $url Target URL.
     * @return string Error message.
     */
    protected function curlRequest(string $url)
    {
        $url = $this->validator->trimString($url);
        
        if (!$this->validator->isUrl($url)) {
            trigger_error(TFISH_ERROR_NOT_URL, E_USER_ERROR);
        }
        
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_curlTimeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->_curlTimeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $this->_curlResult = explode("\n", curl_exec($ch));

        $this->_curlError = curl_error($ch);
        $error = curl_errno($ch);

        curl_close($ch);

        return $error;
    }
    
    /**
     * Get the timeout for cURL requests, in seconds.
     * 
     * @return int cURL timeout (seconds).
     */
    public function getCurlTimeout()
    {
        return $this->_curlTimeout;
    }
    
    /**
     * Returns response message from last verification attempt.
     * 
     * @return string Last response message.
     */
    public function getLastResponse()
    {
        return $this->_response;
    }
    
    /**
     * Returns the timestamp tolerance (seconds).
     * 
     * Timestamp tolerance is how long an authentication request will be accepted after it is
     * generated. You need to allow some time for discrepancies between clocks and user delays.
     * Default: 10 minutes.
     * 
     * @return int Timestamp tolerance (seconds).
     */
    public function getTimestampTolerance()
    {
        return $this->_timestampTolerance;
    }
    
    /**
     * Check Yubikey one time password is expected length.
     * 
     * @param string $otp Yubikey one-time password.
     * @return bool True if length is ok, otherwise false.
     */
    protected function otpIsProperLength(string $otp)
    {
        $otp = $this->validator->trimString($otp);
        
        if (mb_strlen($otp, "UTF-8") === 44) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check Yubikey one time password is modhex encoded.
     * 
     * @param string $otp Yubikey one-time password.
     * @return bool True if modhex encoded, otherwise false.
     */
    protected function otpIsModhex(string $otp)
    {
        $otp = $this->validator->trimString($otp);
        $modhexChars = array("c", "b", "d", "e", "f", "g", "h", "i", "j", "k", "l", "n", "r", "t",
            "u", "v");

        foreach (str_split($otp) as $char) {
            if (!in_array($char, $modhexChars, true))
                return false;
        }

        return true;
    }

    /**
     * Check timestamp is within tolerance.
     * 
     * @param string $timestamp Timestamp to check.
     * @return bool True if timestamp is within tolerance, otherwise false.
     */
    protected function resultTimestampIsGood(string $timestamp)
    {
    	$timestamp = $this->validator->trimString($timestamp);
    
        // Turn times into 'seconds since Unix Epoch' for easy comparison
        $now = date("U");
        $timestampSeconds = (int) (dateFormat(date_create(mb_substr($timestamp, 0, -4, "UTF-8")), "U"));

        // If date() functions above fail for any reason, so do we
        if (!$timestamp || !$now)
            return false;

        if (($timestampSeconds + $this->_timestampTolerance) > $now &&
                ($timestampSeconds - $this->_timestampTolerance) < $now) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate result signature.
     * 
     * @param string $signedMessage Signed message.
     * @param string $signature Signature.
     * @return bool True if signature is good, otherwise false.
     */
    protected function resultSignatureIsGood(string $signedMessage, string $signature)
    {
        $signedMessage = $this->validator->trimString($signedMessage);
        $signature = $this->validator->trimString($signature);
        
        if (!$this->_signatureKey)
            return true;

        if (base64_encode(hash_hmac("sha1", $signedMessage, $this->_signatureKey, true))
                === $signature) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Set the cURL timeout.
     * 
     * @param int $int cURL timeout (seconds).
     * @return bool True on success, false on failure.
     */
    public function setCurlTimeout(int $int)
    {
        $int = (int) $int;
        
        if ($int > 0 && $int < 600) {
            $this->_curlTimeout = $int;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the timestamp tolerance.
     * 
     * @param int $int Timestamp tolerance (seconds).
     * @return bool True on success, false on failure.
     */
    public function setTimestampTolerance(int $int)
    {
        define("SECONDS_IN_ONE_DAY", 86400);
        
        $int = (int) $int;
        
        if ($int > 0 && $int < SECONDS_IN_ONE_DAY) {
            $this->_timestampTolerance = $int;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Authenticate using a Yubikey one-time password.
     * 
     * @param string $otp One time password generated by Yubikey hardware token.
     * @return bool True for successful authentication, false if fail.
     */
    public function verify(string $otp)
    {
        $otp = $this->validator->trimString($otp);
        
        unset($this->_response);
        unset($this->_curlResult);
        unset($this->_curlError);

        $otp = mb_strtolower($otp, "UTF-8");

        if (!$this->_id) {
            $this->_response = "ID NOT SET";
            return false;
        }

        if (!$this->otpIsProperLength($otp)) {
            $this->_response = "BAD OTP LENGTH";
            return false;
        }

        if (!$this->otpIsModhex($otp)) {
            $this->_response = "OTP NOT MODHEX";
            return false;
        }

        $urlParams = "id=" . $this->_id . "&otp=" . $otp;
        $url = $this->createSignedRequest($urlParams);

        // Returns 0 on success.
        if ($this->curlRequest($url)) {
            $this->_response = "ERROR CONNECTING TO YUBICO - " . $this->_curlError;
            return false;
        }

        foreach ($this->_curlResult as $param) {
            if (mb_substr($param, 0, 2, "UTF-8") === "h=")
                $signature = substr(trim($param), 2);
            
            if (mb_substr($param, 0, 2, "UTF-8") === "t=")
                $timestamp = substr(trim($param), 2);
            
            if (mb_substr($param, 0, 7, "UTF-8") === "status=")
                $status = substr(trim($param), 7);
        }

        // Concatenate string for signature verification
        $signedMessage = "status=" . $status . "&t=" . $timestamp;

        if (!$this->resultSignatureIsGood($signedMessage, $signature)) {
            $this->_response = "BAD RESPONSE SIGNATURE";
            return false;
        }

        if (!$this->resultTimestampIsGood($timestamp)) {
            $this->_response = "BAD TIMESTAMP";
            return false;
        }

        if ($status != "OK") {
            $this->_response = $status;
            return false;
        }

        // Everything went well - We pass
        $this->_response = "OK";
        
        return true;
    }

    ///////////////////////////////////////////////////////////////////////
    ///// END Yubikey API methods by Tom Corwine (yubico@corwine.org) /////
    ///////////////////////////////////////////////////////////////////////
}
