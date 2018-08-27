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
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
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
            $keyValues['icon'],
            $keyValues['handler'],
            $keyValues['module'],
            $keyValues['template']
        );
        
        return $keyValues;
    }
    
    /**
     * Escapes object properties for output to browser.
     * 
     * Use this method to retrieve object properties when you want to send them to the browser.
     * They will be automatically escaped with htmlspecialchars() to mitigate cross-site scripting
     * attacks.
     * 
     * Note that the method excludes the teaser and description fields by default, which are 
     * returned unescaped; these are dedicated HTML fields that have been input-validated
     * with the HTMLPurifier library, and so *should* be safe. However, when editing these fields
     * it is necessary to escape them in order to prevent TinyMCE deleting them, as the '&' part of
     * entity encoding also needs to be escaped when in a textarea for some highly annoying reason.
     * 
     * @param string $property Name of property.
     * @param bool $escapeHtml Whether to escape HTML fields (teaser, description).
     * @return string|null Human readable value escaped for display or null if property does not
     * exist.
     */
    public function escapeForXss(string $property, bool $escapeHtml = false)
    {
        $cleanProperty = $this->validator->trimString($property);
        
        // If property is not set return null.
        if (!isset($this->$cleanProperty)) {
            return null;
        }
        
        // Format all data for display and convert TFISH_LINK to URL.
        $humanReadableData = (string) $this->makeDataHumanReadable($cleanProperty);
        $htmlFields = array('teaser', 'description', 'icon');
        
        // Output HTML for display: Do not escape as it has been input filtered with HTMLPurifier.
        if (in_array($property, $htmlFields, true) && $escapeHtml === false) {
            return $humanReadableData;
        }
        
        // Output for display in the TinyMCE editor (edit mode): HTML must be DOUBLE
        // escaped to meet specification requirements.
        if (in_array($property, $htmlFields, true) && $escapeHtml === true) {    
            return htmlspecialchars($humanReadableData, ENT_NOQUOTES, 'UTF-8', 
                    true);
        }
                
        // All other cases: Escape data for display.        
        return htmlspecialchars($humanReadableData, ENT_NOQUOTES, 'UTF-8', false);
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
     * Note that the supplied data is internally validated by __set().
     * 
     * @param array $dirtyInput Usually raw form $_REQUEST data.
     * @param bool $liveUrls Convert base url to TFISH_LINK (true) or TFISH_LINK to base url (false).
     */
    public function loadPropertiesFromArray(array $dirtyInput, $liveUrls = true)
    {
        $propertyWhitelist = $this->getPropertyWhitelist();

        foreach ($propertyWhitelist as $key => $value) {
            if (array_key_exists($key, $dirtyInput)) {
                $this->__set($key, $dirtyInput[$key]);
            }
            unset($key);
        }
        // Convert URLs back to TFISH_LINK for insertion or update, to aid portability.
        if (array_key_exists('teaser', $propertyWhitelist) && !empty($dirtyInput['teaser'])) {
            
            if ($liveUrls === true) {
                $teaser = str_replace(TFISH_LINK, 'TFISH_LINK', $dirtyInput['teaser']);
            } else {
                $teaser = str_replace('TFISH_LINK', TFISH_LINK, $dirtyInput['teaser']);
            }
            
            $this->setTeaser($teaser);
        }

        if (array_key_exists('description', $propertyWhitelist)
                && !empty($dirtyInput['description'])) {
            
            if ($liveUrls === true) {
                $description = str_replace(TFISH_LINK, 'TFISH_LINK', $dirtyInput['description']);
            } else {
                $description = str_replace('TFISH_LINK', TFISH_LINK, $dirtyInput['description']);
            }
            
            $this->setDescription($description);
        }
    }
    
    /**
     * Converts properties to human readable form in preparation for output.
     * 
     * Note that data processed by this function must be escaped for XSS before being sent to
     * display. You can use escapeForXSS().
     * 
     * @param string $property Name of property.
     * @return string Property formatted to human readable form for output.
     */
    protected function makeDataHumanReadable(string $cleanProperty)
    {        
        switch ($cleanProperty) {
            case "description":
            case "teaser":
                // Do a simple string replace to allow TFISH_URL to be used as a constant,
                // making the site portable.
                $tfUrlEnabled = str_replace('TFISH_LINK', TFISH_LINK,
                        $this->$cleanProperty);

                return $tfUrlEnabled; 
                break;
            
            case "latitude":
            case "longitude":
                return $this->cleanProperty; // Todo: Write function to convert decimal degrees.
                break;

            case "submissionTime":
            case "lastUpdated":
                $date = date('j F Y', $this->$cleanProperty);

                return $date;
                break;
                
            // No special handling required. Return unmodified value.
            default:
                return $this->$cleanProperty;
                break;
        }
    }
    
    /**
     * Intercept direct setting of properties to permit data validation.
     * 
     * It is best to set properties using the relevant setter method directly, as it is more
     * efficient, but when bulk loading from an array (database row or $_REQUEST) this is useful.
     * Note that validation of values is handled internally by the relevant setters.
     * 
     * @param string $property Name of property.
     * @param mixed $value Value of property.
     */
    public function __set($property, $value)
    {
        $cleanProperty = $this->validator->trimString($property);
        
        if (isset($this->$cleanProperty)) {
        
            switch ($cleanProperty) {
                case "id":
                    $this->setId((int) $value);
                    break;
                case "title":
                    $this->setTitle((string) $value);
                    break;
                case "teaser":
                    $this->setTeaser((string) $value);
                    break;
                case "description":
                    $this->setDescription((string) $value);
                    break;
                case "latitude":
                    $this->setLatitude((float) $value);
                    break;
                case "longitude":
                    $this->setLongitude((float) $value);
                    break;
                case "online":
                    $this->setOnline((int) $value);
                    break;
                case "submissionTime":
                    $this->setSubmissionTime((int) $value);
                    break;
                case "lastUpdated":
                    $this->setLastUpdated((int) $value);
                    break;
                case "counter":
                    $this->setCounter((int) $value);
                    break;
                case "key":
                    $this->setKey((string) $value);
                    break;
                case "metaTitle":
                    $this->setMetaTitle((string) $value);
                    break;
                case "metaDescription":
                    $this->setMetaDescription((string) $value);
                    break;
                case "seo":
                    $this->setSeo((string) $value);
                    break;
            }
        }  else {
            // Not a permitted property, do not set.
        }
    }
    
    /**
     * Set the ID for this object.
     * 
     * @param int $id ID of this object.
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
     * Set the title of this machine.
     * 
     * @param string $title Title of this object.
     */
    public function setTitle(string $title)
    {
        $this->title = $this->validator->trimString($title);
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
    
    public function setKey(string $key)
    {
        $this->key = $this->validator->trimString($key);
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
     * Set the meta title for this object.
     * 
     * @param string $metaTitle Meta title for this object.
     */
    public function setMetaTitle(string $metaTitle)
    {
        $this->metaTitle = $this->validator->trimString($metaTitle);
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
