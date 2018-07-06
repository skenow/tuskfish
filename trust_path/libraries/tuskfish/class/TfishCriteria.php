<?php

/**
 * TfishCriteria class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     database
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Query composer class for SQLite database.
 * 
 * Use this class to set parameters on database-related actions. Individual conditions are held
 * within the item property, as TfishCriteriaItem objects. Criteria holds the basic query parameters
 * and controls how TfishCriteriaItem are chained together (eg. with "AND", "OR").
 * 
 * See the Tuskfish Developer Guide for a full explanation and examples.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     database
 * @property    array $item Array of TfishCriteriaItem
 * @property    array $condition Array of conditions used to join TfishCriteriaItem (AND, OR)
 * @property    string $group_by Column to group results by
 * @property    int $limit Number of records to retrieve
 * @property    int $offset Starting point for retrieving records
 * @property    string $order Sort order
 * @property    string $order_type Sort ascending (ASC) or descending(DESC)
 * @property    array $tag Array of tag IDs
 */
class TfishCriteria
{

    /** @var array $__data Holds the values of the object's properties for access by magic methods. **/
    protected $__data = array(
        'item' => array(),
        'condition' => array(), 
        'group_by' => '',
        'limit' => 0,
        'offset' => 0,
        'order' => '',
        'order_type' => "DESC",
        'tag' => array()
    );
    
    /**
     * Add conditions (TfishCriteriaItem) to a query.
     * 
     * @param object $criteria_item TfishCriteriaItem object.
     * @param string $condition Condition used to chain TfishCriteriaItems, "AND" or "OR" only.
     */
    public function add(TfishCriteriaItem $criteria_item, string $condition = "AND")
    {
        $this->setItem($criteria_item);
        $this->setCondition($condition);
    }
    
    private function setCondition(string $condition)
    {
        $clean_condition = TfishDataValidator::trimString($condition);
        
        if ($clean_condition === "AND" || $clean_condition === "OR") {
            $this->__data['condition'][] = $clean_condition;
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
        }
    }
    
    public function setGroupBy(string $group_by)
    {
        $clean_group_by = TfishDataValidator::trimString($group_by);

        if (TfishDataValidator::isAlnumUnderscore($clean_group_by)) {
            $this->__data['group_by'] = $clean_group_by;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
        }
    }
    
    private function setItem(TfishCriteriaItem $item)
    {
        if (is_a($item, 'TfishCriteriaItem')) {
            $this->__data['item'][] = $item;
        } else {
            trigger_error(TFISH_ERROR_NOT_CRITERIA_ITEM_OBJECT, E_USER_ERROR);
        }
    }
    
