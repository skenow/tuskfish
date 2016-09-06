<?php

/**
* Tuskfish site preference class
* 
* Holds site configuration data
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishPreference extends TfishAncestralObject
{	
	/**
	 * Whitelist of official properties and datatypes.
	 */
	
	/**
	 * Generic constructor. Reads preferences from database and assigns whitelisted properties
	 */
	function __construct()
	{
		/**
		 * Set the permitted properties of this object.
		 */
		$this->__properties['admin_pagination'] = 'int';
		$this->__properties['allowed_mimetypes'] = 'string';
		$this->__properties['close_site'] = 'int';
		$this->__properties['date_format'] = 'string';
		$this->__properties['default_language'] = 'alpha';
		$this->__properties['min_search_length'] = 'int';
		$this->__properties['search_pagination'] = 'int';
		$this->__properties['server_timezone'] = 'string';
		$this->__properties['site_timezone'] = 'string';
		$this->__properties['site_name'] = 'string';
		$this->__properties['session_domain'] = 'string';
		$this->__properties['session_name'] = 'alnumunder';
		$this->__properties['session_timeout'] = 'int';
		$this->__properties['site_email'] = 'email';
		$this->__properties['user_pagination'] = 'int';
		
		// Instantiate whitelisted fields in the protected $__data property.
		foreach ($this->__properties as $key => $value) {
			$this->__data[$key] = '';
		}
		
		$preferences = self::readPreferences();
		foreach ($preferences as $key => $value) {
			if (isset($this->__data[$key])) {
				if ($this->__properties[$key] == 'int') {
					$this->__set($key, (int)$value);
				} else {
					$this->__set($key, $value);
				}
			}
			unset($key, $value);
		}
	}
	
	/**
	 * Read the site preferences from the database and populate the preference object
	 */
	public static function readPreferences()
	{
		$preferences = array();
		$result = TfishDatabase::select('preference');
		try {
			while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$preferences[$row['title']] = $row['value'];
			}
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}
		return $preferences;
	}
	
	/**
	 * Update the preference object using $_REQUEST data
	 * 
	 * @param array $request
	 */
	public function updatePreferences($dirty_input)
	{
		if (!TfishFilter::isArray($dirty_input)) {
			trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
		}	
		
		// Obtain a whitelist of permitted fields.
		$whitelist = $this->getPropertyWhitelist();
		
		// Iterate through the whitelist validating supplied parameters.
		foreach ($whitelist as $key => $type) {
			if (array_key_exists($key, $dirty_input)) {
				$this->__set($key, $dirty_input[$key]);
			}
			unset($key, $type);
		}
	}
	
	/**
	 * Save updated preferences to the database
	 */
	private static function writePreferences()
	{
		return TfishDatabase::update('preference', $this->__data);
	}
	
	/**
	 * Access an existing object property
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property)
	{
		if (isset($this->__data[$property])) {
			return $this->__data[$property];
		} else {
			return false;
			// Should this be a fatal error?
		}
	}
	
	/**
	 * Set an existing object property
	 * 
	 * @param mixed $property
	 * @param mixed $value
	 */
	public function __set($property, $value)
	{
		if (isset($this->__data[$property])) {
			
			// Validate $value against expected data type and business rules.
			$type = $this->__properties[$property];
			switch ($type) {
				case "alpha":
					$value = TfishFilter::trimString($value);
					if ($property == "language") {
						$language_whitelist = TfishContentHandler::getLanguages();
						if (!array_key_exists($value, $language_whitelist)) {
							trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
						}
					}
					if (TfishFilter::isAlpha($value)) {
						$this->__data[$property] = $value;
					} else {
						trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
					}
				break;
			
				case "alnum":
					$value = TfishFilter::trimString($value);
					if (TfishFilter::isAlnum($value)) {
						$this->__data[$property] = $value;
					} else {
						trigger_error(TFISH_ERROR_NOT_ALNUM, E_USER_ERROR);
					}
				break;
			
				case "alnumunder":
					$value = TfishFilter::trimString($value);
					if (TfishFilter::isAlnumUnderscore($value)) {
						$this->__data[$property] = $value;
					} else {
						trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
					}
				break;
			
				case "bool":
					if (TfishFilter::isBool($value)) {
						$this->__data[$property] = (bool)$value;
					} else {
						trigger_error(TFISH_ERROR_NOT_BOOL, E_USER_ERROR);
					}
				break;
			
				case "email":
					$value = TfishFilter::trimString($value);
					if (TfishFilter::isEmail($value)) {
						$this->__data[$property] = $value;
					} else {
						trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
					}
				break;
			
				case "digit":
					$value = TfishFilter::trimString($value);
					if (TfishFilter::isDigit($value)) {
						$this->__data[$property] = $value;
					} else {
						trigger_error(TFISH_ERROR_NOT_DIGIT, E_USER_ERROR);
					}
				break;
			
				case "float":
					if (TfishFilter::isFloat($value)) {
						$this->__data[$property] = $value;
					} else {
						trigger_error(TFISH_ERROR_NOT_FLOAT, E_USER_ERROR);
					}
				break;
			
				case "html":
					$value = TfishFilter::trimString($value);
					$this->__data[$property] = (string)TfishFilter::filterHtml($value);
				break;
			
				case "int":
					$value = (int)$value;					
					switch ($property) {
						
						// 0 or 1.
						case "close_site":
							if (TfishFilter::isInt($value, 0, 1)) {
								$this->__data[$property] = (int)$value;
							} else {
								trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
							}
						break;
						
						// Minimum value 0.
						case "search_pagination":
						case "session_timeout":
							if (TfishFilter::isInt($value, 0)) {
								$this->__data[$property] = (int)$value;
							} else {
								trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
							}
						break;
						
						// Minimum value 1.
						case "admin_pagination":
						case "user_pagination":
							if (TfishFilter::isInt($value, 1)) {
								$this->__data[$property] = (int)$value;
							} else {
								trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
							}
						break;
					
						// Minimum value 3.
						case "min_search_length":
							if (TfishFilter::isInt($value, 3)) {
								$this->__data[$property] = (int)$value;
							} else {
								trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
							}
						break;
					}
				break;
				
				case "ip":
					$value = TfishFilter::trimString($value);
					if ($value == "" || TfishFilter::isIp($value)) {
						$this->__data[$property] = $value;
					} else {
						trigger_error(TFISH_ERROR_NOT_IP, E_USER_ERROR);
					}
				break;
			
				case "string":
					$this->__data[$property] = TfishFilter::trimString($value);
				break;
			
				case "url":
					$value = TfishFilter::trimString($value);
					if ($value == "" || TfishFilter::isUrl($value)) {
						$this->__data[$property] = $value;
					} else {
						trigger_error(TFISH_ERROR_NOT_URL, E_USER_ERROR);
					}
				break;
			}
			return true;
		} else {
			trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
		}
	}
	
	public function escape($property) {
		if (isset($this->__data[$property])) {
			switch($property) {			
				default:
					return htmlspecialchars($this->__data[$property], ENT_QUOTES, 'UTF-8');
				break;
			}
		} else {
			return null;
		}
	}
	
	/**
	 * Returns an array of known mimetypes
	 * Based on ImpressCMS function (attribute copyright)
	 *
	 * @return array
	 */
	public static function knownMimeTypes()
	{
		return array(
		     "hqx"		=> "application/mac-binhex40",
		     "doc"		=> "application/msword",
		     "dot"		=> "application/msword",
		     "bin"		=> "application/octet-stream",
		     "lha"		=> "application/octet-stream",
		     "lzh"		=> "application/octet-stream",
		     "exe"		=> "application/octet-stream",
		     "class"	=> "application/octet-stream",
		     "so"		=> "application/octet-stream",
		     "dll"		=> "application/octet-stream",
		     "pdf"		=> "application/pdf",
		     "ai"		=> "application/postscript",
		     "eps"		=> "application/postscript",
		     "ps"		=> "application/postscript",
		     "smi"		=> "application/smil",
		     "smil"		=> "application/smil",
		     "wbxml"	=> "application/vnd.wap.wbxml",
		     "wmlc"		=> "application/vnd.wap.wmlc",
		     "wmlsc"	=> "application/vnd.wap.wmlscriptc",
		     "xla"		=> "application/vnd.ms-excel",
		     "xls"		=> "application/vnd.ms-excel",
		     "xlt"		=> "application/vnd.ms-excel",
		     "ppt"		=> "application/vnd.ms-powerpoint",
		     "csh"		=> "application/x-csh",
		     "dcr"		=> "application/x-director",
		     "dir"		=> "application/x-director",
		     "dxr"		=> "application/x-director",
		     "spl"		=> "application/x-futuresplash",
		     "gtar"		=> "application/x-gtar",
		     "php"		=> "application/x-httpd-php",
		     "php3"		=> "application/x-httpd-php",
		     "php4"		=> "application/x-httpd-php",
		     "php5"		=> "application/x-httpd-php",
		     "phtml"	=> "application/x-httpd-php",
		     "js"		=> "application/x-javascript",
		     "sh"		=> "application/x-sh",
		     "swf"		=> "application/x-shockwave-flash",
		     "sit"		=> "application/x-stuffit",
		     "tar"		=> "application/x-tar",
		     "tcl"		=> "application/x-tcl",
		     "xhtml"	=> "application/xhtml+xml",
		     "xht"		=> "application/xhtml+xml",
		     "xhtml"	=> "application/xml",
		     "ent"		=> "application/xml-external-parsed-entity",
		     "dtd"		=> "application/xml-dtd",
		     "mod"		=> "application/xml-dtd",
		     "gz"		=> "application/x-gzip",
		     "zip"		=> "application/zip",
		     "au"		=> "audio/basic",
		     "snd"		=> "audio/basic",
		     "mid"		=> "audio/midi",
		     "midi"		=> "audio/midi",
		     "kar"		=> "audio/midi",
		     "mp1"		=> "audio/mpeg",
		     "mp2"		=> "audio/mpeg",
		     "mp3"		=> "audio/mpeg",
		     "aif"		=> "audio/x-aiff",
		     "aiff"		=> "audio/x-aiff",
		     "m3u"		=> "audio/x-mpegurl",
		     "ram"		=> "audio/x-pn-realaudio",
		     "rm"		=> "audio/x-pn-realaudio",
		     "rpm"		=> "audio/x-pn-realaudio-plugin",
		     "ra"		=> "audio/x-realaudio",
		     "wav"		=> "audio/x-wav",
		     "bmp"		=> "image/bmp",
		     "gif"		=> "image/gif",
		     "jpeg"		=> "image/jpeg",
		     "jpg"		=> "image/jpeg",
		     "jpe"		=> "image/jpeg",
		     "png"		=> "image/png",
		     "tiff"		=> "image/tiff",
		     "tif"		=> "image/tif",
		     "wbmp"		=> "image/vnd.wap.wbmp",
		     "pnm"		=> "image/x-portable-anymap",
		     "pbm"		=> "image/x-portable-bitmap",
		     "pgm"		=> "image/x-portable-graymap",
		     "ppm"		=> "image/x-portable-pixmap",
		     "xbm"		=> "image/x-xbitmap",
		     "xpm"		=> "image/x-xpixmap",
			 "ics"		=> "text/calendar",
			 "ifb"		=> "text/calendar",
		     "css"		=> "text/css",
		     "html"		=> "text/html",
		     "htm"		=> "text/html",
		     "asc"		=> "text/plain",
		     "txt"		=> "text/plain",
		     "rtf"		=> "text/rtf",
		     "sgml"		=> "text/x-sgml",
		     "sgm"		=> "text/x-sgml",
		     "tsv"		=> "text/tab-seperated-values",
		     "wml"		=> "text/vnd.wap.wml",
		     "wmls"		=> "text/vnd.wap.wmlscript",
		     "xsl"		=> "text/xml",
			 "mp4"		=> "video/mp4",
		     "mpeg"		=> "video/mpeg",
		     "mpg"		=> "video/mpeg",
		     "mpe"		=> "video/mpeg",
		     "qt"		=> "video/quicktime",
		     "mov"		=> "video/quicktime",
		     "avi"		=> "video/x-msvideo",
		);
	}
}
