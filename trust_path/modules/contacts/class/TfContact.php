<?php

/**
 * TfContact class file.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     contacts
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Represents a contact (personal details of a participant, associate or trainee).
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     contacts
 * @uses        TfMagicMethods
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
 * @property    int $tags ID of activity this contact was involved in.
 * @property    int $lastUpdated Timestamp for creation / last update of this record.
 * @property    string $template Name of the template file for displaying contacts.
 */
class TfContact
{
    
    use TfMagicMethods;
    
    protected $validator;
    protected $id;
    protected $title;
    protected $firstname;
    protected $midname;
    protected $lastname;
    protected $gender;
    protected $job;
    protected $businessUnit;
    protected $organisation;
    protected $address;
    protected $city;
    protected $state;
    protected $country;
    protected $email;
    protected $mobile;
    protected $tags;
    protected $lastUpdated;
    protected $template;
    
    /**
     * Constructor.
     * 
     * @param TfValidator $validator Instance of the Tuskfish data validator class.
     */
    function __construct(TfValidator $validator)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        $this->id = 0;
        $this->title = 0;
        $this->firstname = '';
        $this->midname = '';
        $this->lastname = '';
        $this->gender = 3;
        $this->job = '';
        $this->businessUnit = '';
        $this->organisation = '';
        $this->address = '';
        $this->city = '';
        $this->state = '';
        $this->country = 0;
        $this->email = '';
        $this->mobile = '';
        $this->tags = 0;
        $this->lastUpdated = time();
        $this->template = "contact";
    }
    
    /**
     * Converts a contact object to an array suitable for insert/update calls to the database.
     * 
     * @return array Array of object property/values.
     */
    public function convertToArray()
    {
        $keyValues = array();
        
        foreach ($this as $key => $value) {
            $keyValues[$key] = $value;
        }
        
        // Unset non-persistanet properties that are not stored in the contact table.
        unset($keyValues['validator']);
        unset($keyValues['template']);
        
        return $keyValues;
    }
    
    /**
     * Return XSS-escaped properties for display.
     * 
     * @param string $property Name of property to return.
     * @return string XSS-escaped value.
     */
    public function escapeForXss(string $property)
    {
        $cleanProperty = $this->validator->trimString($property);
    
        if (!isset($this->$cleanProperty)) {
            return null;
        }

        return htmlspecialchars($this->$cleanProperty, ENT_NOQUOTES, 'UTF-8', false);
    }
    
    public function loadPropertiesFromArray(array $dirtyInput)
    {
        if (isset($dirtyInput['id'])) $this->setId((int) $dirtyInput['id']);
        if (isset($dirtyInput['title'])) $this->setTitle((int) $dirtyInput['title']);
        if (isset($dirtyInput['firstname'])) $this->setFirstname($dirtyInput['firstname']);
        if (isset($dirtyInput['midname'])) $this->setMidname($dirtyInput['midname']);
        if (isset($dirtyInput['lastname'])) $this->setLastname($dirtyInput['lastname']);
        if (isset($dirtyInput['gender'])) $this->setGender((int) $dirtyInput['gender']);
        if (isset($dirtyInput['job'])) $this->setJob($dirtyInput['job']);
        if (isset($dirtyInput['businessUnit'])) $this->setBusinessUnit($dirtyInput['businessUnit']);
        if (isset($dirtyInput['organisation'])) $this->setOrganisation($dirtyInput['organisation']);
        if (isset($dirtyInput['address'])) $this->setAddress($dirtyInput['address']);
        if (isset($dirtyInput['city'])) $this->setCity($dirtyInput['city']);
        if (isset($dirtyInput['state'])) $this->setState($dirtyInput['state']);
        if (isset($dirtyInput['country'])) $this->setCountry((int) $dirtyInput['country']);
        if (isset($dirtyInput['email'])) $this->setEmail($dirtyInput['email']);
        if (isset($dirtyInput['mobile'])) $this->setMobile($dirtyInput['mobile']);
        if (isset($dirtyInput['tags'])) $this->setTags((int) $dirtyInput['tags']);
        if (isset($dirtyInput['lastUpdated'])) $this->setLastUpdated((int) $dirtyInput['lastUpdated']);
    }
    
    /**
     * Set the ID for this contact.
     * 
     * @param int $id ID of this contact.
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
     * Set the title (salutation) for this contact.
     * 
     * Values: 1. Dr, 2. Prof., 3. Mr., 4. Mrs, 5. Ms. 
     * 
     * @param int $title Title (salutation) of this contact.
     */
    public function setTitle(int $title)
    {
        $cleanTitle = (int) $title;
        
        if ($this->validator->isInt($cleanTitle, 1, 5)) {
            $this->title = $cleanTitle;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the contact's first name.
     * 
     * @param string $firstname First (given) name of the contact.
     */
    public function setFirstname(string $firstname)
    {
        $this->firstname = $this->validator->trimString($firstname);
    }
    
    /**
     * Set the contact's middle name(s).
     * 
     * @param string $midname Middle name(s) of the contact.
     */
    public function setMidname(string $midname)
    {
        $this->midname = $this->validator->trimString($midname);
    }
    
    /**
     * Set the contact's last name.
     * 
     * @param string $lastname Last name (surname) of the contact.
     */
    public function setLastname(string $lastname)
    {
        $this->lastname = $this->validator->trimString($lastname);
    }
    
    /**
     * Set the gender of this contact.
     * 
     * Options: 1. Male, 2. Female. 3. Unknown.
     * 
     * @param int $gender Gender of this contact.
     */
    public function setGender(int $gender)
    {
        $cleanGender = (int) $gender;
        
        if ($this->validator->isInt($cleanGender, 1, 3)) {
            $this->gender = $cleanGender;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the job title for this contact.
     * 
     * @param string $job Job title
     */
    public function setJob(string $job)
    {
        $this->job = $this->validator->trimString($job);
    }
    
    /**
     * Set the business unit for this contact.
     * 
     * Business unit is the immediate organisational unit the contact works for, eg. a department
     * within a larger organisation.
     * 
     * @param string $businessUnit Name of the business unit.
     */
    public function setBusinessUnit(string $businessUnit)
    {
        $this->businessUnit = $this->validator->trimString($businessUnit);
    }
    
    /**
     * Set the affiliation (employer) of this contact.
     * 
     * @param string $organisation Name of the organisation the contact is affiliated with.
     */
    public function setOrganisation(string $organisation)
    {
        $this->organisation = $this->validator->trimString($organisation);
    }
    
    /**
     * Set the business address of this contact.
     * 
     * Enter the street or post office box details, do not enter city as that is a separate field.
     * 
     * @param string $address Business address.
     */
    public function setAddress(string $address)
    {
        $this->address = $this->validator->trimString($address);
    }
    
    /**
     * Set the city name component of the address.
     * 
     * @param string $city City name.
     */
    public function setCity(string $city)
    {
        $this->city = $this->validator->trimString($city);
    }
    
    /**
     * Set the state component of the address.
     * 
     * @param string $state State name.
     */
    public function setState(string $state)
    {
        $this->state = $this->validator->trimString($state);
    }
    
    /**
     * Set the country component of the address.
     * 
     * @param int $country Country name.
     */
    public function setCountry(int $country)
    {
        $cleanCountry = (int) $country;
        
        if ($this->validator->isInt($cleanCountry, 0)) {
            $this->country = $cleanCountry;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the email address of this contact.
     * 
     * @param string $email Email address.
     */
    public function setEmail(string $email)
    {
        $cleanEmail = $this->validator->trimString($email);
        
        if (empty($cleanEmail) || $this->validator->isEmail($cleanEmail)) {
            $this->email = $cleanEmail;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    /**
     * Set the mobile phone number of this contact.
     * 
     * @param string $mobile Mobile phone number.
     */
    public function setMobile(string $mobile)
    {
        $this->mobile = $this->validator->trimString($mobile);
    }
    
    /**
     * Set the tag (activity) this contact was associated with.
     * 
     * @param int $tagId Tag ID.
     */
    public function setTags(int $tagId)
    {
        $cleanTagId = (int) $tagId;
        if ($this->validator->isInt($cleanTagId, 0)) {
            $this->tags = $cleanTagId;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the date/time this contact was last updated.
     * 
     * @param int $lastUpdated Timestamp.
     */
    public function setLastUpdated(int $lastUpdated)
    {
        $cleanLastUpdated = (int) $lastUpdated;
        
        if ($this->validator->isInt($cleanLastUpdated, 1)) {
            $this->lastUpdated = $cleanLastUpdated;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }

}