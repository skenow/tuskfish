<?php

/**
 * TfishBiography class file.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     contact
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Biography object class.
 * 
 * Represents the resume of an individual person.
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     contact
 */
class TfishBiography extends TfishAncestralObject
{
    /** Initialise default property values and unset unneeded ones. */
    public function __construct()
    {
        // Must call parent constructor first.
        parent::__construct();
        
        /**
         * Whitelist of official properties and datatypes.
         */
        $this->__properties['id'] = 'int';
        $this->__properties['type'] = 'alpha';
        $this->__properties['salutation'] = 'int';
        $this->__properties['firstname'] = 'string';
        $this->__properties['midname'] = 'string';
        $this->__properties['lastname'] = 'string';
        $this->__properties['gender'] = 'int';
        $this->__properties['job'] = 'string';
        $this->__properties['experience'] = 'html';
        $this->__properties['projects'] = 'html';
        $this->__properties['publications'] = 'html';
        $this->__properties['business_unit'] = 'string';
        $this->__properties['organisation'] = 'string';
        $this->__properties['address'] = 'string';
        $this->__properties['state'] = 'string';
        $this->__properties['country'] = 'int';
        $this->__properties['email'] = 'string';
        $this->__properties['mobile'] = 'string';
        $this->__properties['fax'] = 'string';
        $this->__properties['profile'] = 'url';
        $this->__properties['submission_time'] = 'int';        
        $this->__properties['media'] = 'string';
        $this->__properties['format'] = 'string';
        $this->__properties['file_size'] = 'int';
        $this->__properties['image'] = 'string';
        $this->__properties['parent'] = 'int';
        $this->__properties['tags'] = 'array';
        $this->__properties['online'] = 'int';
        $this->__properties['counter'] = 'int';
        $this->__properties['meta_title'] = 'string';
        $this->__properties['meta_description'] = 'string';
        $this->__properties['seo'] = 'string';
        $this->__properties['handler'] = 'alpha';
        $this->__properties['template'] = 'alnumunder';
        $this->__properties['module'] = 'string';
        $this->__properties['icon'] = 'html';

        /**
         * Set the permitted properties of this object.
         */
        foreach ($this->__properties as $key => $value) {
            $this->__data[$key] = '';
        }

        /**
         * Set default values of permitted properties.
         */
        $this->__data['type'] = get_class($this);
        $this->__data['template'] = 'biography';
        $this->__data['handler'] = $this->__data['type'] . 'Handler';
        $this->__data['online'] = 1;
        $this->__data['counter'] = 0;
        $this->__data['tags'] = array();
    }
    
    /**
     * Set the value of a whitelisted property.
     * 
     * Intercepts direct calls to set the value of an object property. This method is overridden by
     * child classes to impose data type restrictions and range checks on custom subclass
     * properties.
     * 
     * @param string $property Name of property.
     * @param mixed $value Value of property.
     */
    public function __set(string $property, $value)
    {
        $clean_property = TfishFilter::trimString($property);
    
        if (!isset($this->__data[$clean_property])) {
            trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_WARNING);
        }
        
        if (isset($this->__properties[$clean_property])) {
           $type = $this->__properties[$clean_property]; 
        } else {
            $type = false; // Do not set property.
        }
        
