<?php

/**
 * TfishUser class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
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
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
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
    
    public function setId(int $id)
    {
        $clean_id = (int) $id;
        
        if (TfishDataValidator::isInt($clean_id, 1)) {    
            $this->__data['id'] = $clean_id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setAdminEmail(string $email)
    {
        $clean_email = TfishDataValidator::trimString($email);

        if (TfishDataValidator::isEmail($clean_email)) {
            $this->__data['admin_email'] = $clean_email;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    public function setPasswordHash(string $hash)
    {
        $clean_hash = TfishDataValidator::trimString($hash);
        $this->__data['password_hash'] = $clean_hash;
    }
    
    public function setUserSalt(string $salt)
    {
        $clean_salt = TfishDataValidator::trimString($salt);
        $this->__data['user_salt'] = $clean_salt;
    }
    
    public function setUserGroup(int $group)
    {
        $clean_group = (int) $group;
        if (TfishDataValidator::isInt($clean_group, 1)) {
            $this->__data['user_group'] = $clean_group;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setYubikeyId(string $id)
    {
        $clean_id = TfishDataValidator::trimString($id);
        $this->__data['yubikey_id'] = $clean_id;
    }
    
    public function setYubikeyId2(string $id)
    {
        $clean_id = TfishDataValidator::trimString($id);
        $this->__data['yubikey_id2'] = $clean_id;
    }
    
    public function setLoginErrors(int $number_of_errors)
    {
        $clean_number_of_errors = (int) $number_of_errors;
        
        if (TfishDataValidator::isInt($clean_number_of_errors, 0)) {
            $this->__data['login_errors'] = $clean_number_of_errors;
        }  else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
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
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            return $this->__data[$clean_property];
        } else {
            return null;
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
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            trigger_error(TFISH_ERROR_DIRECT_PROPERTY_SETTING_DISALLOWED);
        } else {
            trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
        }
        
        exit;
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
