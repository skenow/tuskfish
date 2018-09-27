<?php

/**
 * TfSensor class file.
 * 
 * @copyright   Simon Wilkinson 2018+(https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@tuskfish.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Represents a remote sensor that collects data, typically an Internet of Things device.
 *
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
class TfSensor extends TfDataObject
{
    use TfOneTimePad;
    use TfMagicMethods;
    
    protected $validator;
    protected $type = '';
    protected $protocol = '';
    protected $title = '';
    protected $teaser = '';
    protected $description = '';
    protected $parent = '';
    
    public function __construct(TfValidator $validator)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator;
        } else {
            trigger_error(TFISH_ERROR_NOT_VALIDATOR, E_USER_ERROR);
        }
        
        $this->lastUpdated = 0;
        $this->counter = 0;
        $this->handler = 'TfSensorHandler';
        $this->template = 'sensor';
        $this->module = 'machines';
        $this->icon = '<i class="fas fa-thermometer-empty"></i>';
    }
    
    /**
     * Converts a sensor object to an array suitable for insert/update calls to the database.
     * 
     * @return array Array of object property/values.
     */
    public function convertObjectToArray()
    {        
        $keyValues = array();
        
        foreach ($this as $key => $value) {
            $keyValues[$key] = $value;
        }
        
        // Unset non-persistanet properties that are not stored in the sensor table.
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
     * This function is used to build a list of $allowedVars for a sensor object. Child classes
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
     * Note that the supplied data is internally validated by __set().
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
     * Assign form data to sensor object.
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
        if (isset($this->type) && isset($dirtyInput['type']))
            $this->setType((string) $dirtyInput['type']);
        if (isset($this->protocol) && $dirtyInput['protocol'])
            $this->setProtocol((string) $dirtyInput['protocol']);
        if (isset($this->title) && $dirtyInput['title'])
            $this->setTitle((string) $dirtyInput['title']);
        if (isset($this->teaser) && $dirtyInput['teaser'])
            $this->setTeaser((string) $dirtyInput['teaser']);
        if (isset($this->description) && $dirtyInput['description'])
            $this->setDescription((string) $dirtyInput['description']);
        if (isset($this->parent) && $dirtyInput['parent'])
            $this->setParent((int) $dirtyInput['parent']);
        if (isset($this->submissionTime) && isset($dirtyInput['submissionTime']))
            $this->setSubmissionTime((int) $dirtyInput['submissionTime']);
        if (isset($this->lastUpdated) && isset($dirtyInput['lastUpdated']))
            $this->setLastUpdated((int) $dirtyInput['lastUpdated']);
        if (isset($this->counter) && isset($dirtyInput['counter']))
            $this->setCounter((int) $dirtyInput['counter']);
        if (isset($this->online) && isset($dirtyInput['online']))
            $this->setOnline((int) $dirtyInput['online']);
        if (isset($this->metaTitle) && isset($dirtyInput['metaTitle']))
            $this->setMetaTitle((string) $dirtyInput['metaTitle']);
        if (isset($this->metaDescription) && isset($dirtyInput['metaDescription']))
            $this->setMetaDescription((string) $dirtyInput['metaDescription']);
        if (isset($this->seo) && isset($dirtyInput['seo']))
            $this->setSeo((string) $dirtyInput['seo']);
    }
    
    /**
     * Set the type of sensor.
     * 
     * Type must be the name of a sensor subclass.
     * 
     * @param string $type Class name for this sensor.
     */
    public function setType(string $type)
    {
        $cleanType = $this->validator->trimString($type);

        if ($this->validator->isAlpha($cleanType)) {
            $this->type = $cleanType;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
        }
    }
    
    /**
     * Return the type of sensor, XSS escaped for display.
     * 
     * @return string Type of sensor (class name).
     */
    public function getType()
    {
        return $this->validator->escapeForXss($this->type);
    }
    
    /**
     * Set the protocol that this sensor speaks.
     * 
     * @param string $protocol The data prototol the sensor responds in.
     */
    public function setProtocol(string $protocol)
    {
        $cleanProtocol = $this->validator->trimString($protocol);
        if ($this->validator->isAlpha($cleanProtocol)) {
            $this->protocol = $cleanProtocol;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
        }
    }
    
    /**
     * Returns the communications protocol spoken by this machine XSS escaped for display.
     * 
     * @return string Communications protocol spoken by this machine.
     */
    public function getProtocol()
    {
        return $this->validator->escapeForXss($this->protocol);
    }
    
    /**
     * Set the title of this sensor.
     * 
     * @param string $title Title of this object.
     */
    public function setTitle(string $title)
    {
        $this->title = $this->validator->trimString($title);
    }
    
    /**
     * Returns the title of this sensor XSS escaped for display.
     * 
     * @return string Title
     */
    public function getTitle()
    {
        return $this->validator->escapeForXSS($this->title);
    }
    
    /**
     * Set the teaser (short form description) for this sensor.(HTML).
     * 
     * @param string $teaser Teaser (in HTML).
     */
    public function setTeaser(string $teaser) // HTML
    {
        $teaser = $this->validator->trimString($teaser);
        $this->teaser = $this->validator->filterHtml($teaser);
    }
    
    /**
     * Return the teaser (short form description) of this machine (prevalidated HTML, XSS safe).
     * 
     * Do not escape HTML for front end display, as HTML properties are input validated with
     * HTMLPurifier. However, you must escape HTML properties when editing a sensor; this is
     * because TinyMCE requires entities to be double escaped for storage (this is a specification
     * requirement) or they will not display property.
     * 
     * @param bool $escapeHtml True to escape HTML, false to return unescaped HTML.
     * @return string Short form description of sensor as HTML.
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
     * Return the description of this sensor (prevalidated HTML, XSS safe).
     * 
     * Do not escape HTML for front end display, as HTML properties are input validated with
     * HTMLPurifier. However, you must escape HTML properties when editing a sensor; this is
     * because TinyMCE requires entities to be double escaped for storage (this is a specification
     * requirement) or they will not display property.
     * 
     * @param bool $escapeHtml True to escape HTML, false to return unescaped HTML.
     * @return string Description of sensor as HTML.
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
     * Set the ID of the parent for this object (must be a collection).
     * 
     * Parent ID must be different to sensor ID (cannot declare self as parent).
     * 
     * @param int $parent ID of parent object.
     */
    public function setParent (int $parent)
    {
        if (!$this->validator->isInt($parent, 0)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        $this->parent = $parent;
    }
    
    /**
     * Return the ID of the parent object, XSS safe.
     * 
     * @return int ID of parent.
     */
    public function getParent()
    {
        return (int) $this->parent;
    }
        
}
