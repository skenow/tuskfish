<?php

/**
 * TfishTaglink class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Taglink object class.
 * 
 * Taglink objects are used to create relationships between content objects and tag objects, thereby
 * facilitating retrieval of related content. Taglinks are stored in their own table.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @property    int $id ID of this taglink object
 * @property    int $tag_id ID of the tag object
 * @property    string $content_type type of content object
 * @property    string $handler The handler for taglink objects
 */
class TfishTaglink
{

    /** @var array Array holding the values of taglink object properties, accessed via magic methods. */
    protected $__data = array(
        'id',
        'tag_id',
        'content_type', 
        'content_id');
    
    public function setContentId($id)
    {
        if (TfishDataValidator::isInt($id, 1)) {
            $this->__data['content_id'] = (int) $id;
        }
    }
    
    public function setContentType($content_type)
    {
        $clean_content_type = TfishDataValidator::trimString($content_type);
        $content_handler = new TfishContentHandler();

        if ($content_handler->isSanctionedType($clean_content_type)) {
            $this->__data['content_type'] = $clean_content_type;
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_TYPE, E_USER_ERROR);
        }
    }   
    
    public function setId($id)
    {
        if (TfishDataValidator::isInt($id, 0)) {
            $this->__data['id'] = (int) $id;
        }
    }

    public function setTagId($id)
    {
        if (TfishDataValidator::isInt($id, 1)) {
            $this->__data['tag_id'] = (int) $id;
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
     * Get the value of a property.
     * 
     * Intercepts direct calls to access an object property. This method can be overridden to impose
     * processing logic to the value before returning it.
     * 
     * @param string $property Name of property.
     * @return mixed|null $property Value if property is set; otherwise null.
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
     * Check if a property is set.
     * 
     * Intercepts isset() calls to correctly read object properties. Can be overridden in child
     * objects to add processing logic for specific properties.
     * 
     * @param string $property Name of property.
     * @return bool True if set, otherwise false.
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
     * @return bool True on success, false on failure.
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
