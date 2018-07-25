<?php

/**
 * TfUser class file.
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
 * @uses        trait TfMagicMethods Prevents direct setting of properties / unlisted properties.
 * @property    TfValidator $validator Instance of the Tuskfish data validator class.
 * @property    int $id ID of this user
 * @property    string $adminEmail email address of this user
 * @property    string $passwordHash
 * @property    string $userSalt Random string used to confound dictionary attacks against password
 * hashes.
 * @property    int $userGroup The privilege group this user belongs to (not implemented).
 * @property    string $yubikeyId ID of the primary Yubikey hardware authentication token
 * for this account.
 * @property    string $yubikeyId2 ID of the secondary Yubikey hardware authentication token for
 * this account.
 * @property    string $loginErrors Number of times the user has failed to enter their password
 * correctly. It is reset to zero on successful login.
 */
class TfUser
{
    
    use TfMagicMethods;

    protected $validator;
    protected $id;
    protected $adminEmail;
    protected $passwordHash;
    protected $userSalt;
    protected $userGroup;
    protected $yubikeyId;
    protected $yubikeyId2;
    protected $loginErrors;
    
    public function __construct(TfValidator $tfValidator)
    {
        $this->validator = $tfValidator;
    }
    
    /**
     * Get the value of a property.
     * 
     * Intercepts direct calls to access an object property. Disallow public access to sensitive
     * properties (passwordHash, userSalt).
     * 
     * @param string $property Name of property.
     * @return mixed|null $property Value of property if it is set; otherwise null.
     */
    public function __get(string $property)
    {
        $cleanProperty = $this->validator->trimString($property);
        
        if (isset($cleanProperty) && $cleanProperty !== 'passwordHash' && $cleanProperty !== 'userSalt') {
            return $this->$cleanProperty;
        } else {
            return null;
        }
    }
    
    /**
     * Set the administrator's email address.
     * 
     * @param string $email Email of the administrator of this website.
     */
    public function setAdminEmail(string $email)
    {
        $cleanEmail = $this->validator->trimString($email);

        if ($this->validator->isEmail($cleanEmail)) {
            $this->adminEmail = $cleanEmail;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    /**
     * Set the ID for this account.
     * 
     * @param int $id ID of this user account.
     */
    public function setId(int $id)
    {
        $cleanId = (int) $id;
        
        if ($this->validator->isInt($cleanId, 1)) {    
            $this->id = $cleanId;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the number of times this user has failed to correctly enter their password.
     * 
     * Set this value to zero on successful login.
     * 
     * @param int $number_of_errors Number of failed login attempts.
     */
    public function setLoginErrors(int $number_of_errors)
    {
        $clean_number_of_errors = (int) $number_of_errors;
        
        if ($this->validator->isInt($clean_number_of_errors, 0)) {
            $this->loginErrors = $clean_number_of_errors;
        }  else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }    
    
    /**
     * Set the password hash for this user.
     * 
     * @param string $hash Hash of the user's password.
     */
    public function setPasswordHash(string $hash)
    {
        $clean_hash = $this->validator->trimString($hash);
        $this->passwordHash = $clean_hash;
    }
    
    /**
     * Set the privilege group for this user (not implemented).
     * 
     * @param int $group User group.
     */
    public function setUserGroup(int $group)
    {
        $clean_group = (int) $group;
        
        if ($this->validator->isInt($clean_group, 1)) {
            $this->userGroup = $clean_group;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the user-specific salt for this user.
     * 
     * Used in password hash calculations.
     * 
     * @param string $salt Random string of text.
     */
    public function setUserSalt(string $salt)
    {
        $clean_salt = $this->validator->trimString($salt);
        $this->userSalt = $clean_salt;
    }
    
    /**
     * Set the ID of the primary yubikey hardware token for this account.
     * 
     * @param string $id Yubikey ID.
     */
    public function setYubikeyId(string $id)
    {
        $cleanId = $this->validator->trimString($id);
        $this->yubikeyId = $cleanId;
    }
    
    /**
     * Set the ID of the secondary yubikey hardware token for this account.
     * 
     * @param string $id Yubikey ID.
     */
    public function setYubikeyId2(string $id)
    {
        $cleanId = $this->validator->trimString($id);
        $this->yubikeyId2 = $cleanId;
    }

}
