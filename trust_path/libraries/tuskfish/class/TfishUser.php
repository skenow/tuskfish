<?php

/**
 * TfishUser class file.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     user
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * User object class.
 * 
 * Represents a user. Since Tuskfish is a single-user system, this class will probably be deprecated
 * in due course.
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     user
 * @property    int $id ID of this user
 * @property    string $admin_email email address of this user
 * @property    string $password_hash
 * @property    int $user_group
 */
class TfishUser
{

    /** @var array $__data Array holding values of this object's properties. */
    protected $__data = array(
        'id',
        'admin_email',
        'password_hash',
        'user_salt',
        'user_group',
        'yubikey_id',
        'yubikey_id2',
        'login_errors'
    );

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
        $clean_property = TfishDataValidator::trimString($property);
        
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
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            switch ($clean_property) {
                case "id":
                    if (TfishDataValidator::isInt($value, 1)) {
                        $clean_value = (int) $value;
                        $this->__data[$clean_property] = $clean_value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                    }
                    break;
                
                case "admin_email":
                    $clean_value = TfishDataValidator::trimString($value);
                    
                    if (TfishDataValidator::isEmail($clean_value)) {
                        $this->__data[$clean_property] = $clean_value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
                    }
                    break;
                
                case "password_hash":
                    $clean_value = TfishDataValidator::trimString($value);
                    $this->__data[$clean_property] = $clean_value;
                    break;
                
                case "user_salt":
                    $clean_value = TfishDataValidator::trimString($value);
                    $this->__data[$clean_property] = $clean_value;
                    break;
                
                case "user_group":                    
                    if (TfishDataValidator::isInt($value, 1)) {
                        $this->__data[$clean_property] = (int) $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                    }
                    break;
                
                case "yubikey_id":
                case "yubikey_id2":
                    $clean_value = TfishDataValidator::trimString($value);
                    $this->__data[$clean_property] = $clean_value;
                    break;
                
                case "login_errors":
                    if (TfishDataValidator::isInt($value, 0)) {
                        $this->__data[$clean_property] = (int) $value;
                    }  else {
                        trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                    }
                    break;
            }
        } else {
            trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
        }
    }

    /**
     * Check if a property is set.
     * 
     * Intercepts isset() calls to correctly read object properties. Can be overridden in child
     * objects to add processing logic for specific properties.
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
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            unset($this->__data[$clean_property]);
            return true;
        } else {
            return false;
        }
    }

}
