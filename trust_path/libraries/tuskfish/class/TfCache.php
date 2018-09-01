<?php

/**
 * TfCache class file.
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
 * Handles Tuskfish page-level caching operations.
 * 
 * Cached pages are written to the private cache directory(trust_path/cache). The cache can be
 * enabled / disabled and a expiry timer set in Tuskfish preferences.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.1
 * @since       1.0
 * @package     core
 * @var         TfishValidator $validator An instance of the Tuskfish data validator class.
 * @var         TfPreference $preference An instance of the Tuskfish site preferences class.
 * 
 */
class TfCache
{
    protected $validator;
    protected $preference;
    
    /**
     * Constructor.
     * 
     * @param TfValidator $validator An instance of the Tuskfish data validator class.
     * @param TfPreference $preference An instance of the Tuskfish site preferences class.
     */
    function __construct(TfValidator $validator, TfPreference $preference)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator; 
        } else {
            trigger_error(TFISH_ERROR_NOT_VALIDATOR, E_USER_ERROR);
        }
        
        if (is_a($preference, 'TfPreference')) {
            $this->preference = $preference;
        }  else {
            trigger_error(TFISH_ERROR_NOT_PREFERENCE, E_USER_ERROR);
        }
    }
    
    /**
     * Save a copy of this page to the cache directory.
     * 
     * This function should be called in tfFooter.php, before ob_end_flush(). Note that
     * warnings are suppressed when trying to open the file. The query parameters are important
     * to retrieve the precise representation of the page requested, since they change its state.
     * 
     * @param string $basename Filename of this page (without extension), alphanumeric and
     * underscore characters only.
     * @param array $params URL Query string parameters for this page as $key => $value pairs.
     * @param string $buffer HTML page output from ob_get_contents().
     */
    public function cachePage(string $basename, array $params, string $buffer)
    {        
        // Abort if cache is disabled.
        if (!$this->preference->enableCache) {
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

        // Write the page to the cache.
        if (false !== ($f = @fopen($file_path, 'w'))) {
            fwrite($f, $buffer);
            fclose($f);
        }
    }
    
    /**
     * Clear the private cache.
     * 
     * The entire cache will be cleared. This method is called if a single object is added, edited
     * or destroyed to ensure that index pages and pagination controls stay up to date.
     * 
     * @return bool True on success, false on failure.
     */
    public function flushCache()
    {
        $directory_iterator = new DirectoryIterator(TFISH_PRIVATE_CACHE_PATH);

        foreach ($directory_iterator as $file) {

            if ($file->isFile()) {
                $path = TFISH_PRIVATE_CACHE_PATH . $file->getFileName();

                if ($path && file_exists($path)) {
                    try {
                        unlink($path);
                    } catch (Exeption $e) {
                        trigger_error(TFISH_CACHE_FLUSH_FAILED_TO_UNLINK, E_USER_NOTICE);
                        return false;
                    }
                } else {
                    trigger_error(TFISH_CACHE_FLUSH_FAILED_BAD_PATH, E_USER_NOTICE);
                    return false;
                }
            }
        }
        
        return true;
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
     * If a cached page is not available controller script execution will simply proceed and 
     * tfFooter.php will request the page be written to cache, assuming that caching is enabled.
     * This function should be called after tfHeader.php is included.
     * 
     * @param string $basename Page filename without extension, eg. 'article' (alphanumeric and 
     * underscore characters only).
     * @param array $params URL Query string parameters for this page as $key => $value pairs.
     * @return string|bool Return cached page if exists, otherwise false.
     */
    public function getCachedPage(string $basename, array $params = array())
    {
        
        // Abort if cache is disabled.
        if (!$this->preference->enableCache) {
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
            return false;
        }

        // Path is good, so check if the file actually exists and has not expired. If so, flush
        // the output buffer to screen. This buffer was opened in tfHeader.
        if (file_exists($resolved_path) && (filemtime($resolved_path) > 
                (time() - $this->preference->cacheLife))) {
            echo file_get_contents($resolved_path);
            ob_end_flush();
            exit;
        }
        
    }

    /**
     * Calculate the return the name of a cached file, based on query string parameters.
     * 
     * @param string $basename Page filename without extension, eg. 'article'. Alphanumeric and 
     * underscore characters only.
     * @param array $params URL query string parameters for this page as $key => $value pairs.
     * @return string Name of the cached version of a file.
     */
    private function _getCachedFileName(string $basename, array $params)
    {
        $cleanFilename = false;
        
        // Remove the extension.
        $basename = rtrim($basename, '.php');
        
        // Validate the parameters. All should be treated as alNumUnderscore strings.
        $basename = $this->validator->trimString($basename);
        
        if ($basename && $this->validator->isAlnumUnderscore($basename)) {
            $cleanFilename = $basename;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
            exit;
        }

        if ($this->validator->isArray($params) && !empty($params)) {
            
            foreach ($params as $key => $value) {  
                if ($value) {
                    $cleanKey = $this->validator->trimString($key);
                    $cleanValue = $this->validator->trimString($value);
                    if ($this->validator->isAlnumUnderscore($cleanKey)
                            && $this->validator->isAlnumUnderscore($cleanValue)) {
                        $cleanFilename .= '&' . $cleanKey . '=' . $cleanValue;
                    }
                }
                
                unset($key, $value, $cleanKey, $cleanValue);
            }
        }

        return $cleanFilename . '.html';
    }

}
