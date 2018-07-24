<?php

/**
 * TfPreference class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Holds Tuskfish site configuration data.
 * 
 * A preference object is automatically instantiated on every page via tfHeader.php.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
 * @property    string $siteName Name of website.
 * @property    string $siteDescription Meta description of website.
 * @property    string $siteAuthor Author of website.
 * @property    string $siteEmail Administrative contact email for website.
 * @property    string $siteCopyright Copyright notice.
 * @property    int $closeSite Toggle to close this site.
 * @property    string $serverTimezone Timezone of server location.
 * @property    string $siteTimezone Timezone for main audience location.
 * @property    int $minSearchLength Minimum length of search terms.
 * @property    int searchPagination Number of search results to show on a page.
 * @property    int userPagination Number of content objects to show on public index page.
 * @property    int adminPagination Number of content objects to show on admin index page.
 * @property    int galleryPagination Number of images to show in admin gallery.
 * @property    int paginationElements Number of slots to include on pagination controls.
 * @property    string session_name Name of session.
 * @property    int session_life Expiry timer for inactive sessions (minutes).
 * @property    string defaultLanguage Default language of site.
 * @property    string dateFormat Format to display dates, as per PHP date() function.
 * @property    int enableCache Enable site cache.
 * @property    int cacheLife Expiry timer for site cache (seconds).
 */
class TfPreference
{
    
    use TfMagicMethods;
    use TfLanguage;

    protected $validator;
    protected $siteName;
    protected $siteDescription;
    protected $siteAuthor;
    protected $siteEmail;
    protected $siteCopyright;
    protected $closeSite;
    protected $serverTimezone;
    protected $siteTimezone;
    protected $minSearchLength;
    protected $searchPagination;
    protected $userPagination;
    protected $adminPagination;
    protected $galleryPagination;
    protected $rssPosts;
    protected $paginationElements;
    protected $session_name;
    protected $session_life;
    protected $defaultLanguage;
    protected $dateFormat;
    protected $enableCache;
    protected $cacheLife;
    
    function __construct(TfValidator $tfValidator, array $tfPreferences)
    {
        $this->validator = $tfValidator;
        $this->loadPropertiesFromArray($tfPreferences);
    }
    
    /**
     * Escape a property for on-screen display to prevent XSS.
     * 
     * Applies htmlspecialchars() to a property destined for display to mitigate XSS attacks.
     * Note that preference values should not be directly assigned to meta tags; they should be
     * assigned to $tfMetadata instead, which will handle any escaping necessary.
     * 
     * @param string $property Name of property.
     * @return string Value of property escaped for display.
     */
    public function escapeForXss(string $property)
    {
        $clean_property = $this->validator->trimString($property);
        
        if (isset($this->$clean_property)) {
            return htmlspecialchars($this->$clean_property, ENT_QUOTES, 'UTF-8', false);
        } else {
            return null;
        }
    }
    
    /**
     * Converts the preference object to an array suitable for insert/update calls to the database.
     * 
     * @return array Array of object property/values.
     */
    public function getPreferencesAsArray()
    {
        $key_values = array();
        
        foreach ($this as $key => $value) {
            $key_values[$key] = $value;
        }
        
        return $key_values;
    }
    
    /**
     * Update the preference object from an external data source (eg. form submission).
     * 
     * The preference object will conduct its own internal data type validation and range checks.
     * 
     * @param array $dirty_input Usually $_REQUEST data.
     */
    public function loadPropertiesFromArray(array $dirty_input)
    {
        if (!$this->validator->isArray($dirty_input)) {
            trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
        }
        
        // Validate object properties as they are assigned.
        if (isset($dirty_input['siteName'])) $this->setSiteName($dirty_input['siteName']);
        if (isset($dirty_input['siteDescription'])) $this->setSiteDescription($dirty_input['siteDescription']);
        if (isset($dirty_input['siteAuthor'])) $this->setSiteAuthor($dirty_input['siteAuthor']);
        if (isset($dirty_input['siteEmail'])) $this->setSiteEmail($dirty_input['siteEmail']);
        if (isset($dirty_input['siteCopyright'])) $this->setSiteCopyright($dirty_input['siteCopyright']);
        if (isset($dirty_input['closeSite'])) $this->setCloseSite((int) $dirty_input['closeSite']);
        if (isset($dirty_input['serverTimezone'])) $this->setServerTimezone($dirty_input['serverTimezone']);
        if (isset($dirty_input['siteTimezone'])) $this->setSiteTimezone($dirty_input['siteTimezone']);
        if (isset($dirty_input['minSearchLength'])) $this->setMinSearchLength((int) $dirty_input['minSearchLength']);
        if (isset($dirty_input['searchPagination'])) $this->setSearchPagination((int) $dirty_input['searchPagination']);
        if (isset($dirty_input['userPagination'])) $this->setUserPagination((int) $dirty_input['userPagination']);
        if (isset($dirty_input['adminPagination'])) $this->setAdminPagination((int) $dirty_input['adminPagination']);
        if (isset($dirty_input['galleryPagination'])) $this->setGalleryPagination((int) $dirty_input['galleryPagination']);
        if (isset($dirty_input['rssPosts'])) $this->setRssPosts((int) $dirty_input['rssPosts']);
        if (isset($dirty_input['paginationElements'])) $this->setPaginationElements((int) $dirty_input['paginationElements']);
        if (isset($dirty_input['session_name'])) $this->setSessionName($dirty_input['session_name']);
        if (isset($dirty_input['session_life'])) $this->setSessionLife((int) $dirty_input['session_life']);
        if (isset($dirty_input['defaultLanguage'])) $this->setDefaultLanguage($dirty_input['defaultLanguage']);
        if (isset($dirty_input['dateFormat'])) $this->setDateFormat($dirty_input['dateFormat']);
        if (isset($dirty_input['enableCache'])) $this->setEnableCache((int) $dirty_input['enableCache']);
        if (isset($dirty_input['cacheLife'])) $this->setCacheLife((int) $dirty_input['cacheLife']);
    }

