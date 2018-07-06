<?php

/**
 * TfishRss class file.
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
 * RSS feed generator class.
 * 
 * For information about the RSS 2.0 spec see http://cyber.harvard.edu/rss/rss.html
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @param       object $tfish_preference TfishPreference object to make site preferences available.
 * @property    string $title Name of channel.
 * @property    string $link URL to website associated with this channel.
 * @property    string $description Sentence describing the channel.
 * @property    string $copyright Copyright license of this channel.
 * @property    string $managingEditor Email of the editor.
 * @property    string $webMaster Email of the webmaster.
 * @property    string $generator Name of software system generating this feed.
 * @property    string $image Image representing channel.
 * @property    array $items Array of content objects.
 * @property    string $template Template for presenting feed, default 'rss'.
 */
class TfishRss
{
    
    /** @var array Holds values of permitted preference object properties. */
    protected $__data = array();

    /** Initialise default property values and unset unneeded ones. */
    public function __construct(TfishPreference $tfish_preference)
    {
        // Set default values of permitted properties.
        $this->setTitle($tfish_preference->site_name);
        $this->setLink(TFISH_RSS_URL);
        $this->setDescription($tfish_preference->site_description);
        $this->setCopyright($tfish_preference->site_copyright);
        $this->setManagingEditor($tfish_preference->site_email);
        $this->setWebMaster($tfish_preference->site_email);
        $this->setGenerator('Tuskfish');
        $this->setItems(array());
        $this->setTemplate('rss');
    }
    
    public function setTitle(string $title)
    {
        $clean_title = TfishDataValidator::trimString($title);
        $this->__data['title'] = $clean_title;
    }
    
    public function setLink(string $url)
    {
        $clean_url = TfishDataValidator::trimString($url);

        if (TfishDataValidator::isUrl($clean_url)) {
            $this->__data['link'] = $clean_url;
        } else {
            trigger_error(TFISH_ERROR_NOT_URL, E_USER_ERROR);
        }
    }
    
    public function setDescription(string $description)
    {
        $clean_description = TfishDataValidator::trimString($description);
        $this->__data['description'] = $clean_description;
    }
    
    public function setCopyright(string $copyright)
    {
        $clean_copyright = TfishDataValidator::trimString($copyright);
        $this->__data['copyright'] = $clean_copyright;
    }
    
    public function setManagingEditor(string $email)
    {
        $clean_email = TfishDataValidator::trimString($email);

        if (TfishDataValidator::isEmail($clean_email)) {
            $this->__data[$clean_property] = $clean_email;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    public function setWebmaster(string $email)
    {
        $clean_email = TfishDataValidator::trimString($email);

        if (TfishDataValidator::isEmail($clean_email)) {
            $this->__data[$clean_property] = $clean_email;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    public function setGenerator(string $generator)
    {
        $clean_generator = TfishDataValidator::trimString($generator);
        $this->__data['generator'] = $clean_generator;
    }
    
    public function setImage(string $image)
    {
        // Not implemented.
    }
    
    public function setItems(array $items)
    {
        if (TfishDataValidator::isArray($items)) {
            $clean_items = array();

            foreach ($items as $item) {
                if (is_a('TfishContentObject')) {
                    $clean_items[] = $item;
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }

                unset($item);
            }

            $this->__data['items'] = $clean_items;
        } else {
            trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
        }
    }
    
    private function setTemplate(string $template)
    {
        $clean_template = TfishDataValidator::trimString($template);
        $this->__data['template'] = $clean_template;
    }

    /**
     * Make a RSS feed for a collection object.
     * 
     * @param object $obj TfishCollection object.
     */
    public function makeFeedForCollection(TfishCollection $obj)
    {
        if (!is_a($obj, 'TfishCollection')) {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        $this->setTitle($obj->title);
        $this->setLink(TFISH_RSS_URL . '?id=' . $obj->id);
        $this->setDescription($obj->teaser);
    }
    
    /** Magic methods **/
    
    /**
     * Validate and set an existing object property according to type specified in constructor.
     * 
     * For more fine-grained control each property could be dealt with individually.
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
    }
    
    /**
     * Get the value of a property.
     * 
     * Intercepts direct calls to access an object property. This method can be overridden to impose
     * processing logic to the value before returning it.
     * 
     * @param string $property Name of property.
     * @return mixed|null $property Value of property if it is set; otherwise null.
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
     * Check if an object property is set.
     * 
     * Intercepts isset() calls to correctly read object properties.
     * 
     * @param string $property Name of property to check.
     * @return bool True if set otherwise false.
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
     * @return bool True on success false on failure.
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
