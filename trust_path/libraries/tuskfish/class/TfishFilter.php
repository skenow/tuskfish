<?php

/**
 * Provides methods to validate different data types and to conduct range checks.
 * 
 * WARNING: The methods in this class validate TYPE COMPLIANCE ONLY. They DO NOT PROVIDE DATABASE
 * SAFETY in their own right. Use them in conjunction with prepared statements and bound values to
 * mitigate SQL injection.
 *
 * 1. Pass ALL STRING type data through the trimString() function first to check for UTF-8 encoding 
 * and basic whitespace & control character removal. Note that this function always returns a string,
 * so DO NOT USE IT ON NON-STRINGS. 
 * 
 * 2. Use the relevant type and pattern-specific methods to validate that other data types meet your
 * expectations.
 * 
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		core
 */
if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishFilter
{

    /**
     * Check if the character encoding of text is UTF-8.
     * 
     * All strings received from external sources must be passed through this function, particularly
     * prior to storage in the database.
     * 
     * @param string $dirty_string
     * @return bool true if UTF-8 otherwise false
     */
    public static function isUtf8($dirty_string)
    {
        return mb_check_encoding($dirty_string, 'UTF-8');
    }

    /**
     * Escape data for display to mitigate XSS attacks.
     * 
     * Applies htmlentities to text fields destined for output / display to limit XSS attacks.
     * Encoding of quotes and use of UTF-8 character set is hardcoded in.
     *
     * @param string $output
     * @return string
     */
    public static function escape($output)
    {
        return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }

    /**
     * URL-encode and escape a string for use in a URL.
     * 
     * Trims, checks for UTF-8 compliance, rawurlencodes and then escapes with htmlspeciachars().
     * If you wish to use the data on a landing page you must decode it with htmlspecialchars_decode()
     * followed by rawurldecode() in that order.
     * 
     * @param string $url
     * @return string
     */
    public static function encodeEscapeUrl($url)
    {
        $url = self::trimString($url); // Trim control characters, verify UTF-8 character set.
        $url = rawurlencode($url); // Encode characters to make them URL safe.
        $clean_url = self::escape($url); // Encode entities with htmlspecialchars()

        return $clean_url;
    }

    /**
     * Validate an array of input against expected data types, typically $_POST, $_GET or $_REQUEST.
     * 
     * Use this function to screen user-side input. If data passes validation it will be handed
     * back with minimalist sanitisation (control characters and trailing whitespace removed); if
     * validation fails it will return false, except for boolean validation which will return null
     * on failure.
     * 
     * Note that the "int" case does not provide range checks. If you want a specific range then
     * check the range with isInt() manually.
     *
     * @param array $dirty_vars untrusted input that requires validation
     * @param array $allowed_vars whitelist of permitted variables and expected data types
     * @return array
     */
    public static function filterData($dirty_vars, $allowed_vars)
    {
        $clean_vars = array();

        foreach ($allowed_vars as $key => $type) {
            if (isset($dirty_vars[$key])) {
                switch ($type) {
                    case "alpha":
                        $dirty_vars[$key] = self::trimString($dirty_vars[$key]);
                        $clean_vars[$key] = self::isAlpha($dirty_vars[$key]) ? $dirty_vars[$key] : false;
                        break;
                    case "alnum":
                        $dirty_vars[$key] = self::trimString($dirty_vars[$key]);
                        $clean_vars[$key] = self::isAlnum($dirty_vars[$key]) ? $dirty_vars[$key] : false;
                        break;
                    case "alnumunder":
                        $dirty_vars[$key] = self::trimString($dirty_vars[$key]);
                        $clean_vars[$key] = self::isAlnumUnderscore($dirty_vars[$key]) ? $dirty_vars[$key] : false;
                        break;
                    case "bool":
                        $dirty_vars[$key] = (bool) $dirty_vars[$key];
                        $clean_vars[$key] = self::isBool($dirty_vars[$key]) ? $dirty_vars[$key] : null;
                        break;
                    case "digit":
                        $dirty_vars[$key] = self::trimString($dirty_vars[$key]);
                        $clean_vars[$key] = self::isDigit($dirty_vars[$key]) ? $dirty_vars[$key] : false;
                        break;
                    case "email": // Checks conformity with email specification; does not escape quotes!
                        $dirty_vars[$key] = self::trimString($dirty_vars[$key]);
                        $clean_vars[$key] = self::isEmail($dirty_vars[$key]) ? $dirty_vars[$key] : false;
                        break;
                    case "float":
                        $dirty_vars[$key] = (float) $dirty_vars[$key];
                        $clean_vars[$key] = self::isFloat($dirty_vars[$key]) ? $dirty_vars[$key] : false;
                        break;
                    case "int":
                        $dirty_vars[$key] = (int) $dirty_vars[$key];
                        $clean_vars[$key] = self::isInt($dirty_vars[$key]) ? $dirty_vars[$key] : false;
                        break;
                    case "ip": // Accepts both private and public IP ranges but not reserved ranges
                        $dirty_vars[$key] = self::trimString($dirty_vars[$key]);
                        $clean_vars[$key] = self::isIp($dirty_vars[$key]) ? $dirty_vars[$key] : false;
                        break;
                    case "string": // Check for UTF-8 encoding; strip white space and control characters (ASCII < 32).
                        $clean_vars[$key] = self::trimString($dirty_vars[$key]);
                        break;
                    case "html": // Filter HTML input with the HTMLPurifier library (allowed tags must be configured there).
                        $dirty_vars[$key] = self::trimString($dirty_vars[$key]);
                        $clean_vars[$key] = self::filterHtml($dirty_vars[$key]);
                        break;
                    case "url": // Checks conformity with email specification; does not escape quotes!
                        $dirty_vars[$key] = self::trimString($dirty_vars[$key]);
                        $clean_vars[$key] = self::isUrl($dirty_vars[$key]) ? $dirty_vars[$key] : false;
                        break;
                }
            }
        }
        return $clean_vars;
    }

    /**
     * Validate (and to some extent, "sanitise") HTML input to conform with whitelisted tags.
     * 
     * Applies HTMLPurifier to validate and sanitise HTML input. The precise operation can be
     * modified by altering the configuration of HTMLPurifier.
     *
     * @param string $dirty_html
     * @param array $config
     * @return string validated HTML content
     */
    public static function filterHtml($dirty_html, $config = false)
    {
        if (self::isUtf8($dirty_html)) {
            if (class_exists('HTMLPurifier')) {
                $html_purifier = new HTMLPurifier($config);
                $clean_html = (string) $html_purifier->purify($dirty_html);
                return $clean_html;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Check that a string is comprised soley of alphabetical characters.
     *
     * @param string $alpha
     * @return bool true if valid alphabetical string, false otherwise
     */
    public static function isAlpha($alpha)
    {
        if (mb_strlen($alpha, 'UTF-8') > 0) {
            return preg_match('/[^a-z]/i', $alpha) ? false : true;
        } else {
            return false;
        }
    }

    /**
     * Check that a string is comprised soley of alphanumeric characters.
     *
     * @param string $alnum
     * @return bool true if valid alphanumerical string, false otherwise
     */
    public static function isAlnum($alnum)
    {
        if (mb_strlen($alnum, 'UTF-8') > 0) {
            return preg_match('/[^a-z0-9]/i', $alnum) ? false : true;
        } else {
            return false;
        }
    }

    /**
     * Check that a string is comprised solely of alphanumeric characters and underscores.
     * 
     * @param string $alnumUnderscore
     * @return bool true if valid alphanumerical or underscore string, false otherwise
     */
    public static function isAlnumUnderscore($alnumUnderscore)
    {
        if (mb_strlen($alnumUnderscore, 'UTF-8') > 0) {
            return preg_match('/[^a-z0-9_]/i', $alnumUnderscore) ? false : true;
        } else {
            return false;
        }
    }

    /**
     * Validate boolean input.
     * 
     * Be careful with the return value; this method simply determines if a value is boolean or
     * not; it does not return the actual value of the parameter.
     *
     * @param bool $bool
     * @return bool true if a valid boolean value, false otherwise.
     */
    public static function isBool($bool)
    {
        $result = filter_var($bool, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (is_null($result)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check that a string is comprised solely of digits.
     *
     * @param string $digit
     * @return bool true if valid digit string, false otherwise
     */
    public static function isDigit($digit)
    {
        if (mb_strlen($digit, 'UTF-8') > 0) {
            return preg_match('/[^0-9]/', $digit) ? false : true;
        } else {
            return false;
        }
    }

    /**
     * Check if an email address is valid.
     * 
     * Note that single quotes ' are a valid character in email addresses, so the output of this 
     * filter IS NOT DATABASE SAFE in of itself.
     *
     * @param string $email
     * @return boolean true if valid email address, otherwise false
     */
    public static function isEmail($email)
    {
        if (mb_strlen($email, 'UTF-8') > 2) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        } else {
            return false;
        }
    }

    /**
     * Validate float (decimal point allowed).
     * 
     * Potential problem - is_float() allows exponents.
     *
     * @param float $float
     * @return boolean true if valid float, otherwise false
     */
    public static function isFloat($float)
    {
        return is_float($float);
    }

    /**
     * Validate integer, optionally include range check.
     * 
     * @param int $int
     * @param int $min
     * @param int $max
     * @return bool true if valid int and within optional range check, false otherwise
     */
    public static function isInt($int, $min = false, $max = false)
    {
        $clean_int = is_int($int) ? (int) $int : false;
        $clean_min = is_int($min) ? (int) $min : false;
        $clean_max = is_int($max) ? (int) $max : false;

        // Range check on minimum and maximum value.
        if (is_int($clean_int) && is_int($clean_min) && is_int($clean_max)) {
            return ($clean_int >= $clean_min) && ($clean_int <= $clean_max) ? true : false;
        }

        // Range check on minimum value.
        if (is_int($clean_int) && is_int($clean_min) && ($clean_max === false)) {
            return $clean_int >= $clean_min ? true : false;
        }

        // Range check on maximum value.
        if (is_int($clean_int) && ($clean_min === false) && is_int($clean_max)) {
            return $clean_int <= $clean_max ? true : false;
        }

        // Simple use case, no range check.
        if (is_int($clean_int) && ($clean_min === false) && ($clean_max === false)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validates IP addresses. Accepts private (but not reserved) ranges. Optionally IPV6.
     *
     * @param string $ip
     * @param int $version
     * @return bool true if valid IP address, false otherwise
     */
    public static function isIp($ip, $version = false)
    {
        if ($version == 6) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE) === false) {
                return true;
            } else {
                return false;
            }
        } else {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE) === false) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Strip trailing whitespace, control characters, check UTF-8 character set and cast to string.
     * 
     * Removes trailing whitespace and control characters (ASCII < 32), checks for UTF-8 character
     * set and casts input to a string. Note that the data returned by this function still
     * requires escaping at the point of use; it is not database safe.
     * 
     * As the input is cast to a string do NOT apply this function to non-string types (int, float,
     * bool, object, resource, null, array, etc).
     * 
     * @param string $dirty_text
     * @return string
     */
    public static function trimString($dirty_text)
    {
        if (self::isUtf8($dirty_text)) {
            // Trims all control characters plus space (ASCII 0-32 inclusive)
            return (string) trim($dirty_text, "\x00..\x20");
            // Trim non-breaking space in UTF-8
            // trim($data, chr(0xC2).chr(0xA0));
            // Combined trim?
            // trim($data, "\x00..\x20chr(0xC2).chr(0xA0)");
        } else {
            return false;
        }
    }

    /**
     * Validate URL.
     * 
     * Only accepts http:// protocol and ASCII characters. Other protocols and internationalised
     * domain names will fail validation (limitation of filter).
     *
     * @param string $url
     * @return bool true if valid URL otherwise false
     */
    public static function isUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED)) {
            if (mb_substr($url, 0, 7, 'UTF-8') == 'http://' || mb_substr($url, 0, 8, 'UTF-8') == 'https://') {
                return true;
            }
        }
        return false;
    }

    /**
     * Test if input is an array.
     *
     * @param array $array
     * @return bool true if valid array otherwise false
     */
    public static function isArray($array)
    {
        return is_array($array);
    }

    /**
     * Test if input is an object.
     * 
     * @param object $object
     * @return bool true if valid object otherwise false
     */
    public static function isObject($object)
    {
        return is_object($object);
    }

    /**
     * Tests if the input is null (ie set but without an assigned value) or not.
     * 
     * @param mixed $null
     * @return bool true if input is null otherwise false
     */
    public static function isNull($null)
    {
        return is_null($null);
    }

    /**
     * Tests if input is a resource.
     * 
     * @param resource $resource
     * @return bool true if valid resource otherwise false
     */
    public static function isResource($resource)
    {
        return is_resource($resource);
    }

}
