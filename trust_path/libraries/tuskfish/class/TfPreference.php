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
 * Holds Tuskfish site configuration (preference) data.
 * 
 * A preference object is automatically instantiated on every page via tfHeader.php.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
 * @uses        trait TfMagicMethods Prevents direct setting of properties / unlisted properties.
 * @uses        trait TfLanguage to obtain a list of available translations.
 * @property    TfValidator $validator Instance of the Tuskfish data validator class.
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
 * @property    string sessionName Name of session.
 * @property    int sessionLife Expiry timer for inactive sessions (minutes).
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
    protected $sessionName;
    protected $sessionLife;
    protected $defaultLanguage;
    protected $dateFormat;
    protected $enableCache;
    protected $cacheLife;
    
    /**
     * Constructor.
     * 
     * @param TfValidator $validator An instance of the Tuskfish data validator class.
     * @param array $preferences An associative array holding Tuskfish preference settings, may
     * be read from the database or passed in via the preference form.
     */
    function __construct(TfValidator $validator, array $preferences)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator; 
        } else {
            trigger_error(TFISH_ERROR_NOT_VALIDATOR, E_USER_ERROR);
        }
        
        $this->loadPropertiesFromArray($preferences);
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
        if (isset($dirtyInput['sessionName'])) $this->setSessionName($dirtyInput['sessionName']);
        if (isset($dirtyInput['sessionLife'])) $this->setSessionLife((int) $dirtyInput['sessionLife']);
        if (isset($dirtyInput['defaultLanguage'])) $this->setDefaultLanguage($dirtyInput['defaultLanguage']);
        if (isset($dirtyInput['dateFormat'])) $this->setDateFormat($dirtyInput['dateFormat']);
        if (isset($dirtyInput['enableCache'])) $this->setEnableCache((int) $dirtyInput['enableCache']);
        if (isset($dirtyInput['cacheLife'])) $this->setCacheLife((int) $dirtyInput['cacheLife']);
    }

    /**
     * Set the number of objects to display in a single admin page view. 
     * 
     * @param int $value Number of objects to view on a single page.
     */
    public function setAdminPagination(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 1)) {
            $this->adminPagination = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the name of the site author. Used to population page meta author tag.
     * 
     * @param string $value Name of the site author.
     */
    public function setSiteAuthor(string $value)
    {
        $this->siteAuthor = $this->validator->trimString($value);
    }
    
    /**
     * Set life of items in cache (seconds).
     * 
     * Items that expire will be rebuilt and re-written to the cache the next time the page is
     * requested.
     * 
     * @param int $value Expiry timer on cached items (seconds).
     */
    public function setCacheLife(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 1)) {
            $this->cacheLife = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Open our close the site.
     * 
     * @param int $value Site open (0) or closed (1).
     */
    public function setCloseSite(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 0, 1)) {
            $this->closeSite = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the date format, used to convert timestamps to human readable form.
     * 
     * See the PHP manual for date formatting templates: http://php.net/manual/en/function.date.php
     * 
     * @param string $value Template for formatting dates.
     */
    public function setDateFormat(string $value)
    {
        $this->dateFormat = $this->validator->trimString($value);
    }
    
    /**
     * Set the default language for this Tuskfish installation.
     * 
     * @param string $value ISO 639-1 two-letter language codes.
     */
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
    
    /**
     * Set the site description. Used in meta description tag.
     * 
     * @param string $value Site description.
     */
    public function setSiteDescription(string $value)
    {
        $this->siteDescription = $this->validator->trimString($value);
    }
    
    /**
     * Set the admin email address for the site.
     * 
     * Used in RSS feeds to populate the managingEditor and webmaster tags.
     * 
     * @param string $value Email address.
     */
    public function setSiteEmail(string $value)
    {
        $cleanValue = $this->validator->trimString($value);

        if ($this->validator->isEmail($cleanValue)) {
            $this->siteEmail = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    /**
     * Enable or disable the cache.
     * 
     * @param int $value Enabled (1) or disabled (0).
     */
    public function setEnableCache(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 0, 1)) {
            $this->enableCache = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set number of objects to display on the gallery page.
     * 
     * @param int $value Number of objects to display on a single page view.
     */
    public function setGalleryPagination(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 1)) {
            $this->galleryPagination = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the minimum length of search terms (characters).
     * 
     * Search terms less than this number of characters will be discarded. It is usually best to
     * allow a minimum length of 3 characters; this allows searching for common acronyms without
     * returning massive numbers of hits.
     * 
     * @param int $value Minimum number of characters.
     */
    public function setMinSearchLength(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 3)) {
            $this->minSearchLength = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the default number of page slots to display in pagination elements.
     * 
     * Can be overridden manually in TfishPaginationControl.
     * 
     * @param int $value Number of page slots to display in pagination control.
     */
    public function setPaginationElements(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 3)) {
            $this->paginationElements = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set number of items to display in RSS feeds.
     * 
     * @param int $value Number of items to include in feed.
     */
    public function setRssPosts(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 1)) {
            $this->rssPosts = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set number of results to display on a search page view.
     * 
     * @param int $value Number of objects to display in a single page view.
     */
    public function setSearchPagination(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 0)) {
            $this->searchPagination = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the server timezone.
     * 
     * @param string $value Timezone.
     */
    public function setServerTimezone(string $value)
    {
        $this->serverTimezone = $this->validator->trimString($value);
    }
    
    /**
     * Set the life of an idle session.
     * 
     * User will be logged out after being idle for this many minutes.
     * 
     * @param int $value Session life (minutes).
     */
    public function setSessionLife(int $value)
    {
        $cleanValue = (int) $value;
        
        if ($this->validator->isInt($cleanValue, 0)) {
            $this->sessionLife = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the name (prefix) used to identify Tuskfish sessions.
     * 
     * If you change it, use something unpredictable.
     * 
     * @param string $value Session name.
     */
    public function setSessionName(string $value)
    {
        $cleanValue = $this->validator->trimString($value);

        if ($this->validator->isAlnum($cleanValue)) {
            $this->sessionName = $cleanValue;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUM, E_USER_ERROR);
        }
    }
    
    /**
     * Set the site meta copyright.
     * 
     * Used to populate the dcterms.rights meta tag in the theme. Can be overriden in the
     * theme.html file.
     * 
     * @param string $value Copyright statement.
     */
    public function setSiteCopyright(string $value)
    {
        $this->siteCopyright = $this->validator->trimString($value);
    }
    
    /**
     * Set the title of the page.
     * 
     * Used to populate the meta title tag. Usually each page / object will specify a title, but
     * if none is set this value will be used as the default. The title can be manually overriden
     * on each page using the TfishMetadata object (see comments at the bottom of controller
     * scripts).
     * 
     * @param string $value
     */
    public function setSiteName(string $value)
    {
        $this->siteName = $this->validator->trimString($value);
    }
    
    /**
     * Set the site timezone.
     * 
     * This is normally the timezone for your principal target audience.
     * 
     * @param string $value Timezone.
     */
    public function setSiteTimezone(string $value)
    {
        $this->siteTimezone = $this->validator->trimString($value);
    }
    
    /**
     * Set the number of objects to display in a single page view on the public facing side of the
     * site.
     * 
     * @param int $value Number of objects to display.
     */
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
