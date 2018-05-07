<?php

/**
 * TfishContact class file.
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
 * Contact object class.
 * 
 * Represents a contact (personal details of a participant, associate or trainee).
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     user
 * @property    int $id ID of this contact.
 * @property    int $title (Dr, Prof. etc).
 * @property    string $firstname First name of contact.
 * @property    string $midname Middle name(s) of contact.
 * @property    string $lastname Last name of contact.
 * @property    int $gender Male or female.
 * @property    string $job Job title.
 * @property    string $business_unit Organisational unit that contact works for.
 * @property    string $organisation Name of parental organisation.
 * @property    string $address Address of organisation (usually postal).
 * @property    string $city City name.
 * @property    string $state State or territory.
 * @property    string $country Country or special administrative region.
 * @property    string $email Email address.
 * @property    string $mobile Mobile phone number.
 */
class TfishContact
{
    /** @var array $__data Array holding values of this objects properties. */
    protected $__data = array(
        'id',
        'title',
        'firstname',
        'midname',
        'lastname',
        'gender',
        'job',
        'business_unit',
        'organisation',
        'address',
        'city',
        'state',
        'country',
        'email',
        'mobile'
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
            switch ($clean_property) {
                // Must be positive integer > 0.
                case "id":
                case "country":
                    $clean_value = (int) $value;
                    if (TfishFilter::isInt($clean_value, 1)) {
                        $this->__data[$clean_property] = $clean_value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                    }
                    break;
                
                // 1. Dr, 2. Prof., 3. Mr., 4. Mrs, 5. Ms. 
                case "title":
                    $clean_value = (int) $value;
                    if (TfishFilter::isInt($clean_value, 1, 5)) {
                        $this->__data[$clean_property] = $clean_value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                    }
                    break;
            
                // 1. Male, 2. Female.
                case "gender":
                    $clean_value = (int) $value;
                    if (TfishFilter::isInt($clean_value, 1, 2)) {
                        $this->__data[$clean_property] = $clean_value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                    }
                    break;
                
                // Must be valid email address.
                case "email":
                    $clean_value = TfishFilter::trimString($value);
                    if (TfishFilter::isEmail($clean_email)) {
                        $this->__data[$clean_property] = $clean_value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
                    }
                    break;
                
                // Fields that are strings without specific validation rules.
                default:
                    $clean_value = trimString($value);
                    $this->__data[$clean_property] = $clean_value;
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
     * @return bool True on success, false on failure.
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

}