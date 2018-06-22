<?php

/**
 * TfishAncestralObject class file.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */
// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Tuskfish parent data object class.
 * 
 * All content objects are descendants of this class via the first child, TfishContentObject. 
 * Object properties are held in a protected store ($__data) and accessed via magic methods.
 * Note that if a subclass implements magical __get() and __set() methods, the parental versions
 * will NOT be called unless you explicitly do it using parent::__get().
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
 */
class TfishAncestralObject
{

    /** @var array $__properties Whitelist that defines permitted content object properties. */
    protected $__properties = array();
    
    /** @var array Holds values of permitted content object properties, accessed via magic methods. */
    protected $__data = array();

    /**
     * Returns a whitelist of object properties whose values are allowed be set.
     * 
     * This function is used to build a list of $allowed_vars for a content object. Child classes
     * use this list to unset properties they do not use. Properties that are not resident in the
     * database are also unset here (handler, template, module and icon).
     * 
     * @return array Array of object properties.
     */
    public function getPropertyWhitelist()
    {
        $properties = $this->__properties;
        unset($properties['handler'], $properties['template'], $properties['module'],
                $properties['icon']);

        return $properties;
    }

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
        $clean_property = TfishFilter::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            return $this->__data[$clean_property];
        } else {
            return null;
        }
    }

    /**
     * Set the value of a whitelisted property.
     * 
     * Intercepts direct calls to set the value of an object property. This method is overridden by
     * child classes to impose data type restrictions and range checks before allowing the property
     * to be set. Tuskfish objects are designed not to trust other components; each conducts its
     * own internal validation checks. 
     * 
     * @param string $property Name of property.
     * @param mixed $value Value of property.
     */
    public function __set(string $property, $value)
    {
        $clean_property = TfishFilter::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            $this->__data[$clean_property] = $value;
        } else {
            trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
            exit;
        }
    }

    /**
     * Check if an object property is set.
     * 
     * Intercepts isset() calls to correctly read object properties. Can be overridden in child
     * objects to add processing logic for specific properties.
     * 
     * @param string $property Name of property to check.
     * @return bool True if set otherwise false.
     */
    public function __isset(string $property)
    {
        $clean_property = TfishFilter::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Unsets a property.
     * 
     * Intercepts unset() calls to correctly unset object properties. Can be overridden in child
     * objects to add processing logic for specific properties.
     * 
     * @param string $property Name of property.
     * @return bool True on success false on failure.
     */
    public function __unset(string $property)
    {
        $clean_property = TfishFilter::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            unset($this->__data[$clean_property]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Converts a content object to an array suitable for insert/update calls to the database.
     * 
     * Note that the returned array observes the PARENT object's getPropertyWhitelist() as a 
     * restriction on the setting of keys. This whitelist explicitly excludes the handler, 
     * template and module properties as these are part of the class definition and are not stored
     * in the database. Calling the parent's property whitelist ensures that properties that are
     * unset by child classes are zeroed (this is important when an object is changed to a
     * different subclass, as the properties used may differ).
     * 
     * @return array Array of object property/values.
     */
    public function toArray()
    {
        $key_values = array();
        $properties = $this->getPropertyWhitelist();
        
        foreach ($properties as $key => $value) {
            $key_values[$key] = $this->__data[$key];
        }
        
        return $key_values;
    }

}
