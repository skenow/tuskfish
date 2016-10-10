<?php

/**
* Tuskfish parent data object class.
* 
* All content objects are descendants of this class via the first child, TfishContentObject. 
* Object properties are held in a protected store and accessed via magic methods.
* Note that if a subclass implements magical __get() and __set() methods, the parental versions
* will NOT be called unless you explicitly do it using parent::__get().
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishAncestralObject
{	
	// Object properties are defined in this array.
	protected $__properties = array();
	
	// Object properties are stored in this array.
	protected $__data = array();
	
	/**
	 * Generic constructor
	 */
	function __construct()
	{}
	
	/**
	 * Returns a whitelist of properties whose values are allowed be set or altered by form input.
	 * 
	 * This function is used to build a list of $allowed_vars for use in TfishFilter::filterData()
	 * or to insert a row in the database. As this function is called by child classes they may
	 * optionally unset some other properties that they do not use.
	 * 
	 * @return array
	 */
	public function getPropertyWhitelist() {
		$properties = $this->__properties;
		unset($properties['handler'], $properties['template'], $properties['module']);
		return $properties;
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
			trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
			exit;
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
	
	/**
	 * Converts the object to an array suitable for insert/update calls to the database.
	 * 
	 * Note that the returned array observes the PARENT object's getPropertyWhitelist() as a 
	 * restriction on the setting of keys. This whitelist explicitly excludes the handler, 
	 * emplate and module properties as these are part of the class definition and are not stored
	 * in the database. Calling the parent's property whitelist ensures that properties that are
	 * unset by child classes are zeroed (this is important when an object is changed to a
	 * different subclass, as the properties used may differ).
	 * 
	 * @param object $obj
	 * @return array
	 */
	public function toArray()
	{	
		$key_values = array();
		$properties = $this->getPropertyWhitelist();
		foreach ($properties as $key => $value) {
			$key_values[$key] = $this->__data[$key];
		}
		return $key_values;
	}
}