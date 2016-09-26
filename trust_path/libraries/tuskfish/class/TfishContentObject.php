<?php

/**
* Tuskfish content object class
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

/**
 * How the TfishContentObject works
 * 
 * There is only one type of content object in Tuskfish; it uses standard Dublin Core metadata
 * fields plus a few more that are common to most content objects. Why? If you look at most common
 * content types - articles, photos, downloads etc. - you will see that for the most part they all
 * use the same fields. For example, everything has a title, everything has a description, 
 * everything has an author, everything has a hit counter.
 * 
 * Traditionally, most CMS create a separate database table for every type of content with
 * duplicate column names. And it works just fine until you want to publish a single content stream
 * containing different kinds of content objects. Then, suddenly, your queries are full of complex
 * joins and other rubbish and it becomes very painful to work with.
 * 
 * By using a single table for content objects with common field names our queries become very
 * simple and much redundancy is avoided. Of course, some types of content might not require a few
 * particular properties; and so subclassed content types simply unset() any properties that they 
 * don't need in their constructor.
 * 
 * Too easy!
 */

class TfishContentObject extends TfishAncestralObject
{
	/**
	 * Generic constructor
	 */
	function __construct()
	{
		parent::__construct();
		
		/**
		 * Whitelist of official properties and datatypes.
		 */
		$this->__properties['id'] = 'int'; // Auto-increment, set by database.
		$this->__properties['type'] = 'alpha'; // Content object type eg. TfishArticle, TfishPodcast etc. [ALPHA]
		$this->__properties['title'] = 'string'; // The headline or name of this content.
		$this->__properties['teaser'] = 'html'; // A short (one paragraph) summary or abstract for this content. [HTML]
		$this->__properties['description'] = 'html'; // The full article or description of the content. [HTML]
		$this->__properties['media'] = 'string'; // An associated download/audio/video file. [FILEPATH OR URL]
		$this->__properties['format'] = 'string'; // Mimetype
		$this->__properties['file_size'] = 'int'; // Specify in bytes.
		$this->__properties['creator'] = 'string'; // Author.
		$this->__properties['image'] = 'string'; // An associated image file, eg. a screenshot a good way to handle it. [FILEPATH OR URL]
		$this->__properties['caption'] = 'string'; // Caption of the image file.
		$this->__properties['date'] = 'string'; // Date of publication expressed as a string.
		$this->__properties['parent'] = 'int'; // A source work or collection of which this content is part.
		$this->__properties['language'] = 'string'; // English (future proofing).
		$this->__properties['rights'] = 'int'; // Intellectual property rights scheme or license under which the work is distributed.
		$this->__properties['publisher'] = 'string'; // The entity responsible for distributing this work.
		$this->__properties['tags'] = 'array'; // Tag IDs associated with this object; not persistent (stored as taglinks in taglinks table).
		$this->__properties['online'] = 'int'; // Toggle object on or offline.
		$this->__properties['submission_time'] = 'int'; // Timestamp representing submission time.
		$this->__properties['counter'] = 'int'; // Number of times this content was viewed or downloaded.
		$this->__properties['meta_title'] = 'string'; // Set a custom page title for this content.
		$this->__properties['meta_description'] = 'string'; // Set a custom page meta description for this content.
		$this->__properties['seo'] = 'string'; // SEO-friendly string; it will be appended to the URL for this content.
		$this->__properties['handler'] = 'alpha'; // Handler for this object.
		$this->__properties['template'] = 'alnum'; // The template that should be used to display this object.
		$this->__properties['module'] = 'string'; // The module that handles this content type

		/**
		 * Set the permitted properties of this object.
		 */
		foreach ($this->__properties as $key => $value) {
			$this->__data[$key] = '';
		}
		
		/**
		 * Set default values of permitted properties.
		 */
		$this->__data['type'] = get_class($this);
		$this->__data['template'] = 'default';
		$this->__data['handler'] = $this->__data['type'] . 'Handler';
		$this->__data['rights'] = 1; // Change to be from preferences
		$this->__data['online'] = 1;
		$this->__data['counter'] = 0;
		$this->__data['tags'] = array();
	}
	
	/**
	 * Escapes object properties for output to browser (except for teaser and description) and
	 * formats it as human readable (where necessary).
	 * 
	 * Use this method to retrieve object properties when you want to send them to the browser.
	 * They will be automatically escaped with htmlspecialchars to mitigate cross-site scripting
	 * attacks. Note that the method specifically excludes the teaser and description fields, 
	 * which are returned unescaped; these are dedicated HTML fields that have been input-validated
	 * with the HTMLPurifier library, and so *should* be safe.
	 * 
	 * @param string $property
	 * @return string
	 */	
	public function escape($property) {
		if (isset($this->__data[$property])) {
			switch($property) {
				case "description":
				case "teaser":
					return $this->__data[$property];
				break;
			
				case "submission_time":
					$date = date('j F Y', $this->__data[$property]);
					return htmlspecialchars($date, ENT_QUOTES, 'UTF-8');
				break;
			
				case "tags":
					$tags = array();
					foreach ($this->__data[$property] as $value) {
						$tags[] = (int)$value;
						unset($value);
					}
					return $tags;
				break;
			
				default:
					return htmlspecialchars($this->__data[$property], ENT_QUOTES, 'UTF-8');
				break;
			}
		} else {
			return null;
		}
	}
	
