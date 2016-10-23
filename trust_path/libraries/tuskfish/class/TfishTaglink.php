<?php

/**
* Tuskfish taglink object class.
* 
* Represents a taglink object.
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishTaglink
{
	protected $__data = array(
		'id', // ID of this taglink object
		'tag_id', // ID of the tag object
		'content_type', // Type of content object
		'content_id', // ID of the content object
		'handler'); // The handler for taglink objects

	function __construct()
	{
		$this->__data['type'] = "TfishTaglink";
	}
	
	/**
	 * Get the value of an object property.
	 * 
	 * Intercepts direct calls to access an object property. This method can be modified to impose
	 * processing logic to specific properties before returning the value.
	 * 
	 * @param string $property name
	 * @return mixed|null $property value if it is set; otherwise null.
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
	 * Set the value of an object property and will not allow non-whitelisted properties to be set.
	 * 
	 * Intercepts direct calls to set the value of an object property. This method can be modified
	 * to impose processing logic to specific properties.
	 * 
	 * @param string $property name
	 * @param return void
	 */
	public function __set($property, $value)
	{
		if (isset($this->__data[$property])) {
			$this->__data[$property] = $value;
		} else {
			return false;
		}
	}
	
	/**
	 * Check if an object property is set.
	 * 
	 * Intercepts isset() calls to correctly read object properties. Can be modified to add
	 * processing logic to specific properties.
	 * 
	 * @param string $property name
	 * @return bool 
	 */
	public function __isset($property)
	{
		if (isset($this->__data[$property])) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Unsets an object property.
	 * 
	 * Intercepts unset() calls to correctly unset object properties. Can be modified to add 
	 * processing logic for specific properties.
	 * 
	 * @param string $property name
	 * @return bool true on success false on failure 
	 */
	public function __unset($property)
	{
		if (isset($this->__data[$property])) {
			unset($this->__data[$property]);
		} else {
			return false;
		}
	}
}