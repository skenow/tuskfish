<?php

/**
 * Tuskfish cookie security class.
 * 
 * Session cookie parameters must be set before calling session_start(). Cookies will be REMOVED
 * from the public-facing areas of Tuskfish in due course.
 *
 * @copyright	Simon Wilkinson (Crushdepth) 2016
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @since		1.0
 * @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
 * @package		core
 */
if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishCookie {

    function __construct(&$handler) {
        
    }

    /**
     * Set session cookie parameters.
     * 
     * Note that use of $secure = true requires the site to be served under SSL.
     * 
     * @param int $limit
     * @param string $path
     * @param string|null $domain
     * @param bool $secure
     * @param bool $httponly
     * @return void
     */
    public static function setSessionCookie($limit = 0, $path = '/admin/', $domain = null, $secure = true, $httponly = true) {
        session_name($tfish_preference->session_name);
        $domain = isset($domain) ? $domain : isset($_SERVER['SERVER_NAME']);
        session_set_cookie_params($limit, $path, $domain, $secure, $httponly);
    }

    /**
     * Set data cookie parameters.
     * 
     * Note that use of $secure = true requires the site to be served under SSL.
     * 
     * @param string $name
     * @param  string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     */
    public static function setDataCookie($name, $value, $expire = 0, $path = '/admin', $domain = '', $secure = true, $httponly = true) {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

}
