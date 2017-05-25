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
* Calls to the cache directory need to be locked down to prevent directory traversals and similar
* shenannigans. It would be best to construct URLs from the parameters passed in, rather than to
* draw on $_SERVER['REQUEST_URL'], as all the data filtering is already in place. Cache calls for
* individual content objects can simply be based on ID, nothing else is needed. Index pages are a
* bit more complicated in that there can be multiple parameters.
* 
* Cache timeout should be based on a preference. A minimum value should be enforced in order to
* prevent admins from setting stupid ones. There should also be an on/off control, and a 'flush'
* link in the control panel.
* 
* Issues:
* - Current implementation only works for individual objects with IDs. Need to allow for index
*   pages with a few parameters attached as well. Use naming convention index . basename . param
*   to allow index pages to be identified separately from ID pages.
* - Cache preferences need to be implemented (on/off switch, expiry timer) - easy.
* - Validation of parameters and file paths needs to be rigorous - doable.
* - If someone requests a page that doesn't exist, the 'no content' page gets cached. This could
*   cause problems if an object with that ID was created later. Solution: Object-specific cache
*   pages are destroyed each time an object is created, modified or deleted.
* - Index pages, tags and collections may have a problem with cached pagination controls becoming
*   out of date or wrong when content is added, edited or deleted.
* - A lot of these (index page) problems would be solved by clearing the entire cache each time a 
*  piece of content is added, edited or deleted. For an infrequently updated site this is not an 
*  issue, but for frequently updated sites it reduces the value of the cache.
*
* @copyright	Simon Wilkinson (Crushdepth) 2017
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishCache
{
	private static $_cache_life;
	
	public function __construct()
	{
		// Should set a sane (minimum) cache time. 24 hours, for testing purpses.
		self::$_cache_life = 86400;
	}
	
	/**
	 * Check if a cached page exists and has not expired, and displays it.
	 * 
	 * You should only pass in parameters that you were expecting and had explicitly whitelisted
	 * and have already validated. Gating the parameters in this way reduces the opportunity for
	 * exploitation.
	 * 
	 * Note that only alphanumeric and underscore characters are permitted in the
	 * basename parameter; this is to avoid directory traversals.
	 * 
	 * If a cached page is not available execution will simply proceed and tfish_footer.php will
	 * request the page be written to cache. This function should be called after tfish_header.php
	 * is included.
	 * 
	 * @param string $basename alphanumeric and underscore characters only.
	 * @param array $params
	 * @return void
	 */
	public static function checkCache($basename, $params = array()) {
		
		// Resolve the file name.
		$file_name = self::_getCachedFileName($basename, $params);
		// Verify that the constructed path matches the canonical path. Exit cache if path is bad.
		$resolved_path = realpath(TFISH_CACHE_PATH) . '/' . $file_name;
		if ($resolved_path != TFISH_CACHE_PATH . $file_name) {
			return;
		}
		
		// Path is good, so check if the file actually exists and has not expired. If so, flush
		// the output buffer to screen. This buffer was opened in tfish_header.
		if (file_exists($resolved_path) && (filemtime($resolved_path) < (time() - self::$_cache_life))) {
			echo file_get_contents($resolved_path);
			ob_end_flush();
			exit;
		}
	}

	/**
	 * Calculate the return the name of a cached file, based on input parameters.
	 * 
	 * @param int $id
	 * @return string
	 */
	private static function _getCachedFileName($basename, $params) {
		
		$clean_filename = false;
		$basename = rtrim($basename, '.php'); // Remove the extension.
		
		// Validate the parameters. All should be treated as alNumUnderscore strings.
		$basename = TfishFilter::trimString($basename);
		if ($basename && TfishFilter::isAlnumUnderscore($basename)) {
			$clean_filename = $basename;
		} else {
			trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
			exit;
		}
		
		if (TfishFilter::isArray($params) && !empty($params)) {			
			foreach ($params as $key => $value) {
				if ($value) {
					$clean_key = TfishFilter::trimString($key);
					$clean_value = TfishFilter::trimString($value);
					if (TfishFilter::isAlnumUnderscore($clean_key) && TfishFilter::isAlnumUnderscore($clean_value)) {
						$clean_filename .= '&' . $clean_key . '=' . $clean_value;
					}
				}
				unset($key, $value, $clean_key, $clean_value);
			}
		}
		
		return $clean_filename . '.html';
	}	
	
	/**
	 * Save a copy of this page to the cache directory.
	 * 
	 * This function should be called in tfish_footer.php, before ob_end_flush(). Note that
	 * warnings are suppressed when trying to open the file.
	 * 
	 */
	public static function cachePage($basename, $params, $buffer) {
		
		// Resolve the file name and vrify that the constructed path matches the canonical path.
		$file_name = self::_getCachedFileName($basename, $params);
		$file_path = realpath(TFISH_CACHE_PATH) . '/' . $file_name;
		if ($file_path != TFISH_CACHE_PATH . $file_name) {
			return;
		}
		
		if(false !== ($f = @fopen($file_path, 'w'))) {
			fwrite($f, $buffer);
			fclose($f);
		}
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

