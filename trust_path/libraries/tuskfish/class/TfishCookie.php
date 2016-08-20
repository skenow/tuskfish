<?php

/**
* Tuskfish cookie security class
* 
* Note that session cookie parameters must be set before calling session_start(). By default, 
* cookies are only deployed in the admin section of Tuskfish, although this can be overriden.
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishCookie
{
	/**
	 * Generic constructor and object definition - unset any properties not required by this content subclass
	 */
	function __construct(&$handler)
	{
	}
	
	// Session cookie test parameters. Note that use of $secure = true requires the site to be served under SSL
	public static function setSessionCookie($limit = 0, $path = '/admin/', $domain = null, $secure = true, $httponly = true)
	{
		session_name($tfish_preference->session_name);
		$domain = isset($domain) ? $domain : isset($_SERVER['SERVER_NAME']);
		session_set_cookie_params($limit, $path, $domain, $secure, $httponly);
	}
	
	// Data cookie parmeters. Note that use of $secure = true requires the site to be served under SSL
	public static function setDataCookie($name, $value, $expire = 0, $path = '/admin', $domain = '', $secure = true, $httponly = true)
	{
		setcookie ($name, $value, $expire, $path, $domain, $secure, $httponly);
	}
	
}