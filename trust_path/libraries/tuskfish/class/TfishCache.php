<?php

/**
* Tuskfish cache object class.
* 
* Handles cache operations - checking if a cached versions of pages exist, generating them if they
* don't, clearing the cache when asked. Works pretty much the same way the image caching system
* does, but the image cache will be a subsiduary of this system. Need to build it into the existing
* output buffering and rendering framework somehow.
 * 
 * Some principles:
 * 
 * 1. Cache must come after all the header setup (autoload, error handler, connect to database, 
 * reading preferences and starting the session, admin check and site closed check. It *might* be
 * able to come before the language file, metadata and template assignment but this needs to be 
 * tested. Basically it would just be easier to run the cache check at the end of the header, for 
 * the time being.
 * 
 * 2. Calls to the cache directory need to be locked down to prevent directory traversals and similar
 * shenannigans. It would be best to construct URLs from the parameters passed in, rather than to
 * draw on $_SERVER['REQUEST_URL'], as all the data filtering is already in place. Cache calls for
 * individual content objects can simply be based on ID, nothing else is needed. Index pages are a
 * bit more complicated in that there can be multiple parameters.
 * 
 * 3. Cache timeout should be based on a preference. A minimum value should be enforced in order to
 * prevent admins from setting stupid ones. There should also be an on/off control, and a 'flush'
 * link in the control panel.
*
* @copyright	Simon Wilkinson (Crushdepth) 2017
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishFileHandler extends TfishAncestralObject
{
	public function __construct()
	{
		// Standard property accessors and restriction rules.
		parent::__construct();
		
		/**
		 * Whitelist of official properties and datatypes.
		 */
		$this->__properties['cache_life'] = 'int'; 
		// TFISH_CACHE_PATH and TFISH_CACHE_URL are available (defined in config.php).
		
		/**
		 * Set the permitted properties of this object.
		 */
		foreach ($this->__properties as $key => $value) {
			$this->__data[$key] = '';
		}
		
		/**
		 * Set default values of permitted properties.
		 */
		// Should set a sane (minimum) cache time, in case the admin enters a stupid one.
		$this->__data['cache_life'] = 86400; // 24 hours, for testing purpses.
	}

	/**
	 * 
	 * 
	 * @param int $id
	 */
	public static function checkCache($id) {
		
	}

	/**
	 * Save a copy of this page to the cache directory.
	 * 
	 */
	public static function cachePage() {

	}

	/**
	 * Clear the cache, or optionally a single file if ID parameter supplied.
	 * 
	 * @param int $id
	 * @return boolean
	 */
	public static function clearCache($id = false) {
		$result = self::_clearCache();
		if (!$result) {
			trigger_error(TFISH_ERROR_FAILED_TO_DELETE_DIRECTORY, E_USER_NOTICE);
			return false;
		}
		
		return true;
	}
	
	private static function _clearCache()
	{}
}

