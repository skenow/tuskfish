<?php

/**
* Tuskfish parent content object class, represents a single content object.
*
* There is only one 'archtype' of content object in Tuskfish; it uses standard Dublin Core metadata
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
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishContentObject extends TfishAncestralObject
{
	function __construct()
	{
		parent::__construct();
		
		/**
		 * Whitelist of official properties and datatypes.
		 */
		$this->__properties['id'] = 'int'; // Auto-increment, set by database.
		$this->__properties['type'] = 'alpha'; // Content object type eg. TfishArticle etc. [ALPHA]
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
	 * Escapes object properties for output to browser and formats it as human readable (where necessary).
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
				case "date": // Stored in format yyyy-mm-dd
					$date = new DateTime($this->__data[$property]);
					return $date->format('j F Y');
				break;
			
				case "file_size": // Convert to human readable.
					$bytes = $this->__data[$property];
					$unit = $val = '';
					if ($bytes == 0 || $bytes < 1024) {
						$unit = ' bytes';
						$val = $bytes;
					} elseif ($bytes > 1023 && $bytes < 1048576) {
						$unit = ' KB';
						$val = ($bytes / 1024);
					} elseif ($bytes > 1048575 && $bytes < 1073741824) {
						$unit = ' MB';
						$val = ($bytes / 1048576);
					} else {
						$unit = ' GB';
						$val = ($bytes / 1073741824);
					}
					$val = round($val, 2);
					return $val . ' ' . $unit;
					
				break;
				
				case "description":
				case "teaser":
					//return (string)TfishFilter::filterHtml($this->__data[$property]); // Output filtering
					return $this->__data[$property]; // Disable output filtering (only do this if enable input filtering of these fields in __set()).
				break;
			
				case "rights":
					$rights = TfishContentHandler::getRights();
					return $rights[$this->__data[$property]];
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
	 * Resizes and caches image property and returns a URL to the copy.
	 * 
	 * Allows arbitrary sized thumbnails to be produced from the object's image property. These are
	 * saved in the cache for future lookups. Image proportions are always preserved, so if both
	 * width and height are specified, the larger dimension will take precedence for resizing and
	 * the other will be ignored.
	 * 
	 * Usually, you want to produce an image of a specific width or (less commonly) height to meet
	 * a template/presentation requirement.
	 * 
	 * @param int $width of the cached image output
	 * @param int $height of the cached image output
	 * @return string $url to the cached image
	 */
	public function getCachedImage($width = 0, $height = 0)
	{
		// Validate parameters; and at least one must be set.
		$clean_width = TfishFilter::isInt($width, 1) ? (int)$width : 0;
		$clean_height = TfishFilter::isInt($height, 1) ? (int)$height : 0;
		if (!$clean_width && !$clean_height) {
			return false;
		}
		
		// Check if this object actually has an associated image, and that it is readable.
		if (!$this->image || !is_readable(TFISH_IMAGE_PATH . $this->image)) {
			return false;
		}
		
		// Check if a cached copy of the requested dimensions already exists in the cache and return URL.
		// CONVENTION: Thumbnail name should follow the pattern: image_file_name . '-' . $width . 'x' . $height
		$filename = pathinfo($this->image, PATHINFO_FILENAME);
		$extension = '.' . pathinfo($this->image, PATHINFO_EXTENSION);
		$cached_path = TFISH_CACHE_PATH . $filename . '-';
		$cached_url = TFISH_CACHE_URL . $filename . '-';
		$original_path = TFISH_IMAGE_PATH . $filename . $extension;
		if ($clean_width > $clean_height) {
			$cached_path .= $clean_width . 'w' . $extension;
			$cached_url .=  $clean_width . 'w' . $extension;
		} else {
			$cached_path .= $clean_height . 'h' . $extension;
			$cached_url .=  $clean_height . 'h' . $extension;
		}
		
		// Security check - is the cached_path actually pointing at the cache directory? Because
		// if it isn't, then we don't want to cooperate by returning anything.
		if (is_readable($cached_path)) {
			return $cached_url;
		} else {
			
			// Get the size. Note that:
			// $properties['mime'] holds the mimetype, eg. 'image/jpeg'.
			// $properties[0] = width, [1] = height, [2] = width = "x" height = "y" which is useful for outputting size attribute.
			$properties = getimagesize($original_path);
			if (!$properties) {
				return false;
			}

			/**
			 * Resizing with GD installed.
			 */
			
			// In order to preserve proportions, need to calculate the size of the other dimension.
			if ($clean_width > $clean_height) {
				$destination_width = $clean_width;
				$destination_height = (int)(($clean_width/$properties[0]) * $properties[1]);
			} else {
				$destination_width = (int)(($clean_height/$properties[1]) * $properties[0]);
				$destination_height = $clean_height;
			}

			// Get a reference to a new image resource.
			$thumbnail = imagecreatetruecolor($destination_width, $destination_height); // Creates a blank (black) image RESOURCE of the specified size.
			
			// Different image types require different handling. JPEg and PNG support optional quality parameter (TODO: Create a preference).
			$result = false;
			switch($properties['mime']) {
				case "image/jpeg":
					$original = imagecreatefromjpeg($original_path);
					imagecopyresampled($thumbnail, $original, 0, 0, 0, 0, $destination_width, $destination_height, $properties[0], $properties[1]);
					$result = imagejpeg($thumbnail, $cached_path); // Optional third quality argument 0-99, higher is better quality.
				break;
			
				case "image/png":
				case "image/gif":
					if ($properties['mime'] == "image/gif") {
						$original = imagecreatefromgif($original_path);
					} else {
						$original = imagecreatefrompng($original_path);
					}
					
					/**
					 * Handle transparency The following code block (only) is a derivative of
					 * the PHP_image_resize project by Nimrod007, which is a fork of the
					 * smart_resize_image project by Maxim Chernyak. The source code is available
					 * from the link below, and it is distributed under the following license terms:
					 * 
					 * Copyright © 2008 Maxim Chernyak
					 * 
					 * Permission is hereby granted, free of charge, to any person obtaining a copy
					 * of this software and associated documentation files (the “Software”), to deal
					 * in the Software without restriction, including without limitation the rights
					 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
					 * copies of the Software, and to permit persons to whom the Software is
					 * furnished to do so, subject to the following conditions:
					 * 
					 * The above copyright notice and this permission notice shall be included in
					 * all copies or substantial portions of the Software.
					 * 
					 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
					 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
					 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
					 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
					 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
					 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
					 * THE SOFTWARE.
					 * 
					 * https://github.com/Nimrod007/PHP_image_resize 
					 */
					
					// Sets the transparent colour in the given image, using a colour identifier created with imagecolorallocate().
					$transparency = imagecolortransparent($original);
					$number_of_colours = imagecolorstotal($original);
					
					if ($transparency >= 0 && $transparency < $number_of_colours) {
						// Get the colours for an index.
						$transparent_colour = imagecolorsforindex($original, $transparency);
						// Allocate a colour for an image. The first call to imagecolorallocate() fills the background colour in palette-based images created using imagecreate().
						$transparency = imagecolorallocate($thumbnail, $transparent_colour['red'], $transparent_colour['green'], $transparent_colour['blue']);
						// Flood fill with the given colour starting at the given coordinate (0,0 is top left).
						imagefill($thumbnail, 0, 0, $transparency);
						// Define a colour as transparent.
						imagecolortransparent($thumbnail, $transparency);
					}
					
					// Bugfix from original: Changed next block to be an independent if, instead of
					// an elseif linked to previous block. Otherwise PNG transparency doesn't work.
					if ($properties['mime'] == "image/png") {
						// Set the blending mode for an image.
						imagealphablending($thumbnail, false);
						// Allocate a colour for an image ($image, $red, $green, $blue, $alpha).
						$colour = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
						// Flood fill again.
						imagefill($thumbnail, 0, 0, $colour);
						// Set the flag to save full alpha channel information (as opposed to single colour transparency) when saving png images.
						imagesavealpha($thumbnail, true);
					}
					
					/**
					 * End code derived from PHP_image_resize project.
					 */
					
					// Copy and resize part of an image with resampling.
					imagecopyresampled($thumbnail, $original, 0, 0, 0, 0, $destination_width, $destination_height, $properties[0], $properties[1]);
					
					// Output a useable png or gif from the image resource.
					if ($properties['mime'] == "image/gif") {
						$result = imagegif($thumbnail, $cached_path);
					} else {
						// Quality is controlled through an optional third argument (0-9, lower is better).
						$result = imagepng($thumbnail, $cached_path);
					}
				break;

				// Anything else, no can do.
				default:
				return false;
			}
			
			if ($result) {
				imagedestroy($thumbnail); // Free memory.
				return $cached_url; // Return the URL to the cached file.
			} else {
				return false;
			}
			
			return $cached_url;
		}
	}
	
	/**
	 * Generates a URL to access this object in single view mode, either relative to home page or
	 * to the subclass-specific page.
	 * 
	 * @param boolean $use_subclass_page
	 * @return string
	 */
	public function getURL($use_subclass_page = false)
	{
		$url = TFISH_URL;
		if ($use_subclass_page) {
			$url .= $this->module . '.php';
		}
		$url .= '?id=' . (int)$this->id;
		if ($this->seo) {
			$url .= '&amp;title=' . TfishFilter::encodeEscapeUrl($this->seo);
		}
		
		return $url;
	}
	
	/**
	 * Populates the properties of the object from external (untrusted) data source.
	 * 
	 * Note that the supplied data is internally validated by __set().
	 * 
	 * @param array $dirty_input usually raw form $_REQUEST data.
	 * @return void
	 */
	public function loadProperties($dirty_input)
	{
		if (!TfishFilter::isArray($dirty_input)) {
			trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
		}
		
		$delete_image = (isset($dirty_input['deleteImage']) && !empty($dirty_input['deleteImage']))
				? true : false;
		$delete_media = (isset($dirty_input['deleteMedia']) && !empty($dirty_input['deleteMedia']))
				? true : false;

		$property_whitelist = $this->getPropertyWhitelist();
		foreach ($property_whitelist as $key => $type) {
			if (array_key_exists($key, $dirty_input)) {
				$this->__set($key, $dirty_input[$key]);
			}
			unset($key, $type);
		}
		
		if (array_key_exists('date', $property_whitelist) && empty($dirty_input['date'])) {
			$this->__set('date', date(DATE_RSS, time()));
		}
		
		if (array_key_exists('image', $property_whitelist) && !empty($_FILES['image']['name'])) {
			$clean_filename = TfishFilter::trimString($_FILES['image']['name']);
			if ($clean_filename) {
				$this->__set('image', $clean_filename);
			}
		}

		if (array_key_exists('media', $property_whitelist) && !empty($_FILES['media']['name'])) {
			$clean_filename = TfishFilter::trimString($_FILES['media']['name']);
			if ($clean_filename) {
				$this->__set('media', $clean_filename);
				$this->__set('format', pathinfo($clean_filename, PATHINFO_EXTENSION));
				$this->__set('file_size', $_FILES['media']['size']);
			}
		}
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
	 * Set the value of an object property and will not allow non-whitelisted properties to be set.
	 * 
	 * Intercepts direct calls to set the value of an object property. This method is overriden by
	 * child classes to impose data type restrictions and range checks before allowing the property
	 * to be set. Tuskfish objects are designed not to trust other components; each conducts its
	 * own internal validation checks. 
	 * 
	 * @param string $property name
	 * @param return void
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
					$this->__data[$property] = (string)TfishFilter::filterHtml($value); // Enable input filtering with HTMLPurifier.
					//$this->__data[$property] = (string)TfishFilter::trimString($value); // Disable input filtering with HTMLPurifier (only do this if output filtering is enabled in escape()).
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
							if (TfishFilter::isInt($value, 0)) {
								$this->__data[$property] = (int)$value;
							} else {
								trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
							}
							
						break;
						
						// Parent ID must be different to content ID (cannot declare self as parent).
						case "parent":
							if (!TfishFilter::isInt($value, 0)) {
								trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
							}							
							if ($value == $this->__data['id'] && $value > 0) {
								trigger_error(TFISH_ERROR_CIRCULAR_PARENT_REFERENCE);
							} else {
								$this->__data[$property] = (int)$value;
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
					if ($property == "date") { // Ensure format complies with DATE_RSS
						$check_date = date_parse_from_format('Y-m-d', $value);
						if ($check_date == false || $check_date['warning_count'] > 0 || $check_date['error_count'] > 0) {
							// Bad date supplied, default to today.
							$this->__data[$property] = date(DATE_RSS, time());
							trigger_error(TFISH_ERROR_BAD_DATE_DEFAULTING_TO_TODAY, E_USER_WARNING);
													
						} else {
							$this->__data[$property] = $value;
						}
					}
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