<?php

/**
 * TfishSession class file.
 * 
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		security
 */

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Tuskfish session security class.
 * 
 * Provides functions for managing sessions in a security-conscious manner.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		security
 */
class TfishSession
{
    
    /** No instantiation permitted. */
    final private function __construct()
    {
    }
    
    /** No cloning permitted */
    final private function __clone()
    {
    }

    /**
     * Unset session variables and destroy the session.
     */
    public static function destroy()
    {
        $_SESSION = [];
        session_destroy();
        session_start();
    }

    /**
     * Shorthand admin privileges check.
     * 
     * For added security this could retrieve an encrypted token, preferably the SSL session id,
     * although thats availability seems to depend on server configuration.
     * 
     * @return bool True if admin false if not.
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
     * @param object $tfish_preference TfishPreference object.
     * @return bool True if session has expired, false if not.
     */
    public static function isExpired($tfish_preference)
    {
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
     * These tests can indicate session hijacking but are by no means definitive; however they do
     * indicate elevated risk and the session should be regenerated as a counter measure.
     * 
     * @return bool True if IP/user agent are unchanged, false otherwise.
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
     * @param string $email Input email.
     * @param string $password Input password.
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

    /** @internal */
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
     * Requires a Yubikey hardware token as the second factor.
     * 
     * @param string $dirty_password Input password.
     * @param string $dirty_otp Input Yubikey one-time password.
     * @param object $yubikey Instance of the TfishYubikeyAuthenticator class.
     */
    public static function twoFactorLogin($dirty_password, $dirty_otp, $yubikey)
    {
        // Check password, OTP and Yubikey have been supplied
        if (empty($dirty_password) || empty($dirty_otp) || empty($yubikey)) {
            self::logout(TFISH_ADMIN_URL . "login.php");
            exit;
        }
        
        $dirty_otp = TfishFilter::trimString($dirty_otp);
        
        // Yubikey OTP should be 44 characters long.
        if (mb_strlen($dirty_otp, "UTF-8") != 44) {
            self::logout(TFISH_ADMIN_URL . "login.php");
            exit;
        }
        
        // Yubikey OTP should be alphabetic characters only.
        if (!TfishFilter::isAlpha($dirty_otp)) {
            self::logout(TFISH_ADMIN_URL . "login.php");
            exit;
        }
        
        // Yubikey should be TfishYubikeyAuthenticator class.
        if (!is_object($yubikey) || get_class($yubikey) != 'TfishYubikeyAuthenticator') {
            self::logout(TFISH_ADMIN_URL . "login.php");
            exit;
        }
        
        // Public ID is the first 12 characters of the OTP.
        $dirty_id = mb_substr($dirty_otp, 0, 12, 'UTF-8');
        
        self::_twoFactorLogin($dirty_id, $dirty_password, $dirty_otp, $yubikey);
    }
    
    /** @internal */
    private static function _twoFactorLogin($dirty_id, $dirty_password, $dirty_otp, $yubikey)
    {
        $user = false;
        $first_factor = false;
        $second_factor = false;
        
        // Query the database for a matching user.
        $statement = TfishDatabase::preparedStatement("SELECT * FROM user WHERE "
                . "`yubikey_id` = :yubikey_id OR "
                . "`yubikey_id2` = :yubikey_id");
        $statement->bindParam(':yubikey_id', $dirty_id, PDO::PARAM_STR);
        $statement->execute();
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        
        if (empty($user)) {
            self::logout(TFISH_ADMIN_URL . "login.php");
            exit;
        }
        
        // First factor authentication: Calculate password hash and compare to the one on file.
        $password_hash = TfishSecurityUtility::recursivelyHashPassword($dirty_password, 100000, TFISH_SITE_SALT, $user['user_salt']);
        if ($password_hash == $user['password_hash']) {
            $first_factor = true;
        }
        
        // Second factor authentication: Submit one-time password to Yubico authentication server.
        $second_factor = $yubikey->verify($dirty_otp);
        
        // If both checks are good regenerate session due to priviledge escalation and login.
        if ($first_factor === true && $second_factor === true) {
            self::regenerate();
            $_SESSION['TFISH_LOGIN'] = true;
            $_SESSION['user_id'] = (int) $user['id']; // Added as a handle for the password change script.
            header('location: ' . TFISH_ADMIN_URL . "admin.php");
            exit;
        }
        
        // Otherwise force logout.
        self::logout(TFISH_ADMIN_URL . "login.php");
        exit;
    }

    /**
     * Returns a login or logout link for insertion in the template.
     * 
     * @return string HTML login or logout link.
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
     * @param string $url_redirect The URL to redirect the user to on logging out. 
     */
    public static function logout($url_redirect = false)
    {
        $clean_url = false;
        if ($url_redirect) {
            $clean_url = TfishFilter::isUrl($url_redirect) ? $url_redirect : false;
        }
        self::_logout($clean_url);
    }

    /** @internal */
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
     * Reset session data after a session hijacking check fails. This will force logout.
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
     * Initialises a session and sets session cookie parameters to security-conscious values. 
     * 
     * @param object $tfish_preference TfishPreference object.
     */
    public static function start($tfish_preference)
    {
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
        session_start($tfish_preference);

        // Check if the session has expired.
        if (self::isExpired($tfish_preference))
            self::destroy();

        // Check for signs of session hijacking and regenerate if at risk. 10% chance of doing it anyway.
        if (!self::isClean()) {
            self::reset();
            self::regenerate();
        } elseif (rand(1, 100) <= 10) {
            self::regenerate();
        }
    }

}
