<?php

/**
 * Collection content object class.
 * 
 * Represents a collection of content objects. For example, issues of a magazine produced at regular
 * intervals (download content objects) can be bound to a collection object via the 'parent'
 * property. Collections can contain mixed sets of content, for example images, videos, audio files
 * etc.
 * 
 * Collections can be nested by assigning another collection as a parent object. In this way,
 * collections can effectively serve as categories and you can construct independent category trees.
 * For example, if you wanted to create a "publications" category or section of your website you 
 * would just create a collection object called "Publications" and assign it as the parent of your 
 * publications content. Collections are also content objects in their own right, so provide them 
 * with a nice description and image/screenshot! 
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

class TfishCollection extends TfishContentObject
{

    function __construct()
    {
        // Must call parent constructor first.
        parent::__construct();

        // Declare the type, template and module for this this class
        $this->__data['type'] = "TfishCollection";
        $this->__data['template'] = "collection";
        $this->__data['module'] = "collections";
        $this->__data['icon'] = '<span class="glyphicon glyphicon-folder-open" aria-hidden="true"></span>';

        // Object definition - unset any properties unused in this subclass.
        $zeroedProperties = $this->zeroedProperties();
        foreach ($zeroedProperties as $property) {
            unset($this->__properties[$property], $this->__data[$property]);
        }
    }

    /**
     * Returns an array of base object properties that are not used by this subclass.
     * 
     * This list is also used in update calls to the database to ensure that unused columns are
     * cleared and reset with default values.
     * 
     * @return array
     */
    public function zeroedProperties()
    {
        return array();
    }

}
