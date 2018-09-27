<?php

/**
 * TfMachine class file.
 * 
 * @copyright   Simon Wilkinson 2018+(https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@tuskfish.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Represents a remote machine with one or more sensors that logs data to Tuskfish.
 *
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
class TfMachine extends TfDataObject
{
    
    use TfOneTimePad;
    use TfMagicMethods;
    
    protected $validator;
    protected $title = '';
    protected $teaser = '';
    protected $description = '';
    protected $latitude = '';
    protected $longitude = '';
    protected $key = '';
    
    public function __construct(TfValidator $validator)
    {
        if (!is_a($validator, 'TfValidator')) {
            trigger_error(TFISH_ERROR_NOT_VALIDATOR, E_USER_ERROR);
        }
        
        $this->validator = $validator;
        $this->id = 0;
        $this->online = 0;
        $this->submissionTime = 0;
        $this->lastUpdated = 0;
        $this->counter = 0;
        $this->handler = 'TfMachineHandler';
        $this->template = 'machine';
        $this->module = 'machines';
        $this->icon = '<i class="fas fa-hdd"></i>';
    }
    
    /**
     * Converts a machine object to an array suitable for insert/update calls to the database.
     * 
     * @return array Array of object property/values.
     */
    public function convertObjectToArray()
    {        
        $keyValues = array();
        
        foreach ($this as $key => $value) {
            $keyValues[$key] = $value;
        }
        
        // Unset non-persistanet properties that are not stored in the machine table.
        unset(
            $keyValues['modulo'],
            $keyValues['ascii_offset'],
            $keyValues['icon'],
            $keyValues['handler'],
            $keyValues['module'],
            $keyValues['template']
        );
        
        return $keyValues;
    }
    
    /**
     * Returns a whitelist of object properties whose values are allowed be set.
     * 
     * This function is used to build a list of $allowedVars for a machine object. Child classes
     * use this list to unset properties they do not use. Properties that are not resident in the
     * database are also unset here (handler, template, module and icon).
     * 
     * @return array Array of object properties as keys.
     */
    public function getPropertyWhitelist()
    {        
        $properties = array();
        
        foreach ($this as $key => $value) {
            $properties[$key] = '';
        }
        
        unset($properties['handler'], $properties['template'], $properties['module'],
                $properties['icon']);
        
        return $properties;
    }
    
    /**
     * Populates the properties of the object from external (untrusted) data source.
     * 
     * Note that values are internally validated by the relevant setters.
     * 
     * @param array $dirtyInput Usually raw form $_REQUEST data.
     * @param bool $liveUrls Convert base url to TFISH_LINK (true) or TFISH_LINK to base url (false).
     */
    public function loadPropertiesFromArray(array $dirtyInput, $liveUrls = true)
    {
        $this->loadProperties($dirtyInput);
        
        // Convert URLs back to TFISH_LINK for insertion or update, to aid portability.
        if (isset($this->teaser) && !empty($dirtyInput['teaser'])) {
            $teaser = $this->convertBaseUrlToConstant($dirtyInput['teaser'], $liveUrls);            
            $this->setTeaser($teaser);
        }
        
        if (isset($this->description) && !empty($dirtyInput['description'])) {
            $description = $this->convertBaseUrlToConstant($dirtyInput['description'], $liveUrls);            
            $this->setDescription($description);
        }
    }
        
    /**
     * Assign form data to a machine object.
     * 
     * Note that data validation is carried out internally via the setters. This is a helper method
     * for loadPropertiesFromArray().
     * 
     * @param array $dirtyInput Array of untrusted form input.
     */
    private function loadProperties(array $dirtyInput)
    {
        if (isset($this->id) && isset($dirtyInput['id']))
            $this->setId((int) $dirtyInput['id']);
        if (isset($this->title) && $dirtyInput['title'])
            $this->setTitle((string) $dirtyInput['title']);
        if (isset($this->teaser) && $dirtyInput['teaser'])
            $this->setTeaser((string) $dirtyInput['teaser']);
        if (isset($this->description) && $dirtyInput['description'])
            $this->setDescription((string) $dirtyInput['description']);
        if (isset($this->latitude) && $dirtyInput['latitude'])
            $this->setLatitude((float) $dirtyInput['latitude']);
        if (isset($this->longitude) && $dirtyInput['longitude'])
            $this->setLongitude((float) $dirtyInput['longitude']);
        if (isset($this->submissionTime) && isset($dirtyInput['submissionTime']))
            $this->setSubmissionTime((int) $dirtyInput['submissionTime']);
        if (isset($this->lastUpdated) && isset($dirtyInput['lastUpdated']))
            $this->setLastUpdated((int) $dirtyInput['lastUpdated']);
        if (isset($this->counter) && isset($dirtyInput['counter']))
            $this->setCounter((int) $dirtyInput['counter']);
        if (isset($this->online) && isset($dirtyInput['online']))
            $this->setOnline((int) $dirtyInput['online']);
        if (isset($this->key) && $dirtyInput['key'])
            $this->setKey((string) $dirtyInput['key']);
        if (isset($this->metaTitle) && isset($dirtyInput['metaTitle']))
            $this->setMetaTitle((string) $dirtyInput['metaTitle']);
        if (isset($this->metaDescription) && isset($dirtyInput['metaDescription']))
            $this->setMetaDescription((string) $dirtyInput['metaDescription']);
        if (isset($this->seo) && isset($dirtyInput['seo']))
            $this->setSeo((string) $dirtyInput['seo']);
    }
    
    /**
     * Returns the title of this machine XSS escaped for display.
     * 
     * @return string Title.
     */
    public function getTitle()
    {
        return $this->validator->escapeForXSS($this->title);
    }
    
    /**
     * Set the title of this machine.
     * 
     * @param string $title Title of this object.
     */
    public function setTitle(string $title)
    {
        $this->title = $this->validator->trimString($title);
    }
    
    /**
     * Return the teaser of this machine (prevalidated HTML).
     * 
     * Do not escape HTML for front end display, as HTML properties are input validated with
     * HTMLPurifier. However, you must escape HTML properties when editing a machine; this is
     * because TinyMCE requires entities to be double escaped for storage (this is a specification
     * requirement) or they will not display property.
     * 
     * @param bool $escapeHtml True to escape HTML, false to return unescaped HTML.
     * @return string Teaser (short form description) of this machine as HTML.
     */
    public function getTeaser($escapeHtml = false)
    {
        // Output HTML for display: Do not escape as it has been input filtered with HTMLPurifier.        
        if ($escapeHtml === false) {
            return $this->teaser;
        }
        
        // Output for display in the TinyMCE editor (editing only).
        if ($escapeHtml === true) {    
            return htmlspecialchars($this->teaser, ENT_NOQUOTES, 'UTF-8', true);
        }
    }
    
    /**
     * Set the teaser (short form description) for this machine.(HTML).
     * 
     * @param string $teaser Teaser (in HTML).
     */
    public function setTeaser(string $teaser) // HTML
    {
        $teaser = $this->validator->trimString($teaser);
        $this->teaser = $this->validator->filterHtml($teaser);
    }
    
    /**
     * Return the description of this machine (prevalidated HTML).
     * 
     * Do not escape HTML for front end display, as HTML properties are input validated with
     * HTMLPurifier. However, you must escape HTML properties when editing a machine; this is
     * because TinyMCE requires entities to be double escaped for storage (this is a specification
     * requirement) or they will not display property.
     * 
     * @param bool $escapeHtml True to escape HTML, false to return unescaped HTML.
     * @return string Description of this machine as HTML.
     */
    public function getDescription($escapeHtml = false)
    {
        // Output HTML for display: Do not escape as it has been input filtered with HTMLPurifier.        
        if ($escapeHtml === false) {
            return $this->description;
        }
        
        // Output for display in the TinyMCE editor (editing only).
        if ($escapeHtml === true) {    
            return htmlspecialchars($this->description, ENT_NOQUOTES, 'UTF-8', true);
        }
    }
    
    /**
     * Set the description of this object (HTML field).
     * 
     * @param string $description Description in HTML.
     */
    public function setDescription(string $description) // HTML
    {
        $description = $this->validator->trimString($description);
        $this->description = $this->validator->filterHtml($description);
    }
    
    /**
     * Return the latitude of this machine XSS escaped for display.
     * 
     * @return string Latitude.
     */
    public function getLatitude()
    {
        return $this->validator->escapeForXss($this->latitude);
    }
    
    /**
     * Set the latitude coordinate of this machine.
     * 
     * @param float $latitude Latitude bounded by +/- 90 degrees.
     */
    public function setLatitude (float $latitude)
    {
        $cleanLatitude = (float) $latitude;
        
        if ($cleanLatitude <= 90.0 && $cleanLatitude >= -90.0) {
            $this->latitude = $cleanLatitude;
        } else {
            trigger_error(TFISH_ERROR_BAD_LATITUDE, E_USER_ERROR);
        }
    }
    
    /**
     * Return the longitude of this machine XSS escaped for display.
     * 
     * @return string Longitude.
     */
    public function getLongitude()
    {
        return $this->validator->escapeForXss($this->longitude);
    }
    
    /**
     * Set the longitudinal coordinate of this machine.
     * 
     * @param float $longitude Longitude bounded by +/180 degrees.
     */
    public function setLongitude(float $longitude)
    {
        $cleanLongitude = (float) $longitude;
        
        if ($cleanLongitude <= 180.0 && $cleanLongitude >= -180.0) {
            $this->longitude = $cleanLongitude;
        } else {
            trigger_error(TFISH_ERROR_BAD_LONGITUDE, E_USER_ERROR);
        }
    }
    
    /**
     * Set the HMAC key for this machine.
     * 
     * @param string $key HMAC key.
     */
    public function setKey(string $key)
    {
        $this->key = $this->validator->trimString($key);
    }
    
    /**
     * Return the HMAC key for this machine.
     * 
     * @return string HMAC key
     */
    public function getKey()
    {
        return $this->validator->escapeForXss($this->key);
    }
    
}
