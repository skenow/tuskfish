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
 * @properties  int $id // Auto-increment, set by database.
 * @properties  string $type // Content object type eg. TfishArticle etc. [ALPHA]
 * @properties  string $title // The name of this content.
 * @properties  string $teaser // A short (one paragraph) summary or abstract for this content. [HTML]
 * @properties  string $description // The full article or description of the content. [HTML]
 * @properties  string $media // An associated download/audio/video file. [FILEPATH OR URL]
 * @properties  string $format // Mimetype
 * @properties  string $file_size // Specify in bytes.
 * @properties  string $creator // Author.
 * @properties  string image // An associated image file, eg. a screenshot a good way to handle it. [FILEPATH OR URL]
 * @properties  string $caption // Caption of the image file.
 * @properties  string $date // Date of publication expressed as a string.
 * @properties  int $parent // A source work or collection of which this content is part.
 * @properties  string $language // Future proofing.
 * @properties  int $rights // Intellectual property rights scheme or license under which the work is distributed.
 * @properties  string $publisher // The entity responsible for distributing this work.
 * @properties  array $tags // Tag IDs associated with this object; not persistent (stored as taglinks in taglinks table).
 * @properties  int $online // Toggle object on or offline.
 * @properties  int $submission_time // Timestamp representing submission time.
 * @properties  int $counter // Number of times this content was viewed or downloaded.
 * @properties  string $meta_title // Set a custom page title for this content.
 * @properties  string $meta_description // Set a custom page meta description for this content.
 * @properties  string $seo // SEO-friendly string; it will be appended to the URL for this content.
 * @properties  string $handler // Handler for this object (not persistent).
 * @properties  string $template // The template that should be used to display this object (not persistent).
 * @properties  string $module // The module that handles this content type (not persistent).
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
