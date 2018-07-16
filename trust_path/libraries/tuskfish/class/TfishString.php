<?php

/**
 * TfishString trait file.
 * 
 * Provides common string handling methods.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */

/**
 * TfishString trait.
 * 
 * Provides common string handling methods.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.03
 * @package     core
 * 
 */
trait TfishString
{
    
    /**
     * Check if the character encoding of text is UTF-8.
     * 
     * All strings received from external sources must be passed through this function, particularly
     * prior to storage in the database.
     * 
     * @param string $dirty_string Input string to check.
     * @return bool True if string is UTF-8 encoded otherwise false.
     */
    public function isUtf8(string $dirty_string)
    {
        return mb_check_encoding($dirty_string, 'UTF-8');
    }
    
    /**
     * Cast to string, check UTF-8 encoding and strip trailing whitespace and control characters.
     * 
     * Removes trailing whitespace and control characters (ASCII <= 32), checks for UTF-8 character
     * set and casts input to a string. Note that the data returned by this function still
     * requires escaping at the point of use; it is not database or XSS safe.
     * 
     * As the input is cast to a string do NOT apply this function to non-string types (int, float,
     * bool, object, resource, null, array, etc).
     * 
     * @param mixed $dirty_string Input to be trimmed.
     * @return string Trimmed and UTF-8 validated string.
     */
    public function trimString($dirty_string)
    {
        $dirty_string = (string) $dirty_string;
        
        if ($this->isUtf8($dirty_string)) {
            // Trims all control characters plus space (ASCII / UTF-8 points 0-32 inclusive).
            return trim($dirty_string, "\x00..\x20");
        } else {
            return '';
        }
    }
    
}
