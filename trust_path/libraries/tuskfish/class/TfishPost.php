<?php

/**
 * TfishPost class file.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     content
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Post content object class.
 * 
 * Represents a forum post or comment. Should be attached to a parent post or 
 * a collection object that is serving as a forum. Posts are user-submitted
 * objects and HTML is not allowed. Text may be formatted using Reddit-style
 * markup.
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @properties  int $id Auto-increment, set by database.
 * @properties  string $type Content object type eg. TfishPost etc. [ALPHA]
 * @properties  string $title The name of this content.
 * @properties  string $description The full post.
 * @properties  string $media An associated download/audio/video file. [URL only]
 * @properties  string $creator Author (username).
 * @properties  string image An associated image file, eg. a screenshot a good way to handle it. [URL only]
 * @properties  int $parent A source work or collection of which this content is part.
 * @properties  array $tags Tag IDs associated with this object; not persistent (stored as taglinks in taglinks table).
 * @properties  int $online Toggle object on or offline.
 * @properties  int $submission_time Timestamp representing submission time.
 * @properties  int $counter Number of times this content was viewed or downloaded.
 * @properties  string $meta_title Set a custom page title for this content.
 * @properties  string $meta_description Set a custom page meta description for this content.
 * @properties  string $seo SEO-friendly string; it will be appended to the URL for this content.
 * @properties  string $handler Handler for this object (not persistent).
 * @properties  string $template The template that should be used to display this object (not persistent).
 * @properties  string $module The module that handles this content type (not persistent).
 */
class TfishPost extends TfishContentObject
{

    /** Initialise default property values and unset unneeded ones. */
    public function __construct()
    {
        // Must call parent constructor first.
        parent::__construct();

        // Declare the type, template, module and icon for this this class.
        $this->__data['type'] = "TfishPost";
        $this->__data['template'] = "post";
        $this->__data['module'] = "forum";

        // Object definition - unset any properties unused in this subclass.
        $zeroedProperties = $this->zeroedProperties();
        
        foreach ($zeroedProperties as $property) {
            unset($this->__properties[$property], $this->__data[$property]);
        }
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
    
    /**
     * Converts properties to human readable form in preparation for output.
     * 
     * If you have added some custom properties to this content subclass that need to be formatted
     * for output, add a switch above the call to the parent method. Structure it so that any case
     * not explicitly handled will fall through to the parent method, while explicit cases will
     * return a formatted value.
     * 
     * @param string $clean_property Name of content object property to be formatted.
     */
    protected function makeHumanReadable(string $clean_property)
    {
        return parent::makeHumanReadable($clean_property);
    }
    
    /**
     * Returns an array of base object properties that are not used by this subclass.
     * 
     * This list is also used in update calls to the database to ensure that unused columns are
     * cleared and reset with default values.
     * 
     * @return array Array of properties that should be zeroed (unset).
     */
    public function zeroedProperties()
    {
        return array(
            'caption',
            'date',
            'file_size',
            'language',
            'mimetype',
            'publisher',
            'rights',
            'teaser'
        );
    }

}
