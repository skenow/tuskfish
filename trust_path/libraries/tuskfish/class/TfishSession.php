<?php

/**
* Tuskfish session security class.
* 
* Provides functions for managing sessions in a secure manner. 
*
* @copyright	http://blog.teamtreehouse.com/how-to-create-bulletproof-sessions
* @package		core
*/

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
	public static function sessionStart($name = null, $limit = 0, $path = '/', $domain = null, $https = null)
	{	
		// Gather parameters
		global $tfish_preference;
		$name = isset($name) ? $name : $tfish_preference->session_name;
		$domain = isset($domain) ? $domain : ltrim($_SERVER['SERVER_NAME'], 'www');
		$https = isset($https) ? $https : isset($_SERVER['HTTPS']);
		
		// Sanitise parameters		
		$clean_name = TfishFilter::trimString($name);
		$clean_limit = TfishFilter::isInt($limit, 0) ? $limit : 0;
		$clean_path = TfishFilter::trimString($path);
		$clean_domain = TfishFilter::trimString($domain);
		if (isset($https)) {
			$clean_https = !empty($https) ? true : false;
		} else {
			$clean_https = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) ? true : false;
		}
		$clean_http_only = true; // Locked to http as security measure.
		
		// Start the session
		self::_sessionStart($clean_name, $clean_limit, $clean_path, $clean_domain, $clean_https, $clean_http_only);
	}
	
	/**
	 * Regenerates the session ID.
	 * 
	 * Called whenever there is a privilege escalation (login) or at random intervals to reduce
	 * risk of session hijacking.
	 * 
	 * @return void
	 */
	public static function regenerateSession()
	{	
		// If this session is obsolete it means there already is a new id
		if (isset($_SESSION['OBSOLETE']) && $_SESSION['OBSOLETE'] == true) {
			return;
		}

		// Set current session to expire in 10 seconds
		$_SESSION['OBSOLETE'] = true;
		$_SESSION['EXPIRES'] = time() + 10;

		// Create new session without destroying the old one
		session_regenerate_id(false);

		// Grab current session ID and close both sessions to allow other scripts to use them
		$newSession = session_id();
		session_write_close();

		// Set session ID to the new one, and start it back up again
		session_id($newSession);
		session_start();

		// Now we unset the obsolete and expiration values for the session we want to keep
		unset($_SESSION['OBSOLETE']);
		unset($_SESSION['EXPIRES']);
	}
	
	/**
	 * Authenticate the user and establish a session.
	 * 
	 * Note that the password field is never sanitised.
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
	
	private static function _sessionStart($name, $limit, $path, $domain, $https, $http_only)
	{	
		// Set the cookie settings and start the session. Cookies are locked to http only.
		session_name($name . '_session');
		session_set_cookie_params($limit, $path, $domain, $https, $http_only);
		session_start();
		
		// Make sure the session hasn't expired, and destroy it if it has
		if (self::validateSession()) {
			// Check to see if the session is new or a hijacking attempt
			if (!self::preventHijacking()) {
				// Reset session data and regenerate id
				$_SESSION = array();
				$_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];
				$_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
				self::regenerateSession();

			// Give a 5% chance of the session id changing on any request
			} elseif (rand(1, 100) <= 5) {
				self::regenerateSession();
			}
		} else {
			$_SESSION = array();
			session_destroy();
			session_start();
		}
	}
	
	private static function _login($clean_email, $dirty_password)
	{	
		// Query the database for a matching user
		try {
			$statement = TfishDatabase::preparedStatement("SELECT * FROM user WHERE `admin_email` = :clean_email");
			$statement->bindParam(':clean_email', $clean_email, PDO::PARAM_STR);
			$statement->execute();
			$user = $statement->fetch(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
			exit;
		}
		
		// Authenticate user by calculating their password hash and comparing it to the one on file
		if ($user) {
			$password_hash = TfishSecurityUtility::recursivelyHashPassword($dirty_password, 100000, TFISH_SITE_SALT, $user['user_salt']);
			if ($password_hash == $user['password_hash']) {
				
				// Regenerate session due to priviledge escalation
				self::regenerateSession();
				$_SESSION['TFISH_LOGIN'] = true;
				header('location: ' . TFISH_ADMIN_URL . "admin.php");
				exit;
			} else {
				// Issue failed login warning, destroy session and redirect to the login page.
				self::logout(TFISH_ADMIN_URL . "login.php");
				exit;
			}
		} else {
			// Issue failed login warning.
			// Redirect to the login page.
			self::logout(TFISH_ADMIN_URL . "login.php");
			exit;
		}		
	}
	
	private static function _logout($clean_url)
	{	
		// Unset all of the session variables.
		$_SESSION = array();
		
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
	 * Watches for potential signs of session hijacking.
	 * 
	 * If the user IP address or user agent change this can indicate session hijacking, and the
	 * session ID should be regenerated as a precaution.
	 * 
	 * @return boolean true if ok false if IP or user agent have changed.
	 */
	protected static function preventHijacking()
	{		
		if (!isset($_SESSION['IPaddress']) || !isset($_SESSION['userAgent'])) {
			return false;
		}

		if ($_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR']) {
			return false;
		}

		if ( $_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT']) {
			return false;
		}

		return true;
	}
	
	/**
	 * Checks if the session has expired or been declared obsolete.
	 * 
	 * If the session has expired or become obsolete it should be destroyed and restarted.
	 * 
	 * @return boolean true if session is valid false if expired or obsolete
	 */
	protected static function validateSession()
	{	
		if ( isset($_SESSION['OBSOLETE']) && !isset($_SESSION['EXPIRES']) ) {
			return false;
		}

		if (isset($_SESSION['EXPIRES']) && $_SESSION['EXPIRES'] < time()) {
			return false;
		}

		return true;
	}
	
	/**
	 * Implementation of the secure cookie protocol of Liu, IKovacs, Huang and Gouda (2005).
	 * 
	 * This function is not in working order. Liu et al. have devised what seems to be a
	 * bulletproof session security mechanism. Unfortunately, it requires access to a SSL
	 * session key, which is only accessible with a specific server configuration, ie. in most
	 * cases it's just not available. Which renders this otherwise excellent idea impracticable
	 * for general use.
	 * 
	 * @return void
	 */
	protected static function secureSession()
	{
		/**
		 * Note that it *requires* use of SSL in order to access a session key.
		 * 
		 * Modifications:
		 * 1. Use the TFISH_KEY as the parent key rather than the server key due to possibility of 
		 * key-sharing in shared hosting.
		 * 2. Added a random piece of junk in the data payload, to confound sample-based attempts to
		 * recover the key.
		 * 
		 * Protocol is as follows:
		 * username|expiration time|(data)k|HMAC(username|expiration time|data|session key, k)
		 * where k=HMAC(username|expiration time, server key)
		 */
		
		$data = '';
		
		// Calculate the key k
		$k = hmac('sha256', $username . $expiration , TFISH_KEY);
		
		// Obtain the SSL session key
		$session_key = '';
		
		// Generate a bit of psuedo-random garbage payload to confound sample-based cryptanalysis
		$pad = '';

		// Calculate the cookie HMAC (SHA256)
		$hmac = hmac('sha256', $username . $expiration . $data . $session_key, $k);
		
		// Encrypt the data payload (session ID)
		// string mcrypt_encrypt ( string $cipher , string $key , string $data , string $mode [, string $iv ] )
		// Note that the '128' in the cipher specification refers to block size, NOT encryption strength!
		// So how do you do 256-bit AES encryption in PHP vs. 128-bit AES encryption???
		// The answer is:  Give it a key that's 32 bytes long as opposed to 16 bytes long.
		// For example:
		//$key256 = '12345678901234561234567890123456';
		//$key128 = '1234567890123456';
		// If you want to be AES compliant always choose the MCRYPT_RIJNDAEL_128 cipher constant.
		// For a good explanation of mycript_encrypt() see https://www.chilkatsoft.com/p/php_aes.asp
		$encrypted_data = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $k, $data, $mode);
		
		// Set the cookie parameters and away we go
	}
}