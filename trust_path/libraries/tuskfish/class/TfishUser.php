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
    
    use TfishMagicMethods;

    protected $validator;
    protected $id;
    protected $admin_email;
    protected $password_hash;
    protected $user_salt;
    protected $user_group;
    protected $yubikey_id;
    protected $yubikey_id2;
    protected $login_errors;
    
    public function __construct(object $tfish_validator)
    {
        if (is_object($tfish_validator)) {
            $this->validator = $tfish_validator;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
    }
    
    public function setId(int $id)
    {
        $clean_id = (int) $id;
        
        if ($this->validator->isInt($clean_id, 1)) {    
            $this->id = $clean_id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setAdminEmail(string $email)
    {
        $clean_email = $this->validator->trimString($email);

        if ($this->validator->isEmail($clean_email)) {
            $this->admin_email = $clean_email;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    public function setPasswordHash(string $hash)
    {
        $clean_hash = $this->validator->trimString($hash);
        $this->password_hash = $clean_hash;
    }
    
    public function setUserSalt(string $salt)
    {
        $clean_salt = $this->validator->trimString($salt);
        $this->user_salt = $clean_salt;
    }
    
    public function setUserGroup(int $group)
    {
        $clean_group = (int) $group;
        
        if ($this->validator->isInt($clean_group, 1)) {
            $this->user_group = $clean_group;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setYubikeyId(string $id)
    {
        $clean_id = $this->validator->trimString($id);
        $this->yubikey_id = $clean_id;
    }
    
    public function setYubikeyId2(string $id)
    {
        $clean_id = $this->validator->trimString($id);
        $this->yubikey_id2 = $clean_id;
    }
    
    public function setLoginErrors(int $number_of_errors)
    {
        $clean_number_of_errors = (int) $number_of_errors;
        
        if ($this->validator->isInt($clean_number_of_errors, 0)) {
            $this->login_errors = $clean_number_of_errors;
        }  else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Get the value of a property.
     * 
     * Intercepts direct calls to access an object property. Disallow public access to sensitive
     * properties (password_hash, user_salt).
     * 
     * @param string $property Name of property.
     * @return mixed|null $property Value of property if it is set; otherwise null.
     */
    public function __get(string $property)
    {
        $clean_property = $this->validator->trimString($property);
        
        if (isset($clean_property) && $clean_property !== 'password_hash' && $clean_property !== 'user_salt') {
            return $this->$clean_property;
        } else {
            return null;
        }
    }

}
