<?php

/**
 * TfishAudio class file.
 * 
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		core
 */

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Audio content object class.
 * 
 * Represents an audio recording.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		core
 * @properties  int $id Auto-increment, set by database.
 * @properties  string $type Content object type eg. TfishArticle etc. [ALPHA]
 * @properties  string $title The name of this content.
 * @properties  string $teaser A short (one paragraph) summary or abstract for this content. [HTML]
 * @properties  string $description The full article or description of the content. [HTML]
 * @properties  string $media An associated download/audio/video file. [FILEPATH OR URL]
 * @properties  string $format Mimetype
 * @properties  string $file_size Specify in bytes.
 * @properties  string $creator Author.
 * @properties  string image An associated image file, eg. a screenshot a good way to handle it. [FILEPATH OR URL]
 * @properties  string $caption Caption of the image file.
 * @properties  string $date Date of publication expressed as a string.
 * @properties  int $parent A source work or collection of which this content is part.
 * @properties  string $language Future proofing.
 * @properties  int $rights Intellectual property rights scheme or license under which the work is distributed.
 * @properties  string $publisher The entity responsible for distributing this work.
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
class TfishAudio extends TfishContentObject
{

    /** Initialise default property values and unset unneeded ones. */
    function __construct()
    {
        // Must call parent constructor first.
        parent::__construct();

        // Declare the type, template and module for this this class
        $this->__data['type'] = "audio";
        $this->__data['template'] = "audio";
        $this->__data['module'] = "soundtracks";
        $this->__data['icon'] = '<span class="glyphicon glyphicon-volume-up" aria-hidden="true"></span>';

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
     * @return array Array of properties that should be zeroed (unset).
     */
    public function zeroedProperties()
    {
        return array();
    }

}
