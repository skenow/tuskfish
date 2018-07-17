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
    
    use TfishMagicMethods;

    protected $validator;
    protected $item = array();
    protected $condition = array();
    protected $group_by = '';
    protected $limit = 0;
    protected $offset = 0;
    protected $order = '';
    protected $order_type = "DESC";
    protected $secondary_order = '';
    protected $secondary_order_type = "DESC";
    protected $tag = array();
    
    public function __construct(object $tfish_validator)
    {
        if (is_object($tfish_validator)) {
            $this->validator = $tfish_validator;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
    }
    
    /**
     * Add conditions (TfishCriteriaItem) to a query.
     * 
     * @param object $criteria_item TfishCriteriaItem object.
     * @param string $condition Condition used to chain TfishCriteriaItems, "AND" or "OR" only.
     */
    public function add(object $criteria_item, string $condition = "AND")
    {
        $this->setItem($criteria_item);
        $this->setCondition($condition);
    }
    
    private function setCondition(string $condition)
    {
        $clean_condition = $this->validator->trimString($condition);
        
        if ($clean_condition === "AND" || $clean_condition === "OR") {
            $this->condition[] = $clean_condition;
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
        }
    }
    
    public function setGroupBy(string $group_by)
    {
        $clean_group_by = $this->validator->trimString($group_by);

        if ($this->validator->isAlnumUnderscore($clean_group_by)) {
            $this->group_by = $clean_group_by;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
        }
    }
    
    private function setItem(object $item)
    {
        if (is_object($item)) {
            $this->item[] = $item;
        } else {
            trigger_error(TFISH_ERROR_NOT_CRITERIA_ITEM_OBJECT, E_USER_ERROR);
        }
    }
    
    public function setLimit(int $limit)
    {
        if ($this->validator->isInt($limit, 0)) {
            $this->limit = (int) $limit;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setOffset(int $offset)
    {
        if ($this->validator->isInt($offset, 0)) {
            $this->offset = (int) $offset;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setOrder(string $order)
    {
        $clean_order = $this->validator->trimString($order);

        if ($this->validator->isAlnumUnderscore($clean_order)) {
            $this->order = $clean_order;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
        }
    }
    
    public function setOrderType(string $order_type)
    {
        $clean_order_type = $this->validator->trimString($order_type);
        
        if ($clean_order_type === "ASC") {
            $this->order_type = "ASC";
        } else {
            $this->order_type = "DESC";
        }
    }
    
    public function setSecondaryOrder(string $secondary_order)
    {
        $clean_secondary_order = $this->validator->trimString($secondary_order);

        if ($this->validator->isAlnumUnderscore($clean_secondary_order)) {
            $this->secondary_order = $clean_secondary_order;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
        }
    }
    
    public function setSecondaryOrderType(string $secondary_order_type)
    {
        $clean_secondary_order_type = $this->validator->trimString($secondary_order_type);
        
        if ($clean_secondary_order_type === "ASC") {
            $this->secondary_order_type = "ASC";
        } else {
            $this->secondary_order_type = "DESC";
        }
    }
    
    public function setTag(array $tags)
    {
        if ($this->validator->isArray($tags)) {
            $clean_tags = array();

            foreach ($tags as $tag) {
                if ($this->validator->isInt($tag, 1)) {
                    $clean_tags[] = (int) $tag;
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
                unset($tag);
            }

            $this->tag = $clean_tags;
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
        if (isset($this->item[$key])) {
            unset($this->item[$key]);
            unset($this->condition[$key]);
        }

        // Reindex the arrays.
        $this->setItem(array_values($this->item));
        $this->setCondition(array_values($this->condition));
    }

}
