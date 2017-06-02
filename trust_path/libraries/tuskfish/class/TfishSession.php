<?php

/**
 * Tuskfish session security class.
 * 
 * Provides functions for managing sessions in a security-concious manner.
 *
 * @copyright	Simon Wilkinson (Crushdepth) 2016
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @since		1.0
 * @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
 * @package		core
 */
if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishSession
{
    /**
     * Initialise a user session.
     * 
     * @global obj $tfish_preference TfishPreference object
     * @param string $name
     * @param int $limit
     * @param string $path
     * @param string|null $domain
     * @param bool|null $https
     */

    /**
     * Unset session variables and destroy the session.
     * 
     * @return void
     */
    public static function destroy()
    {
        $_SESSION = [];
        session_destroy();
        session_start();
    }

    /*
     * Shorthand admin privilages check.
     * 
     * For added security this could retrieve an encrypted token, preferably the SSL session id,
     * although thats availability seems to depend on server configuration.
     * 
     * @return bool true if admin false if not
     */

    public static function isAdmin()
    {
        if (isset($_SESSION['TFISH_LOGIN']) && $_SESSION['TFISH_LOGIN'] == true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if a session has expired and sets last seen activity flag.
     * 
     * @global type $tfish_preference
     * @return boolean
     */
    public static function isExpired()
    {
        // Make Tuskfish preferences available.
        global $tfish_preference;

        // Check if session carries a destroyed flag and kill it if the grace timer has expired.
        if (isset($_SESSION['destroyed']) && time() > $_SESSION['destroyed']) {
            return true;
        }

        // Check for "last seen" timestamp.
        $last_seen = isset($_SESSION['last_seen']) ? (int) $_SESSION['last_seen'] : false;

        // Check expiry (but not if session_life == 0).
        if ($last_seen && $tfish_preference->session_life > 0) {
            if ($last_seen && (time() - $last_seen) > ($tfish_preference->session_life * 60)) {
                return true;
            }
        }

        // Session not seen before, add an activity timestamp.
        $_SESSION['last_seen'] = time();

        return false;
    }

    /**
     * Checks if client IP address or user agent has changed.
     * 
     * These tests can indicate hijacking but are not definitive; however they do indicate elevated
     * risk and session should be regenerated as a counter measure.
     * 
     * @return boolean
     */
    public static function isClean()
    {
        $browser_profile = '';

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $browser_profile .= $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $browser_profile .= $_SERVER['HTTP_USER_AGENT'];
        }
        $browser_profile = hash('sha256', $browser_profile);

        if (isset($_SESSION['browser_profile'])) {
            return $_SESSION['browser_profile'] === $browser_profile;
        }

        $_SESSION['browser_profile'] = $browser_profile;

        return true;
    }

    /**
     * Authenticate the user and establish a session.
     * 
     * Note that the password field is unrestricted content.
     * 
     * @param string $email
     * @param string $password
     * @return void
     */
    public static function login($email, $password)
    {
        // Check email and password have been supplied
        if (empty($email) || empty($password)) {
            // Issue incomplete form warning and redirect to the login page.
            self::logout(TFISH_ADMIN_URL . "login.php");
        } else {
            // Sanitise the admin email (which functions as the username in Tuskfish CMS)
            $clean_email = TfishFilter::trimString($email);
            if (TfishFilter::isEmail($clean_email)) {
                self::_login($clean_email, $password);
            } else {
                // Issue warning - email should follow email format
                self::logout(TFISH_ADMIN_URL . "login.php");
            }
        }
    }

    private static function _login($clean_email, $dirty_password)
    {
        // Query the database for a matching user.
        $statement = TfishDatabase::preparedStatement("SELECT * FROM user WHERE `admin_email` = :clean_email");
        $statement->bindParam(':clean_email', $clean_email, PDO::PARAM_STR);
        $statement->execute();
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        // Authenticate user by calculating their password hash and comparing it to the one on file.
        if ($user) {
            $password_hash = TfishSecurityUtility::recursivelyHashPassword($dirty_password, 100000, TFISH_SITE_SALT, $user['user_salt']);
            if ($password_hash == $user['password_hash']) {

                // Regenerate session due to priviledge escalation
                self::regenerate();
                $_SESSION['TFISH_LOGIN'] = true;
                $_SESSION['user_id'] = (int) $user['id']; // Added as a handle for the password change script.
                header('location: ' . TFISH_ADMIN_URL . "admin.php");
                exit;
            } else {
                // Issue failed login warning, destroy session and redirect to the login page.
                self::logout(TFISH_ADMIN_URL . "login.php");
                exit;
            }
        } else {
            // Issue failed login warning and redirect to the login page.
            self::logout(TFISH_ADMIN_URL . "login.php");
            exit;
        }
    }
    
    /**
     * Authenticate the user with two factors and establish a session.
     * 
     * Requires a Yubikey hardware token as th second factor.
     * 
     * @param type $dirty_password
     * @param type $dirty_otp
     */
    public static function twoFactorLogin($dirty_password, $dirty_otp)
    {
        // Check password and OTP have been supplied
        if (empty($dirty_password) || empty($dirty_otp)) {
            self::logout(TFISH_ADMIN_URL . "yubikey.php");
            exit;
        }
        
        $dirty_otp = TfishFilter::trimString($dirty_otp);
        
        // Yubikey OTP should be 44 characters long.
        if (mb_strlen($dirty_otp, "UTF-8") != 44) {
            self::logout(TFISH_ADMIN_URL . "yubikey.php");
            exit;
        }
        
        // Yubikey OTP should be alphabetic characters only.
        if (!TfishFilter::isAlpha($dirty_otp)) {
            self::logout(TFISH_ADMIN_URL . "yubikey.php");
            exit;
        }
        
        // Public ID is the first 12 characters of the OTP.
        $dirty_id = mb_substr($dirty_otp, 0, 12, 'UTF-8');
        
        self::_login($dirty_id, $dirty_password, $dirty_otp);
    }
    
    private static function _twoFactorLogin($dirty_id, $dirty_password, $dirty_otp)
    {   
        $user = false;
        $first_factor = false;
        $second_factor = false;
        
        // Query the database for a matching user.
        $statement = TfishDatabase::preparedStatement("SELECT * FROM user WHERE `yubikey_id` = :yubikey_id");
        $statement->bindParam(':yubikey_id', $dirty_id, PDO::PARAM_STR);
        $statement->execute();
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        
        if (empty($user)) {
            self::logout(TFISH_ADMIN_URL . "yubikey.php");
            exit;
        }
        
        // First factor authentication: Calculate password hash and compare to the one on file.
        $password_hash = TfishSecurityUtility::recursivelyHashPassword($dirty_password, 100000, TFISH_SITE_SALT, $user['user_salt']);
        if ($password_hash == $user['password_hash']) {
            $first_factor = true;
        }
        
        // Second factor authentication: Submit one-time password to Yubico authentication server.
        $second_factor = self::verify($dirty_otp);
        
        // If both checks are good regenerate session due to priviledge escalation and login.
        if ($first_factor === true && $second_factor === true) {
            self::regenerate();
            $_SESSION['TFISH_LOGIN'] = true;
            $_SESSION['user_id'] = (int) $user['id']; // Added as a handle for the password change script.
            header('location: ' . TFISH_ADMIN_URL . "admin.php");
            exit;
        }
        
        // Otherwise force logout.
        self::logout(TFISH_ADMIN_URL . "yubikey.php");
        exit;
    }

    /*
     * Returns a login or logout link for insertion in the template.
     * 
     * @return string login or logout link
     */

    public static function loginLink()
    {
        if (self::isAdmin()) {
            return '<a href="' . TFISH_ADMIN_URL . 'login.php?op=logout">' . TFISH_LOGOUT . '</a>';
        } else {
            return '<a href="' . TFISH_ADMIN_URL . 'login.php">' . TFISH_LOGIN . '</a>';
        }
    }

    /**
     * Destroys the current session on logout
     * 
     * @param string|bool $url_redirect to redirect the user to. 
     * @return void
     */
    public static function logout($url_redirect = false)
    {
        $clean_url = false;
        if ($url_redirect) {
            $clean_url = TfishFilter::isUrl($url_redirect) ? $url_redirect : false;
        }
        self::_logout($clean_url);
    }

    private static function _logout($clean_url)
    {
        // Unset all of the session variables.
        $_SESSION = [];

        // Destroy the session cookie, DESTROY IT ISILDUR!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }

        // Destroy the session and redirect
        session_destroy();
        if ($clean_url) {
            header('location: ' . $clean_url);
            exit;
        } else {
            header('location: ' . TFISH_URL);
            exit;
        }
    }

    /**
     * Reset session data after a hijacking check fails. This will force logout.
     * 
     * @return void
     */
    public static function reset()
    {
        $_SESSION = [];
        $browser_profile = '';

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $browser_profile .= $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $browser_profile .= $_SERVER['HTTP_USER_AGENT'];
        }
        $_SESSION['browser_profile'] = hash('sha256', $browser_profile);
    }

    /**
     * Regenerates the session ID.
     * 
     * Called whenever there is a privilege escalation (login) or at random intervals to reduce
     * risk of session hijacking.
     * 
     * Note that it allows the new and  old sessions to co-exist for a short period, this is to 
     * avoid headaches with flaky network connections and asynchronous (AJAX) requests, as explained
     * in the PHP Manual warning: http://php.net/manual/en/function.session-regenerate-id.php
     * 
     * @return void
     */
    public static function regenerate()
    {
        // If destroyed flag is set, no need to regenerate ID as it has already been done.
        if (isset($_SESSION['destroyed'])) {
            return;
        }

        // Flag old session for destruction in (arbitrary) 10 seconds.
        $_SESSION['destroyed'] = time() + 10;

        // Create new session.
        session_regenerate_id(false); // Update session ID and keep current session info. Old one is not destroyed.
        $new_session_id = session_id(); // Get the (new) session ID.
        session_write_close(); // Lock the session and close it.
        session_id($new_session_id); // Set the session ID to the new value.
        session_start(); // Now working with the new session. Note that old one still exists and both carry a 'destroyed' flag.
        unset($_SESSION['destroyed']); // Remove the destroyed flag from the new session. Old one will be destroyed next time isExpired() is called on it.
    }

    /**
     * Initialises a session and sets session cookie parameters to security-concious values. 
     * 
     * @global type $tfish_preference
     * @return void
     */
    public static function start()
    {
        // Make Tuskfish preferences available.
        global $tfish_preference;

        // Force session to use cookies to prevent the session ID being passed in the URL.
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);

        // Session name. If the preference has been messed up it will assign one.
        $session_name = isset($tfish_preference->session_name) ? $tfish_preference->session_name : 'tfish';

        // Session life time, in seconds. '0' means until the browser is closed.
        $lifetime = $tfish_preference->session_lifetime;

        // Path on the domain where the cookie will work. Use a single slash for all paths (default, as there are admin checks in some templates).
        $path = '/';

        // Cookie domain, for example www.php.net. To make cookies visible on all subdomains (default) prefix with dot eg. '.php.net'
        $domain = isset($domain) ? $domain : ltrim($_SERVER['SERVER_NAME'], 'www');

        // If true the cookie will only be sent over secure connections.
        $secure = isset($_SERVER['HTTPS']);

        // If true PHP will *attempt* to send the httponly flag when setting the session cookie.
        $http_only = true;

        // Set the parameters and start the session.
        session_name($session_name);
        session_set_cookie_params($lifetime, $path, $domain, $secure, $http_only);
        session_start();

        // Check if the session has expired.
        if (self::isExpired())
            self::destroy();

        // Check for signs of session hijacking and regenerate if at risk. 10% chance of doing it anyway.
        if (!self::isClean()) {
            self::reset();
            self::regenerate();
        } elseif (rand(1, 100) <= 10) {
            self::regenerate();
        }
    }
    
    /////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////
	//	Yubikey API methods, by Tom Corwine (yubico@corwine.org)
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

	public function getTimestampTolerance()
	{
		return self::$_timestampTolerance;
	}

	public function setTimestampTolerance($int)
	{
		if ($int > 0 && $int < 86400)
		{
			self::$_timestampTolerance = $int;
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	public function getCurlTimeout()
	{
		return self::$_curlTimeout;
	}

	public function setCurlTimeout($int)
	{
		if ($int > 0 && $int < 600)
		{
			self::$_curlTimeout = $int;
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	public function getLastResponse()
	{
		return self::$_response;
	}

	public function verify($otp)
	{
		unset (self::$_response);
		unset (self::$_curlResult);
		unset (self::$_curlError);

		$otp = strtolower ($otp);

		if (!self::$_id)
		{
			self::$_response = "ID NOT SET";
			return FALSE;
		}

		if (!self::otpIsProperLength($otp))
		{
			self::$_response = "BAD OTP LENGTH";
			return FALSE;
		}

		if (!self::otpIsModhex($otp))
		{
			self::$_response = "OTP NOT MODHEX";
			return FALSE;
		}

		$urlParams = "id=".self::$_id."&otp=".$otp;

		$url = self::createSignedRequest($urlParams);

		if (self::curlRequest($url)) //Returns 0 on success
		{
			self::$_response = "ERROR CONNECTING TO YUBICO - ".self::$_curlError;
			return FALSE;
		}

		foreach (self::$_curlResult as $param)
		{
			if (substr ($param, 0, 2) == "h=") $signature = substr (trim ($param), 2);
			if (substr ($param, 0, 2) == "t=") $timestamp = substr (trim ($param), 2);
			if (substr ($param, 0, 7) == "status=") $status = substr (trim ($param), 7);
		}

		// Concatenate string for signature verification
		$signedMessage = "status=".$status."&t=".$timestamp;

		if (!self::resultSignatureIsGood($signedMessage, $signature))
		{
			self::$_response = "BAD RESPONSE SIGNATURE";
			return FALSE;
		}

		if (!self::resultTimestampIsGood($timestamp))
		{
			self::$_response = "BAD TIMESTAMP";
			return FALSE;
		}

		if ($status != "OK")
		{
			self::$_response = $status;
			return FALSE;
		}

		// Everything went well - We pass
		self::$_response = "OK";
		return TRUE;
	}

	protected function createSignedRequest($urlParams)
	{
		if (self::$_signatureKey)
		{
			$hash = urlencode (base64_encode (hash_hmac ("sha1", $urlParams, self::$_signatureKey,
					TRUE)));
			return "https://api.yubico.com/wsapi/verify?".$urlParams."&h=".$hash;
		}
		else
		{
			return "https://api.yubico.com/wsapi/verify?".$urlParams;
		}
	}

	protected function curlRequest($url)
	{
		$ch = curl_init ($url);

		curl_setopt ($ch, CURLOPT_TIMEOUT, self::$_curlTimeout);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, self::$_curlTimeout);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, FALSE);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, TRUE);

		self::$_curlResult = explode ("\n", curl_exec($ch));

		self::$_curlError = curl_error ($ch);
		$error = curl_errno ($ch);

		curl_close ($ch);

		return $error;
	}

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

	protected function otpIsModhex($otp)
	{
		$modhexChars = array ("c","b","d","e","f","g","h","i","j","k","l","n","r","t","u","v");

		foreach (str_split ($otp) as $char)
		{
			if (!in_array ($char, $modhexChars)) return FALSE;
		}

		return TRUE;
	}

	protected function resultTimestampIsGood($timestamp)
	{
		// Turn times into 'seconds since Unix Epoch' for easy comparison
		$now = date ("U");
		$timestampSeconds = (date_format (date_create (substr ($timestamp, 0, -4)), "U"));

		// If date() functions above fail for any reason, so do we
		if (!$timestamp || !$now) return FALSE;

		if (($timestampSeconds + self::$_timestampTolerance) > $now &&
		    ($timestampSeconds - self::$_timestampTolerance) < $now)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	protected function resultSignatureIsGood($signedMessage, $signature)
	{
		if (!self::$_signatureKey) return TRUE;

		if (base64_encode (hash_hmac ("sha1", $signedMessage, self::$_signatureKey, TRUE))
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
