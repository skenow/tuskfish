<?php

/**
 * TfCriteriaItemFactory class file.
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
 * Factory for instantiating TfCriteriaItem objects and injecting dependencies.
 * 
 * Use this class to delegate construction of TfCriteriaItem objects.
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
class TfCriteriaItemFactory
{
    protected $validator;
    
    public function __construct(TfValidator $tfValidator)
    {
        if (is_a($tfValidator, 'TfValidator')) {
            $this->validator = $tfValidator;
        }
    }
    
    public function getItem(string $column, $value, string $operator = '=')
    {
        return new TfCriteriaItem($this->validator, $column, $value, $operator);
    }
}