        switch ($type) {

            case "alpha":
                $value = TfishFilter::trimString($value);

                if (TfishFilter::isAlpha($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
                }
                break;

            case "alnum":
                $value = TfishFilter::trimString($value);

                if (TfishFilter::isAlnum($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_ALNUM, E_USER_ERROR);
                }
                break;

            case "alnumunder":
                $value = TfishFilter::trimString($value);

                if (TfishFilter::isAlnumUnderscore($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
                }
                break;

            // Only array field is tags, contents must all be integers.
            case "array":
                if (TfishFilter::isArray($value)) {
                    $clean_tags = array();

                    foreach ($value as $val) {
                        $clean_val = (int) $val;

                        if (TfishFilter::isInt($clean_val, 1)) {
                            $clean_tags[] = $clean_val;
                        } else {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }
                        unset($clean_val);
                    }

                    $this->__data[$clean_property] = $clean_tags;
                } else {
                    trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
                }
                break;

            case "bool":
                if (TfishFilter::isBool($value)) {
                    $this->__data[$clean_property] = (bool) $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_BOOL, E_USER_ERROR);
                }
                break;

            case "email":
                $value = TfishFilter::trimString($value);

                if (TfishFilter::isEmail($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
                }
                break;

            case "digit":
                $value = TfishFilter::trimString($value);

                if (TfishFilter::isDigit($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_DIGIT, E_USER_ERROR);
                }
                break;

            case "float":
                $value = (float) $value;
                
                if (TfishFilter::isFloat($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_FLOAT, E_USER_ERROR);
                }
                break;

            case "html":
                $value = TfishFilter::trimString($value);
                $this->__data[$clean_property] = (string) TfishFilter::filterHtml($value);
                break;

            case "int":
                $value = (int) $value;

                switch ($clean_property) {
                    // 0 or 1.
                    case "online":
                        if (TfishFilter::isInt($value, 0, 1)) {
                            $this->__data[$clean_property] = $value;
                        } else {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }
                        break;
                        
                    case "gender":
                        if (TfishFilter::isInt($value, 0, 2)) {
                            $this->__data[$clean_property] = $value;
                        } else {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }
                        break;

                    // Minimum value 0.
                    case "counter":
                    case "file_size":
                    case "id":
                        if (TfishFilter::isInt($value, 0)) {
                            $this->__data[$clean_property] = $value;
                        } else {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }
                        break;

                    // Parent ID must be different to content ID (cannot declare self as parent).
                    case "parent":
                        if (!TfishFilter::isInt($value, 0)) {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }

                        if ($value === $this->__data['id'] && $value > 0) {
                            trigger_error(TFISH_ERROR_CIRCULAR_PARENT_REFERENCE);
                        } else {
                            $this->__data[$clean_property] = $value;
                        }
                        break;

                    // Minimum value 1.
                    case "submission_time":
                    case "country":
                    case "salutation":
                        if (TfishFilter::isInt($value, 1)) {
                            $this->__data[$clean_property] = $value;
                        } else {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }
                        break;
                }
                break;

            case "ip":
                $value = TfishFilter::trimString($value);
                if ($value === "" || TfishFilter::isIp($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_IP, E_USER_ERROR);
                }
                break;

            case "string":
                $value = TfishFilter::trimString($value);

                // Check image/media paths for directory traversals and null byte injection.
                if ($clean_property === "image" || $clean_property === "media") {
                    if (TfishFilter::hasTraversalorNullByte($value)) {
                        trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
                    }
                }
                
                // Check image file is a permitted mimetype.
                if ($clean_property === "image") {
                    $mimetype_whitelist = TfishFileHandler::allowedImageMimetypes();
                    $extension = mb_strtolower(pathinfo($value, PATHINFO_EXTENSION), 'UTF-8');
                    if (!empty($extension) && !array_key_exists($extension, $mimetype_whitelist)) {
                        trigger_error(TFISH_ERROR_ILLEGAL_MIMETYPE, E_USER_ERROR);
                    }
                }
                
                // Check media file is a permitted mimetype.
                if ($clean_property === "media") {
                    $mimetype_whitelist = TfishFileHandler::getPermittedUploadMimetypes();
                    $extension = mb_strtolower(pathinfo($value, PATHINFO_EXTENSION), 'UTF-8');
                    
                    if (empty($extension) 
                            || (!empty($extension) && !array_key_exists($extension, $mimetype_whitelist))) {
                        $this->__data['media'] = '';
                        $this->__data['format'] = '';
                        $this->__data['file_size'] = '';
                    }
                }

                if ($clean_property === "format") {
                    $mimetype_whitelist = TfishFileHandler::getPermittedUploadMimetypes();
                    if (!empty($value) && !in_array($value, $mimetype_whitelist)) {
                        trigger_error(TFISH_ERROR_ILLEGAL_MIMETYPE, E_USER_ERROR);
                    }
                }

                if ($clean_property === "seo") {
                    if (TfishFilter::isUtf8($value)) {
                        $value = str_replace(' ', '-', $value);
                    } else {
                        trigger_error(TFISH_ERROR_NOT_UTF8, E_USER_ERROR);
                    }
                }

                $this->__data[$clean_property] = $value;
                break;

            case "url":
                $value = TfishFilter::trimString($value);

                if ($value === "" || TfishFilter::isUrl($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_URL, E_USER_ERROR);
                }
                break;
            
            // Illegal data type, do nothing.
            default:
                break;
        }
    }

}
