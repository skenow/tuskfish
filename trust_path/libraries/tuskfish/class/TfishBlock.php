<?php

/**
 * Tuskfish parent block object class.
 * 
 * All block classes are descendants of this class. 
 *
 * @copyright	Simon Wilkinson (Crushdepth) 2013-2016
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @since		1.0
 * @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
 * @package		core
 */
if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishBlock_bak extends TfishAncestralObject
{

    function __construct()
    {
        parent::__construct();

        /**
         * Whitelist of official properties and datatypes.
         */
        $this->__properties['id'] = 'int'; // Auto-increment, set by database.
        $this->__properties['type'] = 'alpha'; // Content object type eg. TfishArticle etc. [ALPHA]
        $this->__properties['title'] = 'string'; // The headline or name of this content.
        $this->__properties['description'] = 'html'; // Content of this block (singular).
        $this->__properties['tags'] = 'array'; // Tag IDs associated with this object; not persistent (stored as taglinks in taglinks table).
        $this->__properties['online'] = 'int'; // Toggle object on or offline.
        $this->__properties['submission_time'] = 'int'; // Timestamp representing submission time.
        $this->__properties['handler'] = 'alpha'; // Handler for this object.
        $this->__properties['template'] = 'alnum'; // The template that should be used to display this object.

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
        $this->__data['template'] = 'default';
        $this->__data['handler'] = $this->__data['type'] . 'Handler';
        $this->__data['online'] = 1;
        $this->__data['tags'] = array();
    }

    /**
     * Set the value of an object property and will not allow non-whitelisted properties to be set.
     * 
     * Intercepts direct calls to set the value of an object property. This method is overriden to
     * impose data type restrictions and range checks before allowing the property to be set.
     * 
     * @param string $property name
     * @param return void
     */
    public function __set($property, $value)
    {
        // Validate $value against expected data type and business rules
        if (isset($this->__data[$property])) {
            $type = $this->__properties[$property];
            
            switch ($type) {
                
                // Type, handler.
                case "alpha":
                    $value = TfishFilter::trimString($value);
                    if (TfishFilter::isAlpha($value)) {
                        $this->__data[$property] = $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
                    }
                    break;
                
                // Template.
                case "alnum":
                    $value = TfishFilter::trimString($value);
                    if (TfishFilter::isAlnum($value)) {
                        $this->__data[$property] = $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_ALNUM, E_USER_ERROR);
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
                        $this->__data[$property] = $clean_tags;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
                    }
                    break;
                
                // Description, filtered with HTMLPurifier.
                case "html":
                    $value = TfishFilter::trimString($value);
                    $this->__data[$property] = (string) TfishFilter::filterHtml($value);
                    break;
                
                // Id, submission_time, online.
                case "int":
                    $value = (int) $value;
                    
                    switch ($property) {
                    
                        // 0.
                        case "id":
                            if (TfishFilter::isInt($value, 0)) {
                                $this->__data[$property] = (int) $value;
                            } else {
                                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                            }
                            break;
                        
                        // > 0.
                        case "submission_time":
                            if (TfishFilter::isInt($value, 1)) {
                                $this->__data[$property] = (int) $value;
                            } else {
                                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                            }
                            break;
                        
                        // 0 or 1.
                        case "online":
                            if (TfishFilter::isInt($value, 0, 1)) {
                                $this->__data[$property] = (int) $value;
                            } else {
                                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                            }
                            break;
                    }
                    
                    break;
                
                // Title.
                case "string":
                    $this->__data[$property] = TfishFilter::trimString($value);
                    break;                
            }
        } else {
            trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
            exit;
        }
    }
    
    /**
     * Escapes object properties for output to browser and formats it as human readable (where necessary).
     * 
     * Use this method to retrieve object properties when you want to send them to the browser.
     * They will be automatically escaped with htmlspecialchars to mitigate cross-site scripting
     * attacks. Note that the method specifically excludes the teaser and description fields, 
     * which are returned unescaped; these are dedicated HTML fields that have been input-validated
     * with the HTMLPurifier library, and so *should* be safe.
     * 
     * @param string $property
     * @return string
     */
    public function escape($property)
    {
        
    }

}
