<?php

/**
 * TfCriteria class file.
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
 * within the item property, as TfCriteriaItem objects. Criteria holds the basic query parameters
 * and controls how TfCriteriaItem are chained together (eg. with "AND", "OR").
 * 
 * See the Tuskfish Developer Guide for a full explanation and examples.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     database
 * @property    array $item Array of TfCriteriaItem
 * @property    array $condition Array of conditions used to join TfCriteriaItem (AND, OR)
 * @property    string $group_by Column to group results by
 * @property    int $limit Number of records to retrieve
 * @property    int $offset Starting point for retrieving records
 * @property    string $order Sort order
 * @property    string $order_type Sort ascending (ASC) or descending(DESC)
 * @property    array $tag Array of tag IDs
 */
class TfCriteria
{
    
    use TfMagicMethods;

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
    
    public function __construct(TfValidator $tf_validator)
    {
        $this->validator = $tf_validator;
    }
    
    /**
     * Add conditions (TfCriteriaItem) to a query.
     * 
     * @param object $criteria_item TfCriteriaItem object.
     * @param string $condition Condition used to chain TfCriteriaItems, "AND" or "OR" only.
     */
    public function add(TfCriteriaItem $criteria_item, string $condition = "AND")
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
    
    private function setItem(TfCriteriaItem $item)
    {
        if (is_a($item, 'TfCriteriaItem')) {
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
        $this->item = array_values($this->item);
        $this->condition = array_values($this->condition);
    }

}
