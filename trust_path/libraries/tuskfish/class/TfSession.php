<?php

/**
 * TfSession class file.
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

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/** 
 * Provides functions for managing user sessions in a security-conscious manner.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     security
 * @var         TfValidator $validator Instance of the Tuskfish data validator class.
 * @var         TfDatabase $db Instance of the Tuskfish database class.
 * @var         TfPreference $preference Instance of the Tuskfish site preference class.
 */
class TfSession
{
    
    /** Set within the start() method; ideally this should be injected but I want to keep the
     * session class static for now. */
    private static $validator;
    private static $db;
    private static $preference;
    
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
        self::setToken();
    }
    
    /**
     * Returns a login or logout link for insertion in the template.
     * 
     * @return string HTML login or logout link.
     */
    public static function getLoginLink()
    {
        if (self::isAdmin()) {
            return '<a href="' . TFISH_ADMIN_URL . 'login.php?op=logout">' . TFISH_LOGOUT . '</a>';
        } else {
            return '<a href="' . TFISH_ADMIN_URL . 'login.php">' . TFISH_LOGIN . '</a>';
        }
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
        if (isset($_SESSION['TFISH_LOGIN']) && $_SESSION['TFISH_LOGIN'] === true) {
            return true;
        } else {
            return false;
        }
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
     * Checks if a session has expired and sets last seen activity flag.
     * 
     * @param object $tfPreference TfPreference object.
     * @return bool True if session has expired, false if not.
     */
    public static function isExpired()
    {
        // Check if session carries a destroyed flag and kill it if the grace timer has expired.
        if (isset($_SESSION['destroyed']) && time() > $_SESSION['destroyed']) {
            return true;
        }

        // Check for "last seen" timestamp.
        $last_seen = isset($_SESSION['last_seen']) ? (int) $_SESSION['last_seen'] : false;

        // Check expiry (but not if sessionLife === 0).
        if ($last_seen && self::$preference->sessionLife > 0) {
            if ($last_seen && (time() - $last_seen) > (self::$preference->sessionLife * 60)) {
                return true;
            }
        }

        // Session not seen before, add an activity timestamp.
        $_SESSION['last_seen'] = time();

        return false;
    }

    /**
     * Authenticate the user and establish a session.
     * 
     * The number of failed login attempts is tracked. Subsequent login attempts will sleep for
     * an equivalent number of seconds before processing, in order to frustrate brute force attacks.
     * A successful login will reset the counter to zero. Note that the password field is
     * unrestricted content.
     * 
     * @param string $email Input email.
     * @param string $password Input password.
     */
    public static function login(string $email, string $password)
    {
        // Check email and password have been supplied
        if (empty($email) || empty($password)) {
            // Issue incomplete form warning and redirect to the login page.
            self::logout(TFISH_ADMIN_URL . "login.php");
        } else {
            // Validate the admin email (which functions as the username in Tuskfish CMS)
            $cleanEmail = self::$validator->trimString($email);
            
            if (self::$validator->isEmail($cleanEmail)) {
                self::_login($cleanEmail, $password);
            } else {
                // Issue warning - email should follow email format
                self::logout(TFISH_ADMIN_URL . "login.php");
            }
        }
    }

    /** @internal */
    private static function _login(string $cleanEmail, string $dirtyPassword)
    {
        // Query the database for a matching user.
        $statement = self::$db->preparedStatement("SELECT * FROM user WHERE "
                . "`adminEmail` = :clean_email");
        $statement->bindParam(':clean_email', $cleanEmail, PDO::PARAM_STR);
        $statement->execute();
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        // Authenticate user by calculating their password hash and comparing it to the one on file.
        if ($user) {
            // If the user has previous failed login atttempts sleep to frustrate brute force attacks.
            if ($user['loginErrors']) {
                sleep((int) $user['loginErrors']);
            }
            
            // If login successful regenerate session due to privilege escalation.
            if (password_verify($dirtyPassword, $user['passwordHash'])) {
                self::regenerate();
                $_SESSION['TFISH_LOGIN'] = true;
                $_SESSION['userId'] = (int) $user['id'];
                
                // Reset failed login counter to zero.
                self::$db->update('user', (int) $user['id'], array('loginErrors' => 0));
                
                // Redirect to admin page.
                header('location: ' . TFISH_ADMIN_URL . "admin.php");
                exit;
            } else {
                // Increment failed login counter, destroy session and redirect to the login page.
                self::$db->updateCounter((int) $user['id'], 'user', 'loginErrors');
                self::logout(TFISH_ADMIN_URL . "login.php");
                exit;
            }
        } else {
            // Redirect to login page.
            self::logout(TFISH_ADMIN_URL . "login.php");
            exit;
        }
    }
    
    /**
     * Destroys the current session on logout
     * 
     * @param string $url_redirect The URL to redirect the user to on logging out. 
     */
    public static function logout(string $url_redirect = '')
    {
        $cleanUrl = '';
        
        if (!empty($url_redirect)) {
            $url_redirect = self::$validator->trimString($url_redirect);
            $cleanUrl = self::$validator->isUrl($url_redirect) ? $url_redirect : '';
        }
        
        self::_logout($cleanUrl);
    }

    /** @internal */
    private static function _logout(string $cleanUrl)
    {
        // Unset all of the session variables.
        $_SESSION = [];

        // Destroy the session cookie, DESTROY IT ISILDUR!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]);
        }

        // Destroy the session and redirect
        session_destroy();
        if ($cleanUrl) {
            header('location: ' . $cleanUrl);
            exit;
        } else {
            header('location: ' . TFISH_URL);
            exit;
        }
    }
    
    /**
     * Authenticate the user with two factors and establish a session.
     * 
     * Requires a Yubikey hardware token as the second factor. Note that the authenticator type
     * is not declared, as the desired response is to logout and redirect, rather than to throw
     * an error.
     * 
     * @param string $dirtyPassword Input password.
     * @param string $dirtyOtp Input Yubikey one-time password.
     * @param object $yubikey Instance of the TfYubikeyAuthenticator class.
     */
    public static function twoFactorLogin(string $dirtyPassword, string $dirtyOtp,
            TfYubikeyAuthenticator $yubikey)
    {
        // Check password, OTP and Yubikey have been supplied
        if (empty($dirtyPassword) || empty($dirtyOtp) || empty($yubikey)) {
            self::logout(TFISH_ADMIN_URL . "login.php");
            exit;
        }
        
        $dirtyOtp = self::$validator->trimString($dirtyOtp);
        
        // Yubikey OTP should be 44 characters long.
        if (mb_strlen($dirtyOtp, "UTF-8") != 44) {
            self::logout(TFISH_ADMIN_URL . "login.php");
            exit;
        }
        
        // Yubikey OTP should be alphabetic characters only.
        if (!self::$validator->isAlpha($dirtyOtp)) {
            self::logout(TFISH_ADMIN_URL . "login.php");
            exit;
        }
        
        // Yubikey should be TfYubikeyAuthenticator class or equivalent.
        if (!is_a($yubikey, 'TfYubikeyAuthenticator')) {
            self::logout(TFISH_ADMIN_URL . "login.php");
            exit;
        }
        
        // Public ID is the first 12 characters of the OTP.
        $dirty_id = mb_substr($dirtyOtp, 0, 12, 'UTF-8');
        
        self::_twoFactorLogin($dirty_id, $dirtyPassword, $dirtyOtp, $yubikey);
    }
    
    /** @internal */
    private static function _twoFactorLogin(string $dirty_id, string $dirtyPassword, string $dirtyOtp,
            TfYubikeyAuthenticator $yubikey)
    {
        $user = false;
        $first_factor = false;
        $second_factor = false;
        
        // Query the database for a matching user.
        $statement = self::$db->preparedStatement("SELECT * FROM user WHERE "
                . "`yubikeyId` = :yubikeyId OR "
                . "`yubikeyId2` = :yubikeyId");
        $statement->bindParam(':yubikeyId', $dirty_id, PDO::PARAM_STR);
        $statement->execute();
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        
        if (empty($user)) {
            self::logout(TFISH_ADMIN_URL . "login.php");
            exit;
        }
        
        // First factor authentication: Calculate password hash and compare to the one on file.        
        if (password_verify($dirtyPassword, $user['passwordHash'])) {
            $first_factor = true;
        }
        
        // Second factor authentication: Submit one-time password to Yubico authentication server.
        $second_factor = $yubikey->verify($dirtyOtp);
        
        // If both checks are good regenerate session due to priviledge escalation and login.
        if ($first_factor === true && $second_factor === true) {
            self::regenerate();
            $_SESSION['TFISH_LOGIN'] = true;
            // Added as a handle for the password change script.
            $_SESSION['userId'] = (int) $user['id'];
            header('location: ' . TFISH_ADMIN_URL . "admin.php");
            exit;
        }
        
        // Otherwise force logout.
        self::logout(TFISH_ADMIN_URL . "login.php");
        exit;
    }
    
    /**
     * Regenerates the session ID.
     * 
     * Called whenever there is a privilege escalation (login) or at random intervals to reduce
     * risk of session hijacking. Note that the cross-site request forgery validation token remains
     * the same, unless the session is destroyed. This is to prevent the random session ID
     * regeneration events creating false positive CSRF checks.
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

        // Create new session. Update ID and keep current session info. Old one is not destroyed.
        session_regenerate_id(false);
        // Get the (new) session ID.
        $new_session_id = session_id();
        // Lock the session and close it.
        session_write_close();
        // Set the session ID to the new value.
        session_id($new_session_id);
        // Now working with the new session. Note that old one still exists and both carry a
        // 'destroyed' flag.
        session_start();
        // Set a cross-site request forgery token.
        self::setToken();
        // Remove the destroyed flag from the new session. Old one will be destroyed next time
        // isExpired() is called on it.
        unset($_SESSION['destroyed']);
    }
    
    /**
     * Hashes and salts a password to harden it against dictionary attacks.
     * 
     * Uses the default password hashing algorithm, which wa bcrypt as of PHP 7.2, with a cost
     * of 12. If logging in is too slow, you could consider reducing this to 10 (the default value).
     * Lowering it further will weaken the security of the hash.
     * 
     * @param string $password Input password.
     * @return string Password hash, incorporating algorithm and difficulty information.
     */
    public static function hashPassword(string $password)
    {
        $options = array('cost' => 12);        
        $password = password_hash($password, PASSWORD_DEFAULT, $options);

        return $password;
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
     * Sets a token for use in cross-site request forgery checks on form submissions.
     * 
     * A random token is generated and stored in the current session (if not already set). The value
     * of this token is included as a hidden field in forms when they are loaded by the user. This
     * allows forms to be validated via validateFormToken().
     */
    public static function setToken()
    {
        if (empty($_SESSION['token'])) {
            $_SESSION['token'] = bin2hex(random_bytes(32)) ;
        }
    }
    
    /**
     * Initialises a session and sets session cookie parameters to security-conscious values. 
     * 
     * @param object $tfPreference TfPreference object.
     */
    public static function start(TfValidator $validator, TfDatabase $db,
            TfPreference $preference)
    {        
        // Force session to use cookies to prevent the session ID being passed in the URL.
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        
        if (is_a($validator, 'TfValidator')) {
            self::$validator = $validator;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
            self::logout(TFISH_ADMIN_URL . "login.php");
        }
        
        if (is_a($db, 'TfDatabase')) {
            self::$db = $db;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
            self::logout(TFISH_ADMIN_URL . "login.php");
        }
        
        if (is_a($preference, 'TfPreference')) {
            self::$preference = $preference;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
            self::logout(TFISH_ADMIN_URL . "login.php");
        }

        // Session name. If the preference has been messed up it will assign one.
        $sessionName = isset($preference->sessionName)
                ? $preference->sessionName : 'tf';

        // Session life time, in seconds. '0' means until the browser is closed.
        $lifetime = $preference->sessionLifetime;

        // Path on the domain where the cookie will work. Use a single slash for all paths (default,
        // as there are admin checks in some templates).
        $path = '/';

        // Cookie domain, for example www.php.net. To make cookies visible on all subdomains
        // (default) prefix with dot eg. '.php.net'
        $domain = isset($domain) ? $domain : ltrim($_SERVER['SERVER_NAME'], 'www');

        // If true the cookie will only be sent over secure connections.
        $secure = isset($_SERVER['HTTPS']);

        // If true PHP will *attempt* to send the httponly flag when setting the session cookie.
        $http_only = true;

        // Set the parameters and start the session.
        session_name($sessionName);
        session_set_cookie_params($lifetime, $path, $domain, $secure, $http_only);
        session_start();
        
        // Set a CSRF token.
        self::setToken();

        // Check if the session has expired.
        if (self::isExpired($preference))
            self::destroy();

        // Check for signs of session hijacking and regenerate if at risk. 10% chance of doing it
        // anyway.
        if (!self::isClean()) {
            self::reset();
            self::regenerate();
        } elseif (rand(1, 100) <= 10) {
            self::regenerate();
        }
    }
    
    /**
     * Validate a cross-site request forgery token from a form submission.
     * 
     * Forms contain a hidden field with a random token taken from the user's session. This token
     * is used to validate that a form submission did indeed originate from the user, by comparing
     * the value against that stored in the user's session. If they do not match then the request
     * could be a forgery and the form submission should be rejected.
     * 
     * @param string $token A form token to validate against the user's session.
     * @return boolean True if token is valid, otherwise false.
     */
    public static function validateToken(string $token)
    {
        $cleanToken = self::$validator->trimString($token);

        // Valid token.
        if (!empty($_SESSION['token']) && $_SESSION['token'] === $cleanToken) {
            return true;
        }
        
        // Invalid token - redirect to warning message and cease processing the request.
        header('location: ' . TFISH_URL . 'token.php');
        exit;
    }

}
