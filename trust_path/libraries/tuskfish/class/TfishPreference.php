<?php

/**
 * Holds Tuskfish site configuration data.
 * 
 * A preference object is automatically instantiated on every page via tfish_header.php.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		core
 */
if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishPreference extends TfishAncestralObject
{

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
                if ($this->__properties[$key] == 'int') {
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
     * 
     * @param string $property
     * @return string|int|null
     */
    public function escape($property)
    {
        if (isset($this->__data[$property])) {
            switch ($property) {
                default:
                    return htmlspecialchars($this->__data[$property], ENT_QUOTES, 'UTF-8');
                    break;
            }
        } else {
            return null;
        }
    }

    /**
     * Read out the site preferences into an array.
     * 
     * @return array of site preferences
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
     * Set the value of an object property and will not allow non-whitelisted properties to be set.
     * 
     * Intercepts direct calls to set the value of an object property. Imposes data type
     * restrictions and range checks before allowing the properties to be set. 
     * 
     * @param string $property name
     * @param return void
     */
    public function __set($property, $value)
    {
        if (isset($this->__data[$property])) {

            // Validate $value against expected data type and business rules.
            $type = $this->__properties[$property];
            switch ($type) {
                case "alpha":
                    $value = TfishFilter::trimString($value);
                    if ($property == "language") {
                        $language_whitelist = TfishContentHandler::getLanguages();
                        if (!array_key_exists($value, $language_whitelist)) {
                            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                        }
                    }
                    if (TfishFilter::isAlpha($value)) {
                        $this->__data[$property] = $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
                    }
                    break;

                case "alnum":
                    $value = TfishFilter::trimString($value);
                    if (TfishFilter::isAlnum($value)) {
                        $this->__data[$property] = $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_ALNUM, E_USER_ERROR);
                    }
                    break;

                case "alnumunder":
                    $value = TfishFilter::trimString($value);
                    if (TfishFilter::isAlnumUnderscore($value)) {
                        $this->__data[$property] = $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
                    }
                    break;

                case "bool":
                    if (TfishFilter::isBool($value)) {
                        $this->__data[$property] = (bool) $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_BOOL, E_USER_ERROR);
                    }
                    break;

                case "email":
                    $value = TfishFilter::trimString($value);
                    if (TfishFilter::isEmail($value)) {
                        $this->__data[$property] = $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
                    }
                    break;

                case "digit":
                    $value = TfishFilter::trimString($value);
                    if (TfishFilter::isDigit($value)) {
                        $this->__data[$property] = $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_DIGIT, E_USER_ERROR);
                    }
                    break;

                case "float":
                    if (TfishFilter::isFloat($value)) {
                        $this->__data[$property] = $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_FLOAT, E_USER_ERROR);
                    }
                    break;

                case "html":
                    $value = TfishFilter::trimString($value);
                    $this->__data[$property] = (string) TfishFilter::filterHtml($value);
                    break;

                case "int":
                    $value = (int) $value;
                    switch ($property) {

                        // 0 or 1.
                        case "close_site":
                        case "enable_cache":
                            if (TfishFilter::isInt($value, 0, 1)) {
                                $this->__data[$property] = (int) $value;
                            } else {
                                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                            }
                            break;

                        // Minimum value 0.
                        case "search_pagination":
                        case "session_life":
                            if (TfishFilter::isInt($value, 0)) {
                                $this->__data[$property] = (int) $value;
                            } else {
                                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                            }
                            break;

                        // Minimum value 1.
                        case "admin_pagination":
                        case "gallery_pagination":
                        case "user_pagination":
                        case "cache_life":
                            if (TfishFilter::isInt($value, 1)) {
                                $this->__data[$property] = (int) $value;
                            } else {
                                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                            }
                            break;

                        // Minimum value 3.
                        case "min_search_length":
                        case "pagination_elements":
                            if (TfishFilter::isInt($value, 3)) {
                                $this->__data[$property] = (int) $value;
                            } else {
                                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                            }
                            break;
                    }
                    break;

                case "ip":
                    $value = TfishFilter::trimString($value);
                    if ($value == "" || TfishFilter::isIp($value)) {
                        $this->__data[$property] = $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_IP, E_USER_ERROR);
                    }
                    break;

                case "string":
                    $this->__data[$property] = TfishFilter::trimString($value);
                    break;

                case "url":
                    $value = TfishFilter::trimString($value);
                    if ($value == "" || TfishFilter::isUrl($value)) {
                        $this->__data[$property] = $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_URL, E_USER_ERROR);
                    }
                    break;
            }
            return true;
        } else {
            trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
        }
    }

    /**
     * Update the preference object using $_REQUEST data.
     * 
     * The preference object will conduct its own internal data type validation and range checks.
     * 
     * @param array $_REQUEST
     */
    public function updatePreferences($dirty_input)
    {
        if (!TfishFilter::isArray($dirty_input)) {
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
     * @return bool true on success false on failure
     */
    private static function writePreferences()
    {
        return TfishDatabase::update('preference', $this->__data);
    }

}
