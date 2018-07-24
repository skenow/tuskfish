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
        $cleanProperty = $this->validator->trimString($property);
        
        if (isset($this->$cleanProperty)) {
            return htmlspecialchars($this->$cleanProperty, ENT_QUOTES, 'UTF-8', false);
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
        $keyValues = array();
        
        foreach ($this as $key => $value) {
            $keyValues[$key] = $value;
        }
        
        return $keyValues;
    }
    
    /**
     * Update the preference object from an external data source (eg. form submission).
     * 
     * The preference object will conduct its own internal data type validation and range checks.
     * 
     * @param array $dirtyInput Usually $_REQUEST data.
     */
    public function loadPropertiesFromArray(array $dirtyInput)
    {
        if (!$this->validator->isArray($dirtyInput)) {
            trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
        }
        
        // Validate object properties as they are assigned.
        if (isset($dirtyInput['siteName'])) $this->setSiteName($dirtyInput['siteName']);
        if (isset($dirtyInput['siteDescription'])) $this->setSiteDescription($dirtyInput['siteDescription']);
        if (isset($dirtyInput['siteAuthor'])) $this->setSiteAuthor($dirtyInput['siteAuthor']);
        if (isset($dirtyInput['siteEmail'])) $this->setSiteEmail($dirtyInput['siteEmail']);
        if (isset($dirtyInput['siteCopyright'])) $this->setSiteCopyright($dirtyInput['siteCopyright']);
        if (isset($dirtyInput['closeSite'])) $this->setCloseSite((int) $dirtyInput['closeSite']);
        if (isset($dirtyInput['serverTimezone'])) $this->setServerTimezone($dirtyInput['serverTimezone']);
        if (isset($dirtyInput['siteTimezone'])) $this->setSiteTimezone($dirtyInput['siteTimezone']);
        if (isset($dirtyInput['minSearchLength'])) $this->setMinSearchLength((int) $dirtyInput['minSearchLength']);
        if (isset($dirtyInput['searchPagination'])) $this->setSearchPagination((int) $dirtyInput['searchPagination']);
        if (isset($dirtyInput['userPagination'])) $this->setUserPagination((int) $dirtyInput['userPagination']);
        if (isset($dirtyInput['adminPagination'])) $this->setAdminPagination((int) $dirtyInput['adminPagination']);
        if (isset($dirtyInput['galleryPagination'])) $this->setGalleryPagination((int) $dirtyInput['galleryPagination']);
        if (isset($dirtyInput['rssPosts'])) $this->setRssPosts((int) $dirtyInput['rssPosts']);
        if (isset($dirtyInput['paginationElements'])) $this->setPaginationElements((int) $dirtyInput['paginationElements']);
        if (isset($dirtyInput['session_name'])) $this->setSessionName($dirtyInput['session_name']);
        if (isset($dirtyInput['session_life'])) $this->setSessionLife((int) $dirtyInput['session_life']);
        if (isset($dirtyInput['defaultLanguage'])) $this->setDefaultLanguage($dirtyInput['defaultLanguage']);
        if (isset($dirtyInput['dateFormat'])) $this->setDateFormat($dirtyInput['dateFormat']);
        if (isset($dirtyInput['enableCache'])) $this->setEnableCache((int) $dirtyInput['enableCache']);
        if (isset($dirtyInput['cacheLife'])) $this->setCacheLife((int) $dirtyInput['cacheLife']);
    }

    public function setAdminPagination(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 1)) {
            $this->adminPagination = $cleanValue;
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
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 1)) {
            $this->cacheLife = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setCloseSite(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 0, 1)) {
            $this->closeSite = $cleanValue;
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
        $cleanValue = $this->validator->trimString($value);
        
        if (!$this->validator->isAlpha($cleanValue)) {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
        }

        $languageWhitelist = $this->getListOfLanguages();

        if (array_key_exists($cleanValue, $languageWhitelist)) {
            $this->defaultLanguage = $cleanValue;
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
        $cleanValue = $this->validator->trimString($value);

        if ($this->validator->isEmail($cleanValue)) {
            $this->siteEmail = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    public function setEnableCache(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 0, 1)) {
            $this->enableCache = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setGalleryPagination(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 1)) {
            $this->galleryPagination = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setMinSearchLength(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 3)) {
            $this->minSearchLength = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setPaginationElements(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 3)) {
            $this->paginationElements = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setRssPosts(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 1)) {
            $this->rssPosts = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setSearchPagination(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 0)) {
            $this->searchPagination = $cleanValue;
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
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 0)) {
            $this->session_life = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setSessionName(string $value)
    {
        $cleanValue = $this->validator->trimString($value);

        if ($this->validator->isAlnum($cleanValue)) {
            $this->session_name = $cleanValue;
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
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 1)) {
            $this->userPagination = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
}