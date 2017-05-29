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
 * @copyright	Simon Wilkinson (Crushdepth) 2017
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @since		1.0
 * @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
 * @package		core
 */
if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishCache
{

    public function __construct()
    {
        
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
    public static function checkCache($basename, $params = array())
    {

        global $tfish_preference;

        // Abort if cache is disabled.
        if (!$tfish_preference->enable_cache) {
            return;
        }

        // Resolve the file name.
        $file_name = self::_getCachedFileName($basename, $params);
        // Verify that the constructed path matches the canonical path. Exit cache if path is bad.
        $resolved_path = realpath(TFISH_PRIVATE_CACHE_PATH) . '/' . $file_name;
        if ($resolved_path != TFISH_PRIVATE_CACHE_PATH . $file_name) {
            return;
        }

        // Path is good, so check if the file actually exists and has not expired. If so, flush
        // the output buffer to screen. This buffer was opened in tfish_header.
        if (file_exists($resolved_path) && (filemtime($resolved_path) > (time() - $tfish_preference->cache_life))) {
            echo file_get_contents($resolved_path);
            ob_end_flush();
            exit;
        } else {
            
        }
    }

    /**
     * Calculate the return the name of a cached file, based on input parameters.
     * 
     * @param int $id
     * @return string
     */
    private static function _getCachedFileName($basename, $params)
    {

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
    public static function cachePage($basename, $params, $buffer)
    {

        global $tfish_preference;

        // Abort if cache is disabled.
        if (!$tfish_preference->enable_cache) {
            return;
        }

        // Resolve the file name and verify that the constructed path matches the canonical path.
        $file_name = self::_getCachedFileName($basename, $params);
        $file_path = realpath(TFISH_PRIVATE_CACHE_PATH) . '/' . $file_name;
        if ($file_path != TFISH_PRIVATE_CACHE_PATH . $file_name) {
            return;
        }

        if (false !== ($f = @fopen($file_path, 'w'))) {
            fwrite($f, $buffer);
            fclose($f);
        }
    }

    /**
     * Clear the private cache.
     * 
     * At the moment this is something of a blunt instrument; the entire cache will be cleared
     * if a single object is added, edited or destroyed (this is to ensure that index pages and
     * pagination controls stay up to date). Later it would be good to be more selective, perhaps
     * marking individual object pages by their id, to allow them to be distinguished from index
     * pages. If an index.html is present it will be left in place (to prevent listing the cache
     * directory).
     * 
     * @return boolean success or failure.
     */
    public static function flushCache()
    {
        try {
            $directory_iterator = new DirectoryIterator(TFISH_PRIVATE_CACHE_PATH);
            foreach ($directory_iterator as $file) {
                if ($file->isFile()) {
                    $path = TFISH_PRIVATE_CACHE_PATH . $file->getFileName();
                    if ($path && file_exists($path)) {
                        try {
                            unlink($path);
                        } catch (Exeption $e) {
                            TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
                        }
                    } else {
                        trigger_error(TFISH_ERROR_BAD_PATH, E_USER_NOTICE);
                        return false;
                    }
                }
            }
        } catch (Exception $e) {
            TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
        return true;
    }

}