    public function setAdminPagination(int $value)
    {
        $clean_value = (int) $value;
        
        if ($this->validator->isInt($clean_value, 1)) {
            $this->adminPagination = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setSiteAuthor(string $value)
    {
        $this->siteAuthor = $this->validator->trimString($value);
    }
    
    public function setCacheLife(int $value)
    {
        $clean_value = (int) $value;
        
        if ($this->validator->isInt($clean_value, 1)) {
            $this->cacheLife = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setCloseSite(int $value)
    {
        $clean_value = (int) $value;
        
        if ($this->validator->isInt($clean_value, 0, 1)) {
            $this->closeSite = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setDateFormat(string $value)
    {
        $this->dateFormat = $this->validator->trimString($value);
    }
    
    public function setDefaultLanguage(string $value)
    {
        $clean_value = $this->validator->trimString($value);
        
        if (!$this->validator->isAlpha($clean_value)) {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
        }

        $language_whitelist = $this->getListOfLanguages();

        if (array_key_exists($clean_value, $language_whitelist)) {
            $this->defaultLanguage = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
        }
    }
    
    public function setSiteDescription(string $value)
    {
        $this->siteDescription = $this->validator->trimString($value);
    }
    
    public function setSiteEmail(string $value)
    {
        $clean_value = $this->validator->trimString($value);

        if ($this->validator->isEmail($clean_value)) {
            $this->siteEmail = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    public function setEnableCache(int $value)
    {
        $clean_value = (int) $value;
        
        if ($this->validator->isInt($clean_value, 0, 1)) {
            $this->enableCache = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setGalleryPagination(int $value)
    {
        $clean_value = (int) $value;
        
        if ($this->validator->isInt($clean_value, 1)) {
            $this->galleryPagination = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setMinSearchLength(int $value)
    {
        $clean_value = (int) $value;
        
        if ($this->validator->isInt($clean_value, 3)) {
            $this->minSearchLength = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setPaginationElements(int $value)
    {
        $clean_value = (int) $value;
        
        if ($this->validator->isInt($clean_value, 3)) {
            $this->paginationElements = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setRssPosts(int $value)
    {
        $clean_value = (int) $value;
        
        if ($this->validator->isInt($clean_value, 1)) {
            $this->rssPosts = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setSearchPagination(int $value)
    {
        $clean_value = (int) $value;
        
        if ($this->validator->isInt($clean_value, 0)) {
            $this->searchPagination = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setServerTimezone(string $value)
    {
        $this->serverTimezone = $this->validator->trimString($value);
    }
    
    public function setSessionLife(int $value)
    {
        $clean_value = (int) $value;
        
        if ($this->validator->isInt($clean_value, 0)) {
            $this->session_life = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setSessionName(string $value)
    {
        $clean_value = $this->validator->trimString($value);

        if ($this->validator->isAlnum($clean_value)) {
            $this->session_name = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUM, E_USER_ERROR);
        }
    }
    
    public function setSiteCopyright(string $value)
    {
        $this->siteCopyright = $this->validator->trimString($value);
    }
    
    public function setSiteName(string $value)
    {
        $this->siteName = $this->validator->trimString($value);
    }
    
    public function setSiteTimezone(string $value)
    {
        $this->siteTimezone = $this->validator->trimString($value);
    }
    
    public function setUserPagination(int $value)
    {
        $clean_value = (int) $value;
        
        if ($this->validator->isInt($clean_value, 1)) {
            $this->userPagination = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
}