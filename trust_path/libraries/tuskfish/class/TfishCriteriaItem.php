<?php

/**
 * TfishCriteriaItem class file.
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
 * Represents a single clause in the WHERE component of a database query. Add TfishCriteriaItem to
 * TfishCriteria to build your queries. Please see the Tuskfish Developer Guide for a full
 * explanation and examples.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     database
 * @property    string $column Name of column in database table
 * @property    mixed $value Value to compare
 * @property    string $operator The operator to use for evaluation (=, +, >, < etc)
 */
class TfishCriteriaItem
{

    /** @var array $__data Array holding values of this object properties, accessed via magic methods. */
    protected $__data = array(
        'column' => false,
        'value' => false,
        'operator' => "=" // Default value.
    );

    /**
     * Constructor.
     * 
     * @param string $column Name of column in database table. Alphanumeric and underscore
     * characters only.
     * @param mixed $value Value of the column.
     * @param string $operator See listOfPermittedOperators() for a list of acceptable operators.
     */
    function __construct(string $column, $value, string $operator = '=')
    {
        self::__set('column', $column);
        self::__set('value', $value);
        self::__set('operator', $operator);
    }

    /**
     * Get the value of an object property.
     * 
     * Intercepts direct calls to access an object property. This method can be modified to impose
     * processing logic to the value before returning it.
     * 
     * @param string $property Name of property.
     * @return mixed|null $property Value if it is set; otherwise null.
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
     * Provides a whitelist of permitted operators for use in database queries.
     * 
     * @todo Consider adding support for "IN", "NOT IN", "BETWEEN", "IS" and "IS NOT". This is a bit
     * messy in PDO if you want to use placeholders because PDO will escape them as a single element
     * unless you pass in an array and build the query string fragment manually in a loop 
     * (complicated by the need to distinguish between string and int datatypes). So manual queries
     * may be easier for now. An alternative approach would be to add an extra parameter to
     * TfishCriteria that allows a manual query fragment to be passed in and appended as the last
     * clause of the dynamically generated query string. That would let you handle cases like this
     * simply, but lose the protection from using 100% bound values in the Tuskfish API, which I am
     * very reluctant to give up. 
     * 
     * @return array Array of permitted operators for use in database queries.
     */
    public function listOfPermittedOperators()
    {
        return array(
            '=', '==', '<', '<=', '>', '>=', '!=', '<>', 'LIKE');
    }

    /**
     * Set the value of an object property and will not allow non-whitelisted properties to be set.
     * 
     * Intercepts direct calls to set the value of an object property. This method can be modified
     * to impose data type restrictions and range checks before allowing the property
     * to be set. 
     * 
     * @param string $property Name of property.
     * @param mixed $value Value of property.
     */
    public function __set(string $property, $value)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            switch ($clean_property) {
                case "column": // Alphanumeric and underscore characters only
                    $value = TfishDataValidator::trimString($value);
                    
                    if (TfishDataValidator::isAlnumUnderscore($value)) {
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
                            $clean_value = TfishDataValidator::trimString($value);
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
                    $value = TfishDataValidator::trimString($value);
                    
                    if (in_array($value, self::listOfPermittedOperators())) {
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
     * @param string $property Name of property.
     * @return bool True if set otherwise false.
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
     * Unsets an object property.
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
