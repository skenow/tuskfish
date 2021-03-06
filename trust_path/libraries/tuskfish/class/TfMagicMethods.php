<?php

/**
 * TfMagicMethods trait file.
 * 
 * Provides common magic methods for non-write access to protected properties. Only to be used in
 * classes that have a TfValidator instance as a property.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.1
 * @package     core
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Allows for non-write access to protected properties and prevents non-whitelisted properties from
 * being set.
 * 
 * Only to be used in classes that have a TfValidator instance as a property.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1
 * @package     core
 * 
 */
trait TfMagicMethods
{
    
    /**
     * Get the value of a property.
     * 
     * Intercepts direct calls to access an object property. This method can be overridden to impose
     * processing logic to the value before returning it.
     * 
     * @param string $property Name of property.
     * @return mixed|null $property Value of property if it is set; otherwise null.
     */
    public function __get(string $property)
    {
        $cleanProperty = $this->validator->trimString($property);
        
        if (isset($this->$cleanProperty)) {
            return $this->$cleanProperty;
        } else {
            return null;
        }
    }
    
    /**
     * Check if a property is set.
     * 
     * Intercepts isset() calls to correctly read object properties. Prevents non-whitelisted,
     * arbitrary properties from being set. Can be overridden in child objects to add processing
     * logic for specific properties.
     * 
     * @param string $property Name of property.
     * @return bool True if set otherwise false.
     */
    public function __isset(string $property)
    {
        $cleanProperty = $this->validator->trimString($property);
        
        if (isset($this->$cleanProperty)) {
            return true;
        } else {
            return false;
        }
    }
    
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
        $cleanProperty = $this->validator->trimString($property);
        
        if (isset($this->$cleanProperty)) {
            trigger_error(TFISH_ERROR_DIRECT_PROPERTY_SETTING_DISALLOWED);
        } else {
            trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
        }
        
        exit;
    }

    /**
     * Unsets a property.
     * 
     * Intercepts unset() calls to correctly unset object properties. Can be overridden in child
     * objects to add processing logic for specific properties.
     * 
     * @param string $property Name of property.
     * @return bool True on success, false on failure.
     */
    public function __unset(string $property)
    {
        $cleanProperty = $this->validator->trimString($property);
        
        if (isset($this->$cleanProperty)) {
            unset($this->$cleanProperty);
            return true;
        } else {
            return false;
        }
    }

}