	/**
	 * Generates a URL to access this object in single view mode, either relative to home page or
	 * to the subclass-specific page.
	 * 
	 * @return string
	 */
	public function getURL($use_subclass_page = false)
	{
		$url = TFISH_URL;
		if (!$use_subclass_page) {
			$url .= self::escape($this->module) . 'php';
		}
		$url .= '?id=' . (int)$this->id;
		if ($this->seo) {
			$url .= '&amp;title=' . self::escape('seo');
		}
		return $url;
	}
	
	public function getEditLink($urlOnly = false)
	{	
	}
	
	public function getDeleteLink($urlOnly = false)
	{	
	}
	
	/**
	 * Populates the properties of the object from external (untrusted) data source.
	 * 
	 * Note that the supplied data is internally validated by __set().
	 * 
	 * @param array $dirty_input usually raw form $_REQUEST data.
	 */
	public function loadProperties($dirty_input)
	{
		if (!TfishFilter::isArray($dirty_input)) {
			trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
		}		
		
		$property_whitelist = $this->getPropertyWhitelist();
		foreach ($property_whitelist as $key => $type) {
			if (array_key_exists($key, $dirty_input)) {
				$this->__set($key, $dirty_input[$key]);
			}
			unset($key, $type);
		}
		
		// Handle image and media file upload.
		// If this is associated with an UPDATE then check if file names are different.
		if (!empty($_FILES['image']['name']) || !empty($_FILES['media']['name'])) {
			$type_list = TfishContentHandler::getTypes();
			$clean_type = array_key_exists($dirty_input['type'], $type_list) ? TfishFilter::trimString($dirty_input['type']) : false;
		}
		
		if (array_key_exists('image', $property_whitelist) && !empty($_FILES['image']['name'])) {
			$filename = TfishFilter::trimString($_FILES['image']['name']);
			$clean_filename = TfishFileHandler::uploadFile($filename, 'image', $clean_type);
			if ($clean_filename) {
				$this->__set('image', $clean_filename);
				$this->__set('format', pathinfo($clean_filename, PATHINFO_EXTENSION));
				$this->__set('file_size', $_FILES['image']['size']);
			}
		}

		if (array_key_exists('media', $property_whitelist) && !empty($_FILES['media']['name'])) {
			$filename = TfishFilter::trimString($_FILES['media']['name']);
			$clean_filename = TfishFileHandler::uploadFile($filename, 'media', $clean_type);
			if ($clean_filename) {
				$this->__set('media', $clean_filename);
				$this->__set('format', pathinfo($clean_filename, PATHINFO_EXTENSION));
				$this->__set('file_size', $_FILES['media']['size']);
			}
		}
	}
	
	public function setErrors()
	{	
	}
	
	/**
	 * Returns an array of base object properties that are not used by this subclass.
	 * 
	 * This list is also used in update calls to the database to ensure that unused columns are
	 * cleared and reset with default values.
	 * 
	 * @return array
	 */
	public function zeroedProperties()
	{
		return array();
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
			return null;
		}
	}
	
	/**
	 * Validate and set an existing object property according to type specified in constructor.
	 * 
	 * For more fine-grained control each property could be dealt with individually.
	 * 
	 * @param mixed $property
	 * @param mixed $value
	 */
	public function __set($property, $value)
	{
		if (isset($this->__data[$property])) {
			
			// Validate $value against expected data type and business rules
			$type = $this->__properties[$property];
			switch ($type) {
				
				case "alpha":
					$value = TfishFilter::trimString($value);
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
				
				// Only array field is tags, contents must all be integers.
				case "array":
					if (TfishFilter::isArray($value)) {
						$clean_tags = array();
						foreach ($value as $val) {
							$clean_val = (int)$val;
							if (TfishFilter::isInt($clean_val, 1)) {
								$clean_tags[] = $clean_val;
							} else {
								trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
							}
							unset($clean_val);
						}
						$this->__data[$property] = $clean_tags;
					} else {
						trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
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
						case "online":
							if (TfishFilter::isInt($value, 0, 1)) {
								$this->__data[$property] = (int)$value;
							} else {
								trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
							}
						break;
						
						// Minimum value 0.
						case "counter":
						case "file_size":
						case "id":
						case "parent":
							if (TfishFilter::isInt($value, 0)) {
								$this->__data[$property] = (int)$value;
							} else {
								trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
							}
						break;
					
						// Minimum value 1.
						case "rights":
						case "submission_time":
							if (TfishFilter::isInt($value, 1)) {
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
					if ($property == "language") {
						$language_whitelist = TfishContentHandler::getLanguages();
						if (!array_key_exists($value, $language_whitelist)) {
							trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
						}
					}
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
		} else {
			/**
			 * If try to set an property that was explicitly unset by a subclass, do nothing.
			 * This does happen when trying to pull rows directly into content subclass objects
			 * using PDO (basically the constructor unsets uneeded fields, then PDO tries to set
			 * them because each row has the full set of columns). Since this functionality is
			 * extremely convenient, we can live without throwing errors on this case. 
			 *  
			 * If try to set some other random property (which probably means a typo has been made)
			 * throw an error to help catch bugs.
			 */
			if(!in_array($property, $this->zeroedProperties())) {
				trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_WARNING);
			}
		}
	}
}