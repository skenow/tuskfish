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
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
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

	function __construct()
	{}
	
	/**
	 * Returns a whitelist of object properties whose values are allowed be set or altered by form input.
	 * 
	 * This function is used to build a list of $allowed_vars for a content object. Child classes
	 * use this list to unset properties they do not use. It is also used by TfishFilter::filterData()
	 * or when screening data before inserting a row in the database.
	 * 
	 * @return array of object properties
	 */
	public function getPropertyWhitelist() {
		$properties = $this->__properties;
		unset($properties['handler'], $properties['template'], $properties['module']);
		
		return $properties;
	}
	
	/**
	 * Get the value of an object property.
	 * 
	 * Intercepts direct calls to access an object property. This method can be overridden to impose
	 * processing logic to the value before returning it.
	 * 
	 * @param string $property name
	 * @return mixed|boolean $property value if it is set; otherwise null.
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
			$this->__data[$property] = $value;
		} else {
			trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
			exit;
		}
	}
	
	/**
	 * Check if an object property is set.
	 * 
	 * Intercepts isset() calls to correctly read object properties. Can be overridden in child
	 * objects to add processing logic for specific properties.
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
	 * Intercepts unset() calls to correctly unset object properties. Can be overridden in child
	 * objects to add processing logic for specific properties.
	 * 
	 * @param string $property name
	 * @return bool true on success false on failure 
	 */
	public function __unset($property)
	{
		if (isset($this->__data[$property])) {
			unset($this->__data[$property]);
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Converts the object to an array suitable for insert/update calls to the database.
	 * 
	 * Note that the returned array observes the PARENT object's getPropertyWhitelist() as a 
	 * restriction on the setting of keys. This whitelist explicitly excludes the handler, 
	 * template and module properties as these are part of the class definition and are not stored
	 * in the database. Calling the parent's property whitelist ensures that properties that are
	 * unset by child classes are zeroed (this is important when an object is changed to a
	 * different subclass, as the properties used may differ).
	 * 
	 * @param object $obj
	 * @return array of object property/values.
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