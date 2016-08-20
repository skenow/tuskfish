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
class TfishPreference
{	
	// Permitted properties
	protected $__data = array(
		'admin_pagination' => '',
		'allowed_mimetypes' => array(),
		'close_site' => '',
		'date_format' => '',
		'default_language' => '',
		'min_search_length' => '',
		'search_pagination' => '',	
		'server_timezone' => '',
		'site_name' => '',
		'session_domain' => '',
		'session_name' => '',
		'session_timeout' => '',
		'site_email' => '',
		'site_timezone' => '',
	);
	
	/**
	 * Generic constructor. Reads preferences from database and assigns whitelisted properties
	 */
	function __construct()
	{		
		$preferences = self::readPreferences();
		foreach ($preferences as $key => $value) {
			if (isset($this->__data[$key])) {
				$this->__data[$key] = $value;
			}
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
			// Check that values match expected types. Values that fail validation (false) are not set
			switch ($property) {
				case "site_name":
				case "server_timezone":
				case "site_timezone":
				case "session_name":
				case "session_domain":
					$value = TfishFilter::filter_text($value);
					$this->__data[$property] = $value;
					break;
					
				case "site_email":
					$value = TfishFilter::trimString($email);
					if (TfishFilter::isEmail($value)) {
						$this->__data[$property] = $value;
					} else {
						trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
					}
					break;
				
				case "session_timeout":
				case "min_search_length":
				case "search_pagination":
					if (TfishFilter::isInt($value, 0)) {
						$this->__data[$property] = (int)$value;
					} else {
						trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
					}
					break;
				
				case "close_site":
					if (TfishFilter::isInt($value, 0, 1)) {
						$this->__data[$property] = (int)$value;
					} else {
						trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
					}
					break;
				
				case "allowed_mimetypes":
					if (TfishFilter::isArray($value)) {
						$clean_mimetypes = array();
						foreach ($value as $key => $val) {
							if (array_key_exists($key, self::knownMimeTypes()) && in_array($val, self::knownMimeTypes())) {
								$clean_mimetypes[$key] = $val;
							} else {
								trigger_error(TFISH_ERROR_UNKNOWN_MIMETYPE, E_USER_ERROR);
							}
						}
						$this->__data[$property] = $clean_mimetypes;
					} else {
						trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
					}
					break;
			}
			return self::writePreferences();
		} else {
			trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
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
