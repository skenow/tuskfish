<?php

/**
* Tuskfish query composer class for SQLite database
*
* @copyright	Simon Wilkinson (Crushdepth) 2013
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
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
	 * @param string $column
	 * @param mixed $value
	 * @param string $operator
	 */
	function __construct($column, $value, $operator = '=')
	{
		self::__set('column', $column); // String, alphanumeric and underscore characters only.
		self::__set('value', $value); // String
		self::__set('operator', $operator); // String, whitelisted values only.
	}
	
	public function permittedOperators()
	{
		return array(
			'=', '==', '<', '<=', '>', '>=', '!=', '<>', 'IN', 'NOT IN', 'BETWEEN', 'IS', 'IS NOT',
			'LIKE');
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
}
