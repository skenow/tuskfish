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
 * @property    int $country Country or special administrative region.
 * @property    string $email Email address.
 * @property    string $mobile Mobile phone number.
 */
class TfishContact extends TfishAncestralObject
{
    /** Initialise default content object properties and values. */
    function __construct()
    {
        /**
         * Whitelist of official properties and datatypes.
         */
        $this->__properties['id'] = 'int';
        $this->__properties['type'] = 'alpha';
        $this->__properties['title'] = 'int';
        $this->__properties['firstname'] = 'string';
        $this->__properties['midname'] = 'string';
        $this->__properties['lastname'] = 'string';
        $this->__properties['gender'] = 'int';
        $this->__properties['job'] = 'string';
        $this->__properties['business_unit'] = 'string';
        $this->__properties['organisation'] = 'string';
        $this->__properties['address'] = 'string';
        $this->__properties['city'] = 'string';
        $this->__properties['state'] = 'string';
        $this->__properties['country'] = 'int';
        $this->__properties['email'] = 'string';
        $this->__properties['mobile'] = 'string';
        $this->__properties['tags'] = 'int'; // activity
        $this->__properties['submission_time'] = 'int';
        $this->__properties['template'] = 'alnumunder';
        
        /**
         * Set the permitted properties of this object.
         */
        foreach ($this->__properties as $key => $value) {
            $this->__data[$key] = '';
        }
        $this->__data['type'] = "contact";
        $this->__data['template'] = "contact";
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
                
                // Must be integer >= 0.
                case "country":
                case "tags":
                    $clean_value = (int) $value;
                    if (TfishFilter::isInt($clean_value, 0)) {
                        $this->__data[$clean_property] = $clean_value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                    }
                    break;
                
                // Must be positive integer > 0.
                case "id":
                case "submission_time":
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
                    if (empty($clean_value) || TfishFilter::isEmail($clean_value)) {
                        $this->__data[$clean_property] = $clean_value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
                    }
                    break;
                
                // Fields that are strings without specific validation rules.
                default:
                    $clean_value = TfishFilter::trimString($value);
                    $this->__data[$clean_property] = $clean_value;
                    break;
            }
        } else {
            trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
        }
    }
    
    public function escape(string $property, bool $escape_html = false)
    {
        $clean_property = TfishFilter::trimString($property);
        
        // If property is not set return null.
        if (!isset($this->__data[$clean_property])) {
            return null;
        }
        
        // Escape data for display.
        return htmlspecialchars($this->__data[$clean_property], ENT_NOQUOTES, 'UTF-8', false);
    }
}