<?php

/**
 * Tag content object class.
 * 
 * Tags are basically 'subjects' that can be used to label content objects, to facilitate retrieval
 * of related items. However, tags are content objects in their own right and can be used as a
 * simple hook to create a section on your website. Tags can be grouped into collections via the
 * parent field, and tag collections can be used to create custom tag select boxes.
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

class TfishTag extends TfishContentObject
{

    function __construct()
    {
        // Must call parent constructor first.
        parent::__construct();

        // Declare the type, template and module for this this class.
        $this->__data['type'] = "TfishTag";
        $this->__data['template'] = "tag";
        $this->__data['module'] = "tags";
        $this->__data['icon'] = '<span class="glyphicon glyphicon-tag" aria-hidden="true"></span>';

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
        return array(
            'format',
            'file_size',
            'creator',
            'media',
            'date',
            //'parent', // Turns out allowing collections of tags is actually useful.
            'language',
            'rights',
            'publisher',
            'tags');
    }

}
