<?php

/**
* Tuskfish query composer class for SQLite database.
* 
* Individual conditions are held within the item property, as TfishCriteriaItem objects.
* Criteria holds the basic query parameters and controls how TfishCriteriaItem are chained
* together (eg. with "AND", "OR". 
*
* @copyright	Simon Wilkinson (Crushdepth) 2013
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishCriteria
{
	protected $__data = array(
		'item' => array(), // Array of TfishCriteriaItem
		'condition' => array(), // Array of conditions used to join TfishCriteriaItem (AND, OR)
		'groupby' => false,
		'limit' => 0,
		'offset' => 0,
		'order' => false,
		'ordertype' => "ASC"
		);
	
	/**
	 * Constructor
	 */
	function __construct()
	{}
	
	public function add($criteria_item, $condition = "AND")
	{		
		self::__set('item', $criteria_item);
		self::__set('condition', $condition);
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
	// This is the problem, item needs to be a holder for non-standard properties
	public function __set($property, $value)
	{
		if (isset($this->__data[$property])) {
			switch ($property) {
				case "item": // Array of TfishCriteriaItem objects
					if (is_a($value, 'TfishCriteriaItem')) {
						$this->__data['item'][] = $value;
					} else {
						trigger_error(TFISH_ERROR_NOT_CRITERIA_ITEM_OBJECT, E_USER_ERROR);
					}
				break;
			
				case "limit": // int
				case "offset": // int
					if (TfishFilter::isInt($value, 0)) {
						$this->__data[$property] = (int)$value;
					} else {
						trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
					}
					
				break;
				
				case "condition":
					if ($value === "AND" || $value === "OR") {
						$this->__data['condition'][] = TfishFilter::trimString($value);
					} else {
						trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
					}
				break;
				
				case "order": // string; any property of target object
				case "groupby": // string; any property of target object
					$value = TfishFilter::trimString($value);
					if (TfishFilter::isAlnumUnderscore($value)) {
						$this->__data[$property] = $value;
					} else {
						trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
					}
					
				break;
				
				case "ordertype": // ASC or DESC
					if ($value == "DESC") {
						$this->__data['ordertype'] = "DESC";
					} else {
						$this->__data['ordertype'] = "ASC";
					}
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