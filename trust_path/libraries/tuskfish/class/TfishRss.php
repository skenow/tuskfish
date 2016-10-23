<?php

/**
* Tuskfish RSS feed generator class.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishRss extends TfishAncestralObject
{
	public function __construct()
	{
		parent::__construct();
		
		/**
		 * Whitelist of official channel properties and datatypes.
		 */
		$this->__properties['title'] = 'string'; // Name of channel.
		$this->__properties['link'] = 'url'; // URL to website associated with this channel.
		$this->__properties['description'] = 'string'; // Sentence describing the channel.
		$this->__properties['copyright'] = 'string'; // Copyright license of this channel.
		$this->__properties['managingEditor'] = 'email'; // Email of the editor.
		$this->__properties['webMaster'] = 'email'; // Email of the webmaster.
		// $this->__properties['category'] = 'int'; // Todo: Implement tag-specific sub-channels.
		$this->__properties['generator'] = 'string'; // Auto-increment, set by database.
		$this->__properties['image'] = 'string'; // Auto-increment, set by database.
		$this->__properties['items'] = 'array'; // Array of content objects.

		/**
		 * Set the permitted properties of this object.
		 */
		foreach ($this->__properties as $key => $value) {
			$this->__data[$key] = '';
		}
		
		/**
		 * Set default values of permitted properties.
		 */
		global $tfish_preference;
		
		$this->__data['title'] = $tfish_preference->site_name;
		$this->__data['link'] = TFISH_URL;
		$this->__data['description'] = $tfish_preference->site_description;
		$this->__data['copyright'] = $tfish_preference->site_copyright;
		$this->__data['managingEditor'] = $tfish_preference->site_email;
		$this->__data['webMaster'] = $tfish_preference->site_email;
		// $this->__data['category'] = 'int'; // Todo: Implement tag-specific sub-channels.
		$this->__data['generator'] = 'Tuskfish';
		//$this->__data['image'] = ''; // Todo: Add a preference or something for RSS feed.
		$this->__data['items'] = array();
		$this->__data['template'] = 'rss';
	}
	
	/**
	 * Validate and set an existing object property according to type specified in constructor.
	 * 
	 * For more fine-grained control each property could be dealt with individually.
	 * 
	 * @param string $property
	 * @param mixed $value
	 */
	public function __set($property, $value)
	{
		if (isset($this->__data[$property])) {
			
			// Validate $value against expected data type and business rules.
			$type = $this->__properties[$property];
			
			switch ($type) {
				
				case "array": // Items
					if (TfishFilter::isArray($value)) {
						$clean_items = array();
						foreach ($value as $val) {
							if (is_a('TfishContentObject')) {
								$clean_items[] = $val;
							} else {
								trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
							}
							unset($clean_val);
						}
						$this->__data[$property] = $clean_items;
					} else {
						trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
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
			
				case "int": // Tags, minimum value 1.
					if (TfishFilter::isInt($value, 1)) {
						$this->__data[$property] = (int)$value;
					} else {
						trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
					}
				break;
			
				case "string":
					$this->__data[$property] = TfishFilter::trimString($value);
				break;

				case "url":
					$value = TfishFilter::trimString($value);
					if (TfishFilter::isUrl($value)) {
						$this->__data[$property] = $value;
					} else {
						trigger_error(TFISH_ERROR_NOT_URL, E_USER_ERROR);
					}
				break;
			}
		}
	}
	
	//
	// ITEM (all optional, but either title or description must be included)
	// ====
	// title
	// link (URL of item)
	// description (teaser)
	// author (email address of the author)
	// category
	// comments (URL of comments page for this item)
	// enclosure (media object attached to this item)
	// guid (string that uniquely identifies the item, a permalink)
	// pubDate (when the item was published)
	// source (RSS channel that the item came from)
}