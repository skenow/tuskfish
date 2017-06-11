<?php

/**
 * RSS feed generator class.
 * 
 * For information about the RSS 2.0 spec see http://cyber.harvard.edu/rss/rss.html
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		core
 * @property    string $title Name of channel
 * @property    string $link URL to website associated with this channel
 * @property    string $description Sentence describing the channel
 * @property    string $copyright Copyright license of this channel
 * @property    string $managingEditor Email of the editor
 * @property    string $webMaster Email of the webmaster
 * @property    string $generator Name of software system generating this feed
 * @property    string $image Image representing channel
 * @property    array $items Array of content objects
 * @property    string $template Template for presenting feed, default 'rss'
 */
if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishRss extends TfishAncestralObject
{

    /** Initialise default property values and unset unneeded ones. */
    public function __construct()
    {
        parent::__construct();

        // Whitelist of official channel properties and datatypes.
        $this->__properties['title'] = 'string';
        $this->__properties['link'] = 'url';
        $this->__properties['description'] = 'string';
        $this->__properties['copyright'] = 'string';
        $this->__properties['managingEditor'] = 'email';
        $this->__properties['webMaster'] = 'email';
        // $this->__properties['category'] = 'int'; // Todo: Implement tag-specific sub-channels.
        $this->__properties['generator'] = 'string';
        $this->__properties['image'] = 'string';
        $this->__properties['items'] = 'array';

        // Set the permitted properties of this object.
        foreach ($this->__properties as $key => $value) {
            $this->__data[$key] = '';
        }

        // Set default values of permitted properties.
        global $tfish_preference;

        $this->__data['title'] = $tfish_preference->site_name;
        $this->__data['link'] = TFISH_URL . 'rss.php';
        $this->__data['description'] = $tfish_preference->site_description;
        $this->__data['copyright'] = $tfish_preference->site_copyright;
        $this->__data['managingEditor'] = $tfish_preference->site_email;
        $this->__data['webMaster'] = $tfish_preference->site_email;
        // $this->__data['category'] = 'int'; // Todo: Implement tag-specific sub-channels.
        $this->__data['generator'] = 'Tuskfish';
        //$this->__data['image'] = ''; // Todo: Add a preference or something for RSS feed.
        $this->__data['items'] = array();
        $this->__data['template'] = 'rss';
    }

    /**
     * Make a RSS feed for a collection object.
     * 
     * @param object $obj TfishCollection
     */
    public function makeFeedForCollection($obj)
    {
        $this->__set('title', $obj->title);
        $this->__set('link', TFISH_RSS_URL . '?id=' . (int) $obj->id);
        $this->__set('description', $obj->teaser);
    }

    /**
     * Validate and set an existing object property according to type specified in constructor.
     * 
     * For more fine-grained control each property could be dealt with individually.
     * 
     * @param string $property
     * @param mixed $value
     */
    public function __set($property, $value)
    {
        if (isset($this->__data[$property])) {

            // Validate $value against expected data type and business rules.
            $type = $this->__properties[$property];

            switch ($type) {

                case "array": // Items
                    if (TfishFilter::isArray($value)) {
                        $clean_items = array();
                        foreach ($value as $val) {
                            if (is_a('TfishContentObject')) {
                                $clean_items[] = $val;
                            } else {
                                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                            }
                            unset($clean_val);
                        }
                        $this->__data[$property] = $clean_items;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
                    }
                    break;

                case "email":
                    $value = TfishFilter::trimString($value);
                    if (TfishFilter::isEmail($value)) {
                        $this->__data[$property] = $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
                    }
                    break;

                case "int": // Tags, minimum value 1.
                    if (TfishFilter::isInt($value, 1)) {
                        $this->__data[$property] = (int) $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                    }
                    break;

                case "string":
                    $this->__data[$property] = TfishFilter::trimString($value);
                    break;

                case "url":
                    $value = TfishFilter::trimString($value);
                    if (TfishFilter::isUrl($value)) {
                        $this->__data[$property] = $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_URL, E_USER_ERROR);
                    }
                    break;
            }
        }
    }

}
