<?php

/**
* Tuskfish data filter class
* 
* NOTE WELL!!!
* ===============
* The methods in this class validate TYPE COMPLIANCE ONLY. They DO NOT PROVIDE DATABASE SAFETY. 
* They are intended for EXCLUSIVE use with PREPARED STATEMENTS and BOUND VALUES to mitigate SQL
* injection.

* 1. Pass ALL STRING type data through the trimString() function first to check for UTF-8 encoding 
* and basic whitespace & control character removal. Note that this function always returns a string,
* so DO NOT USE IT ON NON-STRINGS. 
* 
* 2. Use the relevant type and pattern-specific methods to validate that other data types meet your
* expectations.
* 
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishFilter
{
	/**
	 * Sanitises untrusted input data, removing unwanted tags and junk. Forcibly casts to expected
	 * datatype; as html form input always arrives as strings.
	 * 
	 * Use this function to screen user-side input. If data passes validation it will be handed
	 * back with minimalist sanitisation (control characters and trailing whitespace removed); if
	 * validation fails it will return false, except for boolean validation which will return null
	 * on failure.
	 * 
	 * Note that the "int" case does not provide range checks. If you want a specific range then
	 * check the range with isInt() manually.
	 *
	 * @param array $dirty_vars
	 * @param array $allowed_vars
	 * @return mixed
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
						$dirty_vars[$key] = (bool)$dirty_vars[$key];
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
						$dirty_vars[$key] = (float)$dirty_vars[$key];
						$clean_vars[$key] = self::isFloat($dirty_vars[$key]) ? $dirty_vars[$key] : false;
						break;
					case "int":
						$dirty_vars[$key] = (int)$dirty_vars[$key];
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
	 * Checks if the character encoding of text is UTF-8.
	 * 
	 * All strings received from external sources must be passed through this function, particularly
	 * prior to storage in the database.
	 * 
	 * @param string $dirty_text
	 * @return bool
	 */
	public static function isUtf8($dirty_string)
	{
		return mb_check_encoding($dirty_string, 'UTF-8');
	}
	
		/**
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
	 * Check that a string is comprised soley of alphabetical characters.
	 *
	 * @param string $dirty_alpha
	 * @return mixed
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
	 * @param string $dirty_alnum
	 * @return mixed
	 */
	public static function isAlnum($alnum)
	{
		if (mb_strlen($alnum, 'UTF-8') > 0) {
			return preg_match('/[^a-z0-9]/i', $alnum) ? false: true;
		} else {
			return false;
		}
	}
	
	/**
	 * Check that a string is comprised solely of alphanumeric characters and underscores.
	 * 
	 * @param type $alnumUnder
	 * @return boolean
	 */
	public static function isAlnumUnderscore($alnumUnderscore)
	{
		if (mb_strlen($alnumUnderscore, 'UTF-8') > 0) {
			return preg_match('/[^a-z0-9_]/i', $alnumUnderscore) ? false: true;
		} else {
			return false;
		}
	}
	
	/**
	 * Validate boolean input.
	 *
	 * @param mixed $bool
	 * @return bool 
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
	 * @param string $dirty_digit
	 * @return mixed
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
	 * Sanitise and validate email address.
	 * 
	 * Note that single quotes ' are a valid character in email addresses, so the output of this 
	 * filter is not database safe in of itself.
	 *
	 * @param string $dirty_email
	 * @return string
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
	 * @param float $dirty_float
	 * @return float
	 */
	public static function isFloat($float)
	{
		return is_float($float);
	}
	
	/**
	 * Sanitise and validate integer, optionally include range check.
	 * 
	 * @param int $dirty_int
	 * @param int $min
	 * @param int $max
	 * @return int
	 */
	//1,0,1
	public static function isInt($int, $min = false, $max = false)
	{
		$clean_int = is_int($int) ? (int)$int : false;
		$clean_min = is_int($min) ? (int)$min : false;
		$clean_max = is_int($max) ? (int)$max : false;
		
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
	 * @param string $dirty_ip
	 * @param int $version
	 * @return string
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
	 * Strip trailing whitespace and certain control characters. Casts to string. NOT database safe.
	 * 
	 * CAUTION! THIS METHOD DOES NOT RETURN DATABASE SAFE DATA!!!
	 * 
	 * All this method does is to remove trailing whitespace and control characters (ASCII < 32).
	 * The data returned by this function REQUIRES escaping at the point of use (this is Tuskfish 
	 * policy; since you don't know the context in which data will be used in the future you should
	 * apply relevant escaping when you actually need to use it). For example:
	 * 
	 * 1. To prepare data for use in database queries such as insertion in the database, use it as 
	 * bound values/parameters in prepared statements (PDO). This will mitigate SQL injection
	 * attacks.
	 * 
	 * 2. To prepare data for display in a webpage (including when you retrieve it from the 
	 * database) pass it through escape(). This will mitigate XSS attacks.
	 * 
	 * This function is used to treat ALL TfishContentObject fields that are of STRING type. Do not
	 * apply it to non-string types (int, float, bool, object, resource, null, array, etc).
	 * 
	 * @param string $dirty_text
	 * @return string
	 */
	public static function trimString($dirty_text)
	{
		if (self::isUtf8($dirty_text)) {
			// Trims all control characters plus space (ASCII 0-32 inclusive)
			return (string)trim($dirty_text, "\x00..\x20");
			// Trim non-breaking space in UTF-8
			// trim($data, chr(0xC2).chr(0xA0));
			// Combined trim?
			// trim($data, "\x00..\x20chr(0xC2).chr(0xA0)");
		} else {
			return false;
		}
	}
	
	/**
	 * Validate (and to some extent, "sanitise") HTML input to conform with whitelisted tags.
	 * 
	 * NOTE: This method is ONLY AVAILABLE IN THE ADMIN SECTION of Tuskfish. This is because the
	 * HTMLPurifier library is only included there via tfish_admin_header.php (as it is only used
	 * to validate HTML input in the teaser and description fields of content objects). Not making
	 * the library available in public-facing areas of the site (which do not allow data entry) is
	 * a deliberate design decision made in the interests of performance, as HTMLPurifier is big,
	 * slow and has many includes.
	 *
	 * @param string $dirty_text
	 * @param array $configs
	 * @return string Validated HTML content
	 */
	public static function filterHtml($dirty_html, $config = false)
	{
		if (self::isUtf8($dirty_html)) {
			if (class_exists('HTMLPurifier')) {
				$html_purifier = new HTMLPurifier($config);
				$clean_html = (string)$html_purifier->purify($dirty_html);
				return $clean_html;
			} else {
				return false;
			}	
		} else {
			return false;
		}
	}
	
	/**
	 * Sanitise and validate url. Only accepts http:// protocol and ASCII characters.
	 * Other protocols and internationalised domain names (limitation of filter) will fail validation.
	 *
	 * @param string $dirty_url
	 * @return mixed
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
	 * Tests if input is an array.
	 *
	 * @param array $array
	 * @return bool
	 */
	public static function isArray($array)
	{
		return is_array($array);
	}
	
	/**
	 * Tests if input is an object.
	 * 
	 * @param object $dirty_object
	 * @return bool
	 */
	public static function isObject($object)
	{
		return is_object($object);
	}
	
	/**
	 * Tests if the input is null (ie set but without an assigned value) or not.
	 * 
	 * @param mixed $dirty_null
	 * @return bool
	 */
	public static function isNull($null)
	{
		return is_null($null);
	}
	
	/**
	 * Tests if input is a resource.
	 * 
	 * @param resource $dirty_resource
	 * @return bool
	 */
	public static function isResource($resource)
	{
		return is_resource($resource);
	}
}