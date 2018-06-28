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
class TfishPreference extends TfishAncestralObject
{

    /** Initialise default properties. */
    function __construct()
    {
        /**
         * Set the permitted properties of this object.
         */
        $this->__properties['site_name'] = 'string';
        $this->__properties['site_description'] = 'string';
        $this->__properties['site_author'] = 'string';
        $this->__properties['site_email'] = 'email';
        $this->__properties['site_copyright'] = 'string';
        $this->__properties['close_site'] = 'int';
        $this->__properties['server_timezone'] = 'string';
        $this->__properties['site_timezone'] = 'string';
        $this->__properties['min_search_length'] = 'int';
        $this->__properties['search_pagination'] = 'int';
        $this->__properties['user_pagination'] = 'int';
        $this->__properties['admin_pagination'] = 'int';
        $this->__properties['gallery_pagination'] = 'int';
        $this->__properties['rss_posts'] = 'int';
        $this->__properties['pagination_elements'] = 'int';
        $this->__properties['session_name'] = 'alnum';
        $this->__properties['session_life'] = 'int';
        $this->__properties['default_language'] = 'alpha';
        $this->__properties['date_format'] = 'string';
        $this->__properties['enable_cache'] = 'int';
        $this->__properties['cache_life'] = 'int';

        // Instantiate whitelisted fields in the protected $__data property.
        foreach ($this->__properties as $key => $value) {
            $this->__data[$key] = '';
        }

        $preferences = self::readPreferences();
        
        foreach ($preferences as $key => $value) {
            if (isset($this->__data[$key])) {
                if ($this->__properties[$key] === 'int') {
                    $this->__set($key, (int) $value);
                } else {
                    $this->__set($key, $value);
                }
            }
            
            unset($key, $value);
        }
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
    public function escape(string $property)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            switch ($clean_property) {
                default:
                    return htmlspecialchars($this->__data[$clean_property], ENT_NOQUOTES, 'UTF-8',
                            false);
                    break;
            }
        } else {
            return null;
        }
    }

    /**
     * Read out the site preferences into an array.
     * 
     * @return array Array of site preferences.
     */
    public static function readPreferences()
    {
        $preferences = array();
        $result = TfishDatabase::select('preference');
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $preferences[$row['title']] = $row['value'];
        }
        
        return $preferences;
    }

    /**
     * Set the value of a whitelisted property.
     * 
     * Intercepts direct calls to set the value of object properties. Imposes data type
     * restrictions and range checks before allowing the properties to be set. 
     * 
     * @param string $property Name of property.
     * @param mixed $value Value of property.
     */
    public function __set(string $property, $value)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        // Check that property is whitelisted.
        if (!isset($this->__data[$clean_property])) {
            trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
        }

        // Validate $value against expected data type and business rules.
        $type = $this->__properties[$clean_property];

        switch ($type) {
            case "alpha":
                $clean_value = TfishDataValidator::trimString($value);

                if ($clean_property === "language") {
                    $language_whitelist = TfishContentHandler::getLanguages();

                    if (!array_key_exists($clean_value, $language_whitelist)) {
                        trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                    }
                }

                if (TfishDataValidator::isAlpha($clean_value)) {
                    $this->__data[$clean_property] = $clean_value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
                }
                break;

            case "alnum":
                $clean_value = TfishDataValidator::trimString($value);

                if (TfishDataValidator::isAlnum($clean_value)) {
                    $this->__data[$clean_property] = $clean_value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_ALNUM, E_USER_ERROR);
                }
                break;

            case "alnumunder":
                $clean_value = TfishDataValidator::trimString($value);

                if (TfishDataValidator::isAlnumUnderscore($clean_value)) {
                    $this->__data[$clean_property] = $clean_value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
                }
                break;

            case "bool":
                $clean_value = (bool) $value;
                
                if (TfishDataValidator::isBool($clean_value)) {
                    $this->__data[$clean_property] = (bool) $clean_value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_BOOL, E_USER_ERROR);
                }
                break;

            case "email":
                $clean_value = TfishDataValidator::trimString($value);

                if (TfishDataValidator::isEmail($clean_value)) {
                    $this->__data[$clean_property] = $clean_value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
                }
                break;

            case "digit":
                $clean_value = TfishDataValidator::trimString($value);

                if (TfishDataValidator::isDigit($clean_value)) {
                    $this->__data[$clean_property] = $clean_value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_DIGIT, E_USER_ERROR);
                }
                break;

            case "float":
                $clean_value = (float) $value;
                
                if (TfishDataValidator::isFloat($clean_value)) {
                    $this->__data[$clean_property] = $clean_value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_FLOAT, E_USER_ERROR);
                }
                break;

            case "html":
                $clean_value = TfishDataValidator::trimString($value);
                $this->__data[$clean_property] = (string) TfishDataValidator::filterHtml($clean_value);
                break;

            case "int":
                $clean_value = (int) $value;
                
                switch ($clean_property) {
                    // 0 or 1.
                    case "close_site":
                    case "enable_cache":
                        if (TfishDataValidator::isInt($clean_value, 0, 1)) {
                            $this->__data[$clean_property] = $clean_value;
                        } else {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }
                        break;

                    // Minimum value 0.
                    case "search_pagination":
                    case "session_life":
                        if (TfishDataValidator::isInt($clean_value, 0)) {
                            $this->__data[$clean_property] = $clean_value;
                        } else {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }
                        break;

                    // Minimum value 1.
                    case "admin_pagination":
                    case "gallery_pagination":
                    case "user_pagination":
                    case "cache_life":
                    case "rss_posts":
                        if (TfishDataValidator::isInt($clean_value, 1)) {
                            $this->__data[$clean_property] = $clean_value;
                        } else {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }
                        break;

                    // Minimum value 3.
                    case "min_search_length":
                    case "pagination_elements":
                        if (TfishDataValidator::isInt($clean_value, 3)) {
                            $this->__data[$clean_property] = $clean_value;
                        } else {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }
                        break;
                }
                break;

            case "ip":
                $clean_value = TfishDataValidator::trimString($value);

                if ($clean_value === "" || TfishDataValidator::isIp($clean_value)) {
                    $this->__data[$clean_property] = $clean_value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_IP, E_USER_ERROR);
                }
                break;

            case "string":
                $this->__data[$clean_property] = TfishDataValidator::trimString($value);
                break;

            case "url":
                $clean_value = TfishDataValidator::trimString($value);

                if ($clean_value === "" || TfishDataValidator::isUrl($clean_value)) {
                    $this->__data[$clean_property] = $clean_value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_URL, E_USER_ERROR);
                }
                break;
        }
        
        return true;
    }

    /**
     * Update the preference object.
     * 
     * The preference object will conduct its own internal data type validation and range checks.
     * 
     * @param array $dirty_input Usually $_REQUEST data.
     */
    public function updatePreferences(array $dirty_input)
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

    /**
     * Save updated preferences to the database.
     * 
     * @return bool True on success false on failure.
     */
    private static function writePreferences()
    {
        return TfishDatabase::update('preference', $this->__data);
    }

}
