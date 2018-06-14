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
        $this->__properties['city'] = 'string';
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
     * If you have added some custom properties to this content subclass that need to be type
     * and/or range checked before permitting assignment, add a switch above the call to the parent
     * method. Structure it so that any case not explicitly handled will fall through to the parent
     * method, while explicit cases will be handled here.
     * 
     * @param string $property Name of property.
     * @param mixed $value Value of property.
     */
    public function __set(string $property, $value)
    {
        parent::__set($property, $value);
    }

}
