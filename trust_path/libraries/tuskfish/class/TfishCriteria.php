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
		'ordertype' => "ASC",
		'tag' => array() // Array of tag IDs
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
				
				case "tag":
					if (TfishFilter::isArray($value)) {
						$clean_tags = array();
						foreach ($value as $val) {
							if (TfishFilter::isInt($val, 1)) {
								$clean_tags[] = (int)$val;
							} else {
								trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
							}
							unset($val);
						}
						$this->__data['tag'] = $clean_tags;
					} else {
						trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
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
	
	/**
	 * Generate an SQL WHERE clause with PDO placeholders based on criteria items.
	 * 
	 * Loop through the criteria items building a list of PDO placeholders together
	 * with the SQL. These will be used to bind the values in the statement to prevent
	 * SQL injection. Note that values are NOT inserted into the SQL directly.
	 * 
	 * Enclose column identifiers in backticks to escape them. Link criteria items with AND/OR
	 * except on the last iteration ($count-1).
	 * 
	 * @return string $sql query
	 */
	/*public function renderSQL()
	{
		$sql = '';
		
		$count = count($this->item);
		if ($count) {
			$sql = "(";
				for ($i = 0; $i < $count; $i++) {
				$sql .= "`" . TfishDatabase::escapeIdentifier($this->item[$i]->column) . "` " 
						. $this->item[$i]->operator
						. " :" . TfishDatabase::escapeIdentifier($this->item[$i]->column);
				if ($i < ($count-1)) {
					$sql .= " " . $this->condition[$i] . " ";
				}
			}
			$sql .= ") ";
		}
		
		return $sql;
	}*/
	
	public function renderSQL()
	{
		$sql = '';
		$count = count($this->item);
		if ($count) {
			$sql = "(";
				for ($i = 0; $i < $count; $i++) {
				$sql .= "`" . TfishDatabase::escapeIdentifier($this->item[$i]->column) . "` " 
						. $this->item[$i]->operator . " :placeholder" . (string)$i;
				if ($i < ($count-1)) {
					$sql .= " " . $this->condition[$i] . " ";
				}
			}
			$sql .= ") ";
		}
		
		return $sql;
	}
	
	/**
	 * Generate an array of PDO placeholders based on criteria items.
	 * 
	 * Use this function to get a list of placeholders generated by renderSQL(). The two functions
	 * should be used together; use renderSQL() to create a WHERE clause with named placeholders,
	 * and renderPDO() to get a list of the named placeholders so that you can bind values to them.
	 * 
	 * @return string $sql query
	 */
	/*public function renderPDO() {
		$pdo_placeholders = array();
		$count = count($this->item);
		for ($i = 0; $i < $count; $i++) {
			$pdo_placeholders[":" . TfishDatabase::escapeIdentifier($this->item[$i]->column)] = $this->item[$i]->value;
		}
		
		return $pdo_placeholders;
	}*/
	
	public function renderPDO() {
		$pdo_placeholders = array();
		$count = count($this->item);
		for ($i = 0; $i < $count; $i++) {
			$pdo_placeholders[":placeholder" . (string)$i] = $this->item[$i]->value;
		}
		
		return $pdo_placeholders;
	}
	
	public function renderTagSQL() {
		$sql = '';
		$count = count($this->tag);
		if ($count == 1) {
			$sql .= "`taglink`.`tag_id` = :tag0 ";
		} elseif ($count > 1) {
			$sql .= "`taglink`.`tag_id` IN (";
			for($i = 0; $i < count($this->tag); $i++) {
				$sql .= ':tag' . (string)$i . ',';
			}
		$sql = rtrim($sql, ',');
			$sql .= ") ";
		}
		return $sql;
	}
	
	public function renderTagPDO() {
		$tag_placeholders = array();
		for($i = 0; $i < count($this->tag); $i++) {
			$tag_placeholders[":tag" . (string)$i] = (int)$this->tag[$i];
		}
		return $tag_placeholders;
	}
	
}