<?php

/**
 * TfishPreference class file.
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
 * A preference object is automatically instantiated on every page via tfish_header.php.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
 * @property    string $site_name Name of website.
 * @property    string $site_description Meta description of website.
 * @property    string $site_author Author of website.
 * @property    string $site_email Administrative contact email for website.
 * @property    string $site_copyright Copyright notice.
 * @property    int $close_site Toggle to close this site.
 * @property    string $server_timezone Timezone of server location.
 * @property    string $site_timezone Timezone for main audience location.
 * @property    int $min_search_length Minimum length of search terms.
 * @property    int search_pagination Number of search results to show on a page.
 * @property    int user_pagination Number of content objects to show on public index page.
 * @property    int admin_pagination Number of content objects to show on admin index page.
 * @property    int gallery_pagination Number of images to show in admin gallery.
 * @property    int pagination_elements Number of slots to include on pagination controls.
 * @property    string session_name Name of session.
 * @property    int session_life Expiry timer for inactive sessions (minutes).
 * @property    string default_language Default language of site.
 * @property    string date_format Format to display dates, as per PHP date() function.
 * @property    int enable_cache Enable site cache.
 * @property    int cache_life Expiry timer for site cache (seconds).
 */
class TfishPreference
{
    
    protected $site_name;
    protected $site_description;
    protected $site_author;
    protected $site_email;
    protected $site_copyright;
    protected $close_site;
    protected $server_timezone;
    protected $site_timezone;
    protected $min_search_length;
    protected $search_pagination;
    protected $user_pagination;
    protected $admin_pagination;
    protected $gallery_pagination;
    protected $rss_posts;
    protected $pagination_elements;
    protected $session_name;
    protected $session_life;
    protected $default_language;
    protected $date_format;
    protected $enable_cache;
    protected $cache_life;
    
    function __construct()
    {
        $preferences = self::readPreferencesFromDatabase();
        
        $this->setSiteName($preferences['site_name']);
        $this->setSiteDescription($preferences['site_description']);
        $this->setSiteAuthor($preferences['site_author']);
        $this->setSiteEmail($preferences['site_email']);
        $this->setSiteCopyright($preferences['site_copyright']);
        $this->setCloseSite($preferences['close_site']);
        $this->setServerTimezone($preferences['server_timezone']);
        $this->setSiteTimezone($preferences['site_timezone']);
        $this->setMinSearchLength($preferences['min_search_length']);
        $this->setSearchPagination($preferences['search_pagination']);
        $this->setUserPagination($preferences['user_pagination']);
        $this->setAdminPagination($preferences['admin_pagination']);
        $this->setGalleryPagination($preferences['gallery_pagination']);
        $this->setRssPosts($preferences['rss_posts']);
        $this->setPaginationElements($preferences['pagination_elements']);
        $this->setSessionName($preferences['session_name']);
        $this->setSessionLife($preferences['session_life']);
        $this->setDefaultLanguage($preferences['default_language']);
        $this->setDateFormat($preferences['date_format']);
        $this->setEnableCache($preferences['enable_cache']);
        $this->setCacheLife($preferences['cache_life']);
    }
    
    /**
     * Escape a property for on-screen display to prevent XSS.
     * 
     * Applies htmlspecialchars() to a property destined for display to mitigate XSS attacks.
     * Note that preference values should not be directly assigned to meta tags; they should be
     * assigned to $tfish_metadata instead, which will handle any escaping necessary.
     * 
     * @param string $property Name of property.
     * @return string Value of property escaped for display.
     */
    public function escapeForXss(string $property)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->$clean_property)) {
            return htmlspecialchars($this->__data[$clean_property], ENT_QUOTES, 'UTF-8', false);
        } else {
            return null;
        }
    }
    
    /**
     * Read out the site preferences into an array.
     * 
     * @return array Array of site preferences.
     */
    public static function readPreferencesFromDatabase()
    {
        $preferences = array();
        $result = TfishDatabase::select('preference');
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $preferences[$row['title']] = $row['value'];
        }
        
        return $preferences;
    }

    public function setAdminPagination($value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 1)) {
            $this->admin_pagination = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setSiteAuthor($value)
    {
        $this->site_name = TfishDataValidator::trimString($value);
    }
    
    public function setCacheLife($value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 1)) {
            $this->cache_life = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setCloseSite($value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 0, 1)) {
            $this->close_site = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setDateFormat($value)
    {
        $this->site_name = TfishDataValidator::trimString($value);
    }
    
    public function setDefaultLanguage($value)
    {
        $clean_value = TfishDataValidator::trimString($value);
        
        if (!TfishDataValidator::isAlpha($clean_value)) {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
        }

        $content_handler = new TfishContentHandler();
        $language_whitelist = $content_handler->getListOfLanguages();

        if (array_key_exists($clean_value, $language_whitelist)) {
            $this->default_language = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
        }
    }
    
    public function setSiteDescription($value)
    {
        $this->site_name = TfishDataValidator::trimString($value);
    }
    
    public function setSiteEmail($value)
    {
        $clean_value = TfishDataValidator::trimString($value);

        if (TfishDataValidator::isEmail($clean_value)) {
            $this->site_email = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    public function setEnableCache($value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 0, 1)) {
            $this->enable_cache = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setGalleryPagination($value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 1)) {
            $this->gallery_pagination = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setMinSearchLength($value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 3)) {
            $this->min_search_length = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setPaginationElements($value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 3)) {
            $this->pagination_elements = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setRssPosts($value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 1)) {
            $this->rss_posts = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setSearchPagination($value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 0)) {
            $this->search_pagination = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setServerTimezone($value)
    {
        $this->site_name = TfishDataValidator::trimString($value);
    }
    
    public function setSessionLife($value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 0)) {
            $this->session_life = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setSessionName($value)
    {
        $clean_value = TfishDataValidator::trimString($value);

        if (TfishDataValidator::isAlnum($clean_value)) {
            $this->session_name = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUM, E_USER_ERROR);
        }
    }
    
    public function setSiteCopyright($value)
    {
        $this->site_name = TfishDataValidator::trimString($value);
    }
    
    public function setSiteName($value)
    {
        $this->site_name = TfishDataValidator::trimString($value);
    }
    
    public function setSiteTimezone($value)
    {
        $this->site_name = TfishDataValidator::trimString($value);
    }
    
    public function setUserPagination($value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 1)) {
            $this->user_pagination = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
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
        if (!TfishDataValidator::isArray($dirty_input)) {
            trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
        }

        // Obtain a whitelist of permitted fields.
        $whitelist = $this->getPropertyWhitelist();

        // Iterate through the whitelist validating supplied parameters.
        foreach ($whitelist as $key => $type) {
            if (array_key_exists($key, $dirty_input)) {
                $this->__set($key, $dirty_input[$key]);
            }
            
            unset($key, $type);
        }
    }

}
