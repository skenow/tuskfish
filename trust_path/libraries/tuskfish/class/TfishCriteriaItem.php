<?php

/**
* Tuskfish query composer class for SQLite database
* 
* Represents a single clause in the WHERE component of a database query. Add TfishCriteriaItem to
* TfishCriteria to build your queries. Please see the Tuskfish manual for a full explanation and
* examples.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishCriteriaItem
{
	protected $__data = array(
		'column' => false,
		'value' => false,
		'operator' => "=" // Default value.
	);
	
	/**
	 * Constructor
	 * 
	 * @param string $column of the database table
	 * @param mixed $value
	 * @param string $operator see permittedOperators() for a list
	 */
	function __construct($column, $value, $operator = '=')
	{
		self::__set('column', $column); // String, alphanumeric and underscore characters only.
		self::__set('value', $value); // String
		self::__set('operator', $operator); // String, whitelisted values only.
	}
	
	/**
	 * Provides a whitelist of permitted operators for use in database queries.
	 * 
	 * @return array of permitted operators for use in database queries
	 */
	public function permittedOperators()
	{
		return array(
			'=', '==', '<', '<=', '>', '>=', '!=', '<>', 'IN', 'NOT IN', 'BETWEEN', 'IS', 'IS NOT',
			'LIKE');
	}
	
	/**
	 * Get the value of an object property.
	 * 
	 * Intercepts direct calls to access an object property. This method can be modified to impose
	 * processing logic to the value before returning it.
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
	 * to impose data type restrictions and range checks before allowing the property
	 * to be set. 
	 * 
	 * @param string $property name
	 * @param return void
	 */
	public function __set($property, $value)
	{
		if (isset($this->__data[$property])) {
			switch ($property) {
				
				case "column": // Alphanumeric and underscore characters only
					$value = TfishFilter::trimString($value);
					if (TfishFilter::isAlnumUnderscore($value)) {
						$this->__data['column'] = $value;
					} else {
						trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
					}
				break;
			
				// Could be any type of value, so it is difficult to validate.
				case "value":
					$clean_value;
					$type = gettype($value);
					switch ($type) {
						
						// Strings are valid but should be trimmed of control characters.
						case "string":
							$clean_value = TfishFilter::trimString($value);
						break;
						
					
						// Types that can't be validated further in the current context.
						case "array":
						case "boolean":
						case "integer":
						case "double":
							$clean_value = $value;
						break;
							
						case "object":
						case "resource":
						case "NULL":
						case "uknown type":
							trigger_error(TFISH_ERROR_ILLEGAL_TYPE, E_USER_ERROR);
						break;
					}
					$this->__data['value'] = $clean_value;
				break;
			
				// The default operator is "=" and this will be used unless something else is set.
				case "operator":
					$value = TfishFilter::trimString($value);
					if (in_array($value, self::permittedOperators())) {
						$this->__data['operator'] = $value;
					} else {
						trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
					}
				break;
				
				default:
					trigger_error(TFISH_ERROR_ILLEGAL_TYPE, E_USER_ERROR);
				break;
			}
			return true;
		} else {
			trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
		}
	}
	
	/**
	 * Check if an object property is set.
	 * 
	 * Intercepts isset() calls to correctly read object properties. Can be modified to add
	 * processing logic for specific properties.
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
