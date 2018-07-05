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
    
    /** @var array Holds values of permitted preference object properties. */
    protected $__data = array();
    
    function __construct(array $preferences)
    {
        $this->loadPropertiesFromArray($preferences);
    }
    
    /**
     * Converts the preference object to an array suitable for insert/update calls to the database.
     * 
     * @return array Array of object property/values.
     */
    public function getPreferencesAsArray()
    {
        $key_values = array();
        
        foreach ($this->__data as $key => $value) {
            $key_values[$key] = $value;
        }
        
        return $key_values;
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

    public function setAdminPagination(int $value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 1)) {
            $this->__data['admin_pagination'] = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setSiteAuthor(string $value)
    {
        $this->__data['site_author'] = TfishDataValidator::trimString($value);
    }
    
    public function setCacheLife(int $value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 1)) {
            $this->__data['cache_life'] = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setCloseSite(int $value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 0, 1)) {
            $this->__data['close_site'] = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setDateFormat(string $value)
    {
        $this->__data['date_format'] = TfishDataValidator::trimString($value);
    }
    
    public function setDefaultLanguage(string $value)
    {
        $clean_value = TfishDataValidator::trimString($value);
        
        if (!TfishDataValidator::isAlpha($clean_value)) {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
        }

        $content_handler = new TfishContentHandler();
        $language_whitelist = $content_handler->getListOfLanguages();

        if (array_key_exists($clean_value, $language_whitelist)) {
            $this->__data['default_language'] = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
        }
    }
    
    public function setSiteDescription(string $value)
    {
        $this->__data['site_description'] = TfishDataValidator::trimString($value);
    }
    
    public function setSiteEmail(string $value)
    {
        $clean_value = TfishDataValidator::trimString($value);

        if (TfishDataValidator::isEmail($clean_value)) {
            $this->__data['site_email'] = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    public function setEnableCache(int $value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 0, 1)) {
            $this->__data['enable_cache'] = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setGalleryPagination(int $value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 1)) {
            $this->__data['gallery_pagination'] = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setMinSearchLength(int $value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 3)) {
            $this->__data['min_search_length'] = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setPaginationElements(int $value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 3)) {
            $this->__data['pagination_elements'] = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setRssPosts(int $value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 1)) {
            $this->__data['rss_posts'] = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setSearchPagination(int $value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 0)) {
            $this->__data['search_pagination'] = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setServerTimezone(string $value)
    {
        $this->__data['server_timezone'] = TfishDataValidator::trimString($value);
    }
    
    public function setSessionLife(int $value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 0)) {
            $this->__data['session_life'] = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setSessionName(string $value)
    {
        $clean_value = TfishDataValidator::trimString($value);

        if (TfishDataValidator::isAlnum($clean_value)) {
            $this->__data['session_name'] = $clean_value;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUM, E_USER_ERROR);
        }
    }
    
    public function setSiteCopyright(string $value)
    {
        $this->__data['site_copyright'] = TfishDataValidator::trimString($value);
    }
    
    public function setSiteName(string $value)
    {
        $this->__data['site_name'] = TfishDataValidator::trimString($value);
    }
    
    public function setSiteTimezone(string $value)
    {
        $this->__data['site_timezone'] = TfishDataValidator::trimString($value);
    }
    
    public function setUserPagination(int $value)
    {
        $clean_value = (int) $value;
        
        if (TfishDataValidator::isInt($clean_value, 1)) {
            $this->__data['user_pagination'] = $clean_value;
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
        // Validate object properties as they are assigned.
        if (isset($dirty_input['site_name'])) $this->setSiteName($dirty_input['site_name']);
        if (isset($dirty_input['site_description'])) $this->setSiteDescription($dirty_input['site_description']);
        if (isset($dirty_input['site_author'])) $this->setSiteAuthor($dirty_input['site_author']);
        if (isset($dirty_input['site_email'])) $this->setSiteEmail($dirty_input['site_email']);
        if (isset($dirty_input['site_copyright'])) $this->setSiteCopyright($dirty_input['site_copyright']);
        if (isset($dirty_input['close_site'])) $this->setCloseSite((int) $dirty_input['close_site']);
        if (isset($dirty_input['server_timezone'])) $this->setServerTimezone($dirty_input['server_timezone']);
        if (isset($dirty_input['site_timezone'])) $this->setSiteTimezone($dirty_input['site_timezone']);
        if (isset($dirty_input['min_search_length'])) $this->setMinSearchLength((int) $dirty_input['min_search_length']);
        if (isset($dirty_input['search_pagination'])) $this->setSearchPagination((int) $dirty_input['search_pagination']);
        if (isset($dirty_input['user_pagination'])) $this->setUserPagination((int) $dirty_input['user_pagination']);
        if (isset($dirty_input['admin_pagination'])) $this->setAdminPagination((int) $dirty_input['admin_pagination']);
        if (isset($dirty_input['gallery_pagination'])) $this->setGalleryPagination((int) $dirty_input['gallery_pagination']);
        if (isset($dirty_input['rss_posts'])) $this->setRssPosts((int) $dirty_input['rss_posts']);
        if (isset($dirty_input['pagination_elements'])) $this->setPaginationElements((int) $dirty_input['pagination_elements']);
        if (isset($dirty_input['session_name'])) $this->setSessionName($dirty_input['session_name']);
        if (isset($dirty_input['session_life'])) $this->setSessionLife((int) $dirty_input['session_life']);
        if (isset($dirty_input['default_language'])) $this->setDefaultLanguage($dirty_input['default_language']);
        if (isset($dirty_input['date_format'])) $this->setDateFormat($dirty_input['date_format']);
        if (isset($dirty_input['enable_cache'])) $this->setEnableCache((int) $dirty_input['enable_cache']);
        if (isset($dirty_input['cache_life'])) $this->setCacheLife((int) $dirty_input['cache_life']);
    }
    
    /** Magic methods **/
    
    /**
     * Get the value of a property.
     * 
     * Intercepts direct calls to access an object property. This method can be overridden to impose
     * processing logic to the value before returning it.
     * 
     * @param string $property Name of property.
     * @return mixed|null $property Value of property if it is set; otherwise null.
     */
    public function __get(string $property)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            return $this->__data[$clean_property];
        } else {
            return null;
        }
    }

    /**
     * Intercept and prevent direct setting of properties.
     * 
     * Properties must be set using the relevant setter method.
     * 
     * @param string $property Name of property.
     * @param mixed $value Value of property.
     */
    public function __set(string $property, $value)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            trigger_error(TFISH_ERROR_DIRECT_PROPERTY_SETTING_DISALLOWED);
        } else {
            trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
        }
        
        exit;
    }

    /**
     * Check if an object property is set.
     * 
     * Intercepts isset() calls to correctly read object properties.
     * 
     * @param string $property Name of property to check.
     * @return bool True if set otherwise false.
     */
    public function __isset(string $property)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Unsets a property.
     * 
     * Intercepts unset() calls to correctly unset object properties. Can be overridden in child
     * objects to add processing logic for specific properties.
     * 
     * @param string $property Name of property.
     * @return bool True on success false on failure.
     */
    public function __unset(string $property)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            unset($this->__data[$clean_property]);
            return true;
        } else {
            return false;
        }
    }

}