    public function setLimit(int $limit)
    {
        if (TfishDataValidator::isInt($limit, 0)) {
            $this->__data['limit'] = (int) $limit;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setOffset(int $offset)
    {
        if (TfishDataValidator::isInt($offset, 0)) {
            $this->__data['offset'] = (int) $offset;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setOrder(string $order)
    {
        $clean_order = TfishDataValidator::trimString($order);

        if (TfishDataValidator::isAlnumUnderscore($clean_order)) {
            $this->__data['order'] = $clean_order;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
        }
    }
    
    public function setOrderType(string $order_type)
    {
        $clean_order_type = TfishDataValidator::trimString($order_type);
        
        if ($clean_order_type === "ASC") {
            $this->__data['order_type'] = "ASC";
        } else {
            $this->__data['order_type'] = "DESC";
        }
    }
    
    public function setTag(array $tags)
    {
        if (TfishDataValidator::isArray($tags)) {
            $clean_tags = array();

            foreach ($tags as $tag) {
                if (TfishDataValidator::isInt($tag, 1)) {
                    $clean_tags[] = (int) $tag;
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
                unset($tag);
            }

            $this->__data['tag'] = $clean_tags;
        } else {
            trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
        }
    }
    
    /**
     * Generate an array of PDO placeholders based on criteria items.
     * 
     * Use this function to get a list of placeholders generated by renderSql(). The two functions
     * should be used together; use renderSql() to create a WHERE clause with named placeholders,
     * and renderPdo() to get a list of the named placeholders so that you can bind values to them.
     * 
     * @return array $pdo_placeholders Array of PDO placeholders used for building SQL query.
     */
    public function renderPdo()
    {
        $pdo_placeholders = array();
        $count = count($this->item);
        
        for ($i = 0; $i < $count; $i++) {
            $pdo_placeholders[":placeholder" . (string) $i] = $this->item[$i]->value;
        }

        return $pdo_placeholders;
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
     * @return string $sql SQL query fragment.
     */
    public function renderSql()
    {
        $sql = '';
        $count = count($this->item);
        
        if ($count) {
            $sql = "(";
            
            for ($i = 0; $i < $count; $i++) {
                $sql .= "`" . TfishDatabase::escapeIdentifier($this->item[$i]->column) . "` " 
                        . $this->item[$i]->operator . " :placeholder" . (string) $i;
                
                if ($i < ($count - 1)) {
                    $sql .= " " . $this->condition[$i] . " ";
                }
            }
            $sql .= ") ";
        }

        return $sql;
    }

    /**
     * Generate an SQL WHERE clause with PDO placeholders based on the tag property.
     * 
     * Loop through the criteria->tags building a list of PDO placeholders together
     * with the SQL. These will be used to bind the values in the statement to prevent
     * SQL injection. Note that values are NOT inserted into the SQL directly.
     * 
     * @return string $sql SQL query fragment.
     */
    public function renderTagSql()
    {
        $sql = '';
        $count = count($this->tag);
        
        if ($count === 1) {
            $sql .= "`taglink`.`tag_id` = :tag0 ";
        } elseif ($count > 1) {
            $sql .= "`taglink`.`tag_id` IN (";
            
            for ($i = 0; $i < count($this->tag); $i++) {
                $sql .= ':tag' . (string) $i . ',';
            }
            
            $sql = rtrim($sql, ',');
            $sql .= ") ";
        }
        
        return $sql;
    }
    
    /**
     * Generate an array of PDO placeholders based on the tag property.
     * 
     * Use this function to get a list of placeholders generated by renderTagSql(). The two
     * functions should be used together; use renderTagSql() to create a WHERE clause with named
     * placeholders, and renderTagPdo() to get a list of the named placeholders so that you can
     * bind values to them.
     * 
     * @return array $tag_placeholders Array of PDO placeholders used for building SQL query.
     */
    public function renderTagPdo()
    {
        $tag_placeholders = array();
        
        for ($i = 0; $i < count($this->tag); $i++) {
            $tag_placeholders[":tag" . (string) $i] = (int) $this->tag[$i];
        }
        
        return $tag_placeholders;
    }
    
    /**
     * Unset existing type criteria.
     * 
     * Used by content object handler subclasses to remove any existing type filter when they may
     * need to set or reset it to a specific type.
     * 
     * @param int $key Key of the item array containing the type filter.
     */
    public function unsetType(int $key)
    {
        if (isset($this->__data['item'][$key])) {
            unset($this->__data['item'][$key]);
            unset($this->__data['condition'][$key]);
        }

        // Reindex the arrays.
        $this->setItem(array_values($this->__data['item']));
        $this->setCondition(array_values($this->__data['condition']));
    }
    
    /** Magic methods. **/
    
    /**
     * Intercept and prevent direct setting of properties.
     * 
     * Properties must be set using the relevant setter method.
     * 
     * @param string $property Name of property.
     * @param mixed $value Value of property.
     */
    public function __set(string $property, $value)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            trigger_error(TFISH_ERROR_DIRECT_PROPERTY_SETTING_DISALLOWED);
        } else {
            trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
        }
        
        exit;
    }
    
    /**
     * Get the value of a property.
     * 
     * Intercepts direct calls to access an object property. This method can be modified to impose
     * processing logic to the value before returning it.
     * 
     * @param string $property Name of property.
     * @return mixed|null $property Value of property if it is set; otherwise null.
     */
    public function __get(string $property)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            return $this->__data[$clean_property];
        } else {
            return null;
        }
    }

    /**
     * Check if a property is set.
     * 
     * Intercepts isset() calls to correctly read object properties. Can be modified to add
     * processing logic for specific properties.
     * 
     * @param string $property Name of property.
     * @return bool True if set, otherwise false.
     */
    public function __isset(string $property)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Unsets a property.
     * 
     * Intercepts unset() calls to correctly unset object properties. Can be modified to add
     * processing logic for specific properties.
     * 
     * @param string $property Name of property.
     * @return bool True on success false on failure.
     */
    public function __unset(string $property)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            unset($this->__data[$clean_property]);
            return true;
        } else {
            return false;
        }
    }

}
