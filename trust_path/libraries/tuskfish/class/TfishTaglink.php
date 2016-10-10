<?php

/**
* Tuskfish taglink object class.
* 
* Represents a taglink object.
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishTaglink
{
	protected $__data = array(
		'id', // ID of this taglink object
		'tag_id', // ID of the tag object
		'content_type', // Type of content object
		'content_id', // ID of the content object
		'handler'); // The handler for taglink objects
	
	/**
	 * Generic constructor and object definition - unset any properties not required by this content subclass
	 */
	function __construct()
	{
		$this->__data['type'] = "TfishTaglink";
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
			$this->__data[$property] = $value;
		} else {
			return false;
		}
	}
	
	/**
	 * Intercept isset() calls to correctly read object properties
	 * 
	 * @param type $property
	 * @return type 
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
	 * Intercept unset() calls to correctly unset object properties
	 * 
	 * @param type $property
	 * @return type 
	 */
	public function __unset($property)
	{
		if (isset($this->__data[$property])) {
			unset($this->__data[$property]);
		} else {
			return false;
		}
	}
	
	public function insert()
	{	
	}
	
	public function delete()
	{	
	}
	
	public function setErrors()
	{	
	}
}

