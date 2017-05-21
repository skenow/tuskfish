<?php

/**
* Tuskfish cache object class.
* 
* Handles cache operations - checking if a cached versions of pages exist, generating them if they
* don't, clearing the cache when asked. Works pretty much the same way the image caching system
* does, but the image cache will be a subsiduary of this system. Need to build it into the existing
* output buffering and rendering framework somehow. 
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
		// Time to expire (should be a preference but let's worry about that later).
		
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

	// Check if a cached version of the page exists.
	// What file name convention should be used?
	public static function checkCache() {

	}

	// If not, generate a cached version.
	// What file name convention should be used?
	public static function cachePage() {

	}

	// Clear the cache directory (only). Need to lock the file path down hard (see TfishFileHandler).
	// Torch everything, or a specific file. The latter would be useful
	// if you are just editing a particular object.
	public static function clearCache() {
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

