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
