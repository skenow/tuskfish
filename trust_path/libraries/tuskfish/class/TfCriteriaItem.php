<?php

/**
 * TfCriteriaItem class file.
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
 * Clause composer class for SQLite database
 * 
 * Represents a single clause in the WHERE component of a database query. Add TfCriteriaItem to
 * TfCriteria to build your queries. Please see the Tuskfish Developer Guide for a full
 * explanation and examples.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     database
 * @uses        trait TfMagicMethods Prevents direct setting of properties / unlisted properties.
 * @property    TfValidator $validator Instance of the Tuskfish data validator class.
 * @property    string $column Name of column in database table
 * @property    mixed $value Value to compare
 * @property    string $operator The operator to use for evaluation (=, +, >, < etc)
 */
class TfCriteriaItem
{
    
    use TfMagicMethods;
    
    protected $validator;
    protected $column = false;
    protected $value = false;
    protected $operator = "="; // Default value.

    /**
     * Constructor.
     * 
     * @param TfValidator $validator Instance of the Tuskfish data validator class.
     * @param string $column Name of column in database table. Alphanumeric and underscore
     * characters only.
     * @param mixed $value Value of the column.
     * @param string $operator See getListOfPermittedOperators() for a list of acceptable operators.
     */
    function __construct(TfValidator $validator, string $column, $value, string $operator = '=')
    {

        $this->validator = $validator;
        $this->setColumn($column);
        $this->setValue($value);
        $this->setOperator($operator);
    }

    /**
     * Provides a whitelist of permitted operators for use in database queries.
     * 
     * @todo Consider adding support for "IN", "NOT IN", "BETWEEN", "IS" and "IS NOT". This is a bit
     * messy in PDO if you want to use placeholders because PDO will escape them as a single element
     * unless you pass in an array and build the query string fragment manually in a loop 
     * (complicated by the need to distinguish between string and int datatypes). So manual queries
     * may be easier for now. An alternative approach would be to add an extra parameter to
     * TfCriteria that allows a manual query fragment to be passed in and appended as the last
     * clause of the dynamically generated query string. That would let you handle cases like this
     * simply, but lose the protection from using 100% bound values in the Tuskfish API, which I am
     * very reluctant to give up. 
     * 
     * @return array Array of permitted operators for use in database queries.
     */
    public function getListOfPermittedOperators()
    {
        return array('=', '==', '<', '<=', '>', '>=', '!=', '<>', 'LIKE');
    }

    /**
     * Specifies the column to use in a query clause. 
     * 
     * @param string $value Name of column.
     */
    
    public function setColumn($value)
    {
        $cleanValue = $this->validator->trimString($value);
                    
        if ($this->validator->isAlnumUnderscore($cleanValue)) {
            $this->column = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
        }
    }
    
    /**
     * Sets the operator (=, <, >, etc) to use in a query clause.
     * 
     * @param string $value An operator to use in a clause.
     */
    public function setOperator($value)
    {
        $cleanValue = $this->validator->trimString($value);
                    
        if (in_array($cleanValue, $this->getListOfPermittedOperators(), true)) {
            $this->operator = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
        }
    }
    
    /**
     * Sets the value of a column to use in a query clause.
     * 
     * @param mixed $value Value of column.
     */
    public function setValue($value)
    {
        $type = gettype($value);

        switch ($type) {
            case "string":
                $cleanValue = $this->validator->trimString($value);
                break;

            // Types that can't be validated further in the current context.
            case "array":
            case "boolean":
            case "integer":
            case "double":
                $cleanValue = $value;
                break;

            // Illegal types.
            case "object":
            case "resource":
            case "NULL":
            case "unknown type":
                trigger_error(TFISH_ERROR_ILLEGAL_TYPE, E_USER_ERROR);
                break;
        }

        $this->value = $cleanValue;
    }
        
}