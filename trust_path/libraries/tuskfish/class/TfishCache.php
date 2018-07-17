<?php

/**
 * TfishCache class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Handles Tuskfish cache operations.
 * 
 * The cache can be enabled / disabled and expiry timer set in Tuskfish preferences.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
 */
class TfishCache
{
    /** @var object $loger Instance of TfishLogger class, used for logging errors. */
    protected $validator;
    protected $logger;
    
    /** @param object $preference Instance of TfishLogger class, used for logging errors. */
    function __construct(object $tfish_validator, object $tfish_logger)
    {
        if (is_object($tfish_validator)) {
            $this->validator = $tfish_validator;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (is_object($tfish_logger)) {
            $this->logger = $tfish_logger;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }        
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
     * @param object $tfish_preference TfishPreference object, to make site preferences available.
     * @param string $basename Page filename without extension, eg. 'article' (alphanumeric and 
     * underscore characters only).
     * @param array $params URL Query string parameters for this page as $key => $value pairs.
     */
    public function getCachedPage(object $tfish_preference, string $basename,
            array $params = array())
    {
        
        // Abort if cache is disabled.
        if (!$tfish_preference->enable_cache) {
            return;
        }
        
        // Check for directory traversals and null byte injection.
        if ($this->validator->hasTraversalorNullByte($basename)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            return false;
        }

        // Resolve the file name.
        $file_name = $this->_getCachedFileName($basename, $params);
        
        // Verify that the constructed path matches the canonical path. Exit cache if path is bad.
        $resolved_path = realpath(TFISH_PRIVATE_CACHE_PATH) . '/' . $file_name;
        
        if ($resolved_path != TFISH_PRIVATE_CACHE_PATH . $file_name) {
            return;
        }

        // Path is good, so check if the file actually exists and has not expired. If so, flush
        // the output buffer to screen. This buffer was opened in tfish_header.
        if (file_exists($resolved_path) && (filemtime($resolved_path) > 
                (time() - $tfish_preference->cache_life))) {
            echo file_get_contents($resolved_path);
            ob_end_flush();
            exit;
        }
        
    }

    /**
     * Calculate the return the name of a cached file, based on input parameters.
     * 
     * @param string $basename Page filename without extension, eg. 'article'. Alphanumeric and 
     * underscore characters only.
     * @param array $params URL query string parameters for this page as $key => $value pairs.
     */
    private function _getCachedFileName(string $basename, array $params)
    {
        $clean_filename = false;
        
        // Remove the extension.
        $basename = rtrim($basename, '.php');
        
        // Validate the parameters. All should be treated as alNumUnderscore strings.
        $basename = $this->validator->trimString($basename);
        
        if ($basename && $this->validator->isAlnumUnderscore($basename)) {
            $clean_filename = $basename;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
            exit;
        }

        if ($this->validator->isArray($params) && !empty($params)) {
            
            foreach ($params as $key => $value) {  
                if ($value) {
                    $clean_key = $this->validator->trimString($key);
                    $clean_value = $this->validator->trimString($value);
                    if ($this->validator->isAlnumUnderscore($clean_key)
                            && $this->validator->isAlnumUnderscore($clean_value)) {
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
     * @param object $tfish_preference TfishPreference object, to make the site preferences
     * available.
     * @param string $basename Filename of this page, alphanumeric and underscore characters only.
     * @param array $params URL Query string parameters for this page as $key => $value pairs.
     * @param string $buffer HTML page output from ob_get_contents().
     */
    public function cachePage(object $tfish_preference, string $basename,
            array $params, string $buffer)
    {        
        // Abort if cache is disabled.
        if (!$tfish_preference->enable_cache) {
            return;
        }
        
        // Check for directory traversals and null byte injection.
        if ($this->validator->hasTraversalorNullByte($basename)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            return false;
        }

        // Resolve the file name and verify that the constructed path matches the canonical path.
        $file_name = $this->_getCachedFileName($basename, $params);
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
     * pagination controls stay up to date). Later it would be good to be more selective.
     * 
     * @return bool True on success, false on failure.
     */
    public function flushCache()
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
                            $this->logger->logError($e->getCode(), $e->getMessage(), $e->getFile(),
                                    $e->getLine());
                        }
                    } else {
                        trigger_error(TFISH_ERROR_BAD_PATH, E_USER_NOTICE);
                        return false;
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->logError($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
        return true;
    }

}
