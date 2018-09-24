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
class TfMachine
{
    
    use TfOneTimePad;
    use TfMagicMethods;
    
    protected $validator;
    protected $id = '';
    protected $title = '';
    protected $teaser = '';
    protected $description = '';
    protected $latitude = '';
    protected $longitude = '';
    protected $online = '';
    protected $submissionTime = '';
    protected $lastUpdated = '';
    protected $counter = '';
    protected $key = '';
    protected $metaTitle = '';
    protected $metaDescription = '';
    protected $seo = '';
    protected $handler = 'TfMachineHandler';
    protected $template = 'machine';
    protected $module = 'machines';
    protected $icon = '<i class="fas fa-hdd"></i>';
    
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
     * Convert URLs back to TFISH_LINK and back for insertion or update, to aid portability.
     * 
     * This is a helper method for loadPropertiesFromArray(). Only useful on HTML fields. Basically
     * it converts the base URL of your site to the TFISH_LINK constant for storage or vice versa
     * for display. If you change the base URL of your site (eg. domain) all your internal links
     * will automatically update when they are displayed.
     * 
     * @param string $html A HTML field that makes use of the TFISH_LINK constant.
     * @param bool $liveUrls Flag to convert urls to constants (true) or constants to urls (false).
     * @return string HTML field with converted URLs.
     */
    private function convertBaseUrlToConstant(string $html, bool $liveUrls = false)
    {
        if ($liveUrls === true) {
            $html = str_replace(TFISH_LINK, 'TFISH_LINK', $html);
        } else {
                $html = str_replace('TFISH_LINK', TFISH_LINK, $html);
        }
        
        return $html;
    }

    
    /**
     * Returns the ID for this machine.
     * 
     * @return int ID of this machine.
     */
    public function getId()
    {
        return (int) $this->id;
    }
    
    /**
     * Set the ID for this machine XSS safe.
     * 
     * @param int $id ID of this machine.
     */
    public function setId(int $id)
    {
        if ($this->validator->isInt($id, 0)) {
            $this->id = $id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
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
     * Returns the online status of this machine as a boolean value, XSS safe.
     * 
     * @return boolean True if online, false if not.
     */
    public function getOnline()
    {
        if ($this->online === 1) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Set this machine as online (1) or offline (0).
     * 
     * Offline objects are not displayed on the front end or returned in search results.
     * 
     * @param int $online Online (1) or offline (0).
     */
    public function setOnline(int $online)
    {
        if ($this->validator->isInt($online, 0, 1)) {
            $this->online = $online;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Return formatted date that this machine was submitted.
     * 
     * @return string Date/time of submission.
     */
    public function getSubmissionTime()
    {
        $date = date('j F Y', $this->$submissionTime);
        return $this->validator->escapeForXss($date);
    }
    
    /**
     * Set the submission time for this machine (timestamp).
     * 
     * @param int $submissionTime Timestamp.
     */
    public function setSubmissionTime(int $submissionTime)
    {
        if ($this->validator->isInt($submissionTime, 1)) {
            $this->submissionTime = $submissionTime;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Return formatted date/time this machine was last updated, escaped for display.
     * 
     * @return string Date/time last updated.
     */
    public function getlastUpdated()
    {
        $date = date('j F Y', $this->$lastUpdated);
        return $this->validator->escapeForXss($date);
    }
    
    /**
     * Set the last updated time for this machine (timestamp).
     * 
     * @param int $lastUpdated Timestamp.
     */
    public function setLastUpdated(int $lastUpdated)
    {
        if ($this->validator->isInt($lastUpdated, 0)) {
            $this->lastUpdated = $lastUpdated;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
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
    
    /**
     * Returns the number of times this machine was viewed, XSS safe.
     * 
     * @return int View counter.
     */
    public function getCounter()
    {
        return (int) $this->counter;
    }
    
    /**
     * Set the view counter for this machine.
     * 
     * @param int $counter Counter value.
     */
    public function setCounter(int $counter)
    {
        if ($this->validator->isInt($counter, 0)) {
            $this->counter = $counter;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Returns the meta title for this machine XSS escaped for display.
     * 
     * @return string Meta title.
     */
    public function getMetaTitle()
    {
        return $this->validator->escapeForXss($this->metaTitle);
    }
    
    /**
     * Set the meta title for this object.
     * 
     * @param string $metaTitle Meta title for this object.
     */
    public function setMetaTitle(string $metaTitle)
    {
        $this->metaTitle = $this->validator->trimString($metaTitle);
    }
    
    /**
     * Return the meta description of this machine XSS escaped for display.
     * 
     * @return string Meta description.
     */
    public function getMetaDescription()
    {
        return $this->validator->escapeForXss($this->metaDescription);
    }
    
    /**
     * Set the meta description for this machine.
     * 
     * @param string $metaDescription Meta description of this object.
     */
    public function setMetaDescription(string $metaDescription)
    {
        $this->metaDescription = $this->validator->trimString($metaDescription);
    }
    
    /**
     * Return the SEO string for this machine XSS escaped for display.
     * 
     * @return string SEO-friendly URL string.
     */
    public function getSeo()
    {
        return $this->validator->escapeForXss($this->seo);
    }
    
    /**
     * Set the SEO-friendly search string for this machine.
     * 
     * The SEO string will be appended to the URL for this object.
     * 
     * @param string $seo Dash-separated-title-of-this-object.
     */
    public function setSeo(string $seo)
    {
        $cleanSeo = $this->validator->trimString($seo);
        $cleanSeo = str_replace(' ', '-', $cleanSeo);        
        $this->seo = $cleanSeo;
    }
    
    /**
     * Set the handler class for this machine type.
     * 
     * @param string $handler Handler name (alphabetical characters only).f
     */
    public function setHandler(string $handler)
    {
        $cleanHandler = $this->validator->trimString($handler);

        if ($this->validator->isAlpha($cleanHandler)) {
            $this->handler = $cleanHandler;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
        }
    }
    
    /**
     * Set the template file for displaying this machine.
     * 
     * The equivalent HTML template file must be present in the active theme.
     * 
     * @param string $template Template filename without extension, eg. 'camera'.
     */
    public function setTemplate(string $template)
    {
        $cleanTemplate = $this->validator->trimString($template);

        if ($this->validator->isAlnumUnderscore($cleanTemplate)) {
            $this->template = $cleanTemplate;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
        }
    }
    
    /**
     * Set the module for this machine.
     * 
     * Usually handled by the machine's constructor.
     * 
     * @param string $module Module name (alphabetical characters only).
     */
    public function setModule(string $module)
    {
        $cleanModule = $this->validator->trimString($module);
        
        if ($this->validator->isAlpha($module)) {
            $this->module = $cleanModule;
        }
    }
    
    /**
     * Returns the Font Awesome icon for this machine, XSS safe (prevalidated with HTMLPurifier).
     * 
     * @return string FontAwesome icon for this machine (HTML).
     */
    public function getIcon()
    {
        return $this->icon;
    }
    
    /**
     * Set the icon for this machine.
     * 
     * This is a HTML field.
     * 
     * @param string $icon Icon expressed as a FontAwesome tag, eg. '<i class="fas fa-file-alt"></i>'
     */
    public function setIcon(string $icon) // HTML
    {
        $icon = $this->validator->trimString($icon);
        $this->icon = $this->validator->filterHtml($icon);
    }
    
    /**
     * Reset the last updated time for this sensor object (timestamp).
     */
    public function updateLastUpdated()
    {
        $this->lastUpdated = time();
    }
}
