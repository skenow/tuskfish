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
class TfishUser extends TfishBaseObject
{

    /** @var array $__data Array holding values of this object's properties. */
    protected $id;
    protected $admin_email;
    protected $password_hash;
    protected $user_salt;
    protected $user_group;
    protected $yubikey_id;
    protected $yubikey_id2;
    protected $login_errors;
    
    public function setId(int $id)
    {
        $clean_id = (int) $id;
        
        if (TfishDataValidator::isInt($clean_id, 1)) {    
            $this->id = $clean_id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setAdminEmail(string $email)
    {
        $clean_email = TfishDataValidator::trimString($email);

        if (TfishDataValidator::isEmail($clean_email)) {
            $this->admin_email = $clean_email;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    public function setPasswordHash(string $hash)
    {
        $clean_hash = TfishDataValidator::trimString($hash);
        $this->password_hash = $clean_hash;
    }
    
    public function setUserSalt(string $salt)
    {
        $clean_salt = TfishDataValidator::trimString($salt);
        $this->user_salt = $clean_salt;
    }
    
    public function setUserGroup(int $group)
    {
        $clean_group = (int) $group;
        
        if (TfishDataValidator::isInt($clean_group, 1)) {
            $this->user_group = $clean_group;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setYubikeyId(string $id)
    {
        $clean_id = TfishDataValidator::trimString($id);
        $this->yubikey_id = $clean_id;
    }
    
    public function setYubikeyId2(string $id)
    {
        $clean_id = TfishDataValidator::trimString($id);
        $this->yubikey_id2 = $clean_id;
    }
    
    public function setLoginErrors(int $number_of_errors)
    {
        $clean_number_of_errors = (int) $number_of_errors;
        
        if (TfishDataValidator::isInt($clean_number_of_errors, 0)) {
            $this->login_errors = $clean_number_of_errors;
        }  else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }

}
