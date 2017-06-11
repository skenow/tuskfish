<?php

/**
 * Taglink object class.
 * 
 * Taglink objects are used to create relationships between content objects and tag objects, thereby
 * facilitating retrieval of related content. Taglinks are stored in their own table.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		core
 * @property    int $id ID of this taglink object
 * @property    int $tag_id ID of the tag object
 * @property    string $content_type type of content object
 * @property    string $handler The handler for taglink objects
 */
if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishTaglink
{

    /** @var array Array holding the values of taglink object properties, acccessed via magic methods. */
    protected $__data = array(
        'id',
        'tag_id', 
        'content_type', 
        'content_id',
        'handler');

    /** Initialise default property values and unset unneeded ones. */
    function __construct()
    {
        $this->__data['type'] = "TfishTaglink";
    }

    /**
     * Get the value of a property.
     * 
     * Intercepts direct calls to access an object property. This method can be overridden to impose
     * processing logic to the value before returning it.
     * 
     * @param string $property name
     * @return mixed|null $property value if it is set; otherwise null.
     */
    public function __get($property)
    {
        if (isset($this->__data[$property])) {
            return $this->__data[$property];
        } else {
            return null;
        }
    }

    /**
     * Set the value of a whitelisted property.
     * 
     * Intercepts direct calls to set the value of an object property. This method is overridden by
     * child classes to impose data type restrictions and range checks before allowing the property
     * to be set. Tuskfish objects are designed not to trust other components; each conducts its
     * own internal validation checks. 
     * 
     * @param string $property name
     * @param mixed $value
     */
    public function __set($property, $value)
    {
        if (isset($this->__data[$property])) {
            $this->__data[$property] = $value;
        } else {
            return false;
        }
    }

    /**
     * Check if a property is set.
     * 
     * Intercepts isset() calls to correctly read object properties. Can be overridden in child
     * objects to add processing logic for specific properties.
     * 
     * @param string $property name
     * @return bool 
     */
    public function __isset($property)
    {
        if (isset($this->__data[$property])) {
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
     * @param string $property name
     * @return bool true on success false on failure 
     */
    public function __unset($property)
    {
        if (isset($this->__data[$property])) {
            unset($this->__data[$property]);
            return true;
        } else {
            return false;
        }
    }

}
