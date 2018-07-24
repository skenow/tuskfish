<?php

/**
 * TfRss class file.
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
 * @param       object $tfPreference TfPreference object to make site preferences available.
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
class TfRss
{
    
    use TfMagicMethods;
    
    protected $validator;
    protected $title;
    protected $link;
    protected $description;
    protected $copyright;
    protected $managing_editor;
    protected $webmaster;
    protected $generator;
    protected $items;
    protected $template;

    /** Initialise default property values and unset unneeded ones. */
    public function __construct(TfPreference $tfPreference, object $tfValidator)
    {
        
        // Set default values of permitted properties.
        $this->validator = $tfValidator;
        $this->setTitle($tfPreference->siteName);
        $this->setLink(TFISH_RSS_URL);
        $this->setDescription($tfPreference->siteDescription);
        $this->setCopyright($tfPreference->siteCopyright);
        $this->setManagingEditor($tfPreference->siteEmail);
        $this->setWebMaster($tfPreference->siteEmail);
        $this->setGenerator('Tuskfish');
        $this->setItems(array());
        $this->setTemplate('rss');
    }
    
    /**
     * Make a RSS feed for a collection object.
     * 
     * @param object $obj TfCollection object.
     */
    public function makeFeedForCollection(TfCollection $obj)
    {
        if (!is_a($obj, 'TfCollection')) {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        $this->setTitle($obj->title);
        $this->setLink(TFISH_RSS_URL . '?id=' . $obj->id);
        $this->setDescription($obj->teaser);
    }
    
    public function setCopyright(string $copyright)
    {
        $clean_copyright = $this->validator->trimString($copyright);
        $this->copyright = $clean_copyright;
    }
    
    public function setDescription(string $description)
    {
        $clean_description = $this->validator->trimString($description);
        $this->description = $clean_description;
    }
    
    public function setGenerator(string $generator)
    {
        $clean_generator = $this->validator->trimString($generator);
        $this->generator = $clean_generator;
    }
    
    public function setImage(string $image)
    {
        // Not implemented.
    }
    
    public function setItems(array $items)
    {
        if ($this->validator->isArray($items)) {
            $clean_items = array();

            foreach ($items as $item) {
                if (is_object($item)) {
                    $clean_items[] = $item;
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }

                unset($item);
            }

            $this->items = $clean_items;
        } else {
            trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
        }
    }
    
    public function setLink(string $url)
    {
        $clean_url = $this->validator->trimString($url);

        if ($this->validator->isUrl($clean_url)) {
            $this->link = $clean_url;
        } else {
            trigger_error(TFISH_ERROR_NOT_URL, E_USER_ERROR);
        }
    }
    
    public function setManagingEditor(string $email)
    {
        $cleanEmail = $this->validator->trimString($email);

        if ($this->validator->isEmail($cleanEmail)) {
            $this->managing_editor = $cleanEmail;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    public function setTitle(string $title)
    {
        $clean_title = $this->validator->trimString($title);
        $this->title = $clean_title;
    }
    
    private function setTemplate(string $template)
    {
        $clean_template = $this->validator->trimString($template);
        $this->template = $clean_template;
    }
    
    public function setWebmaster(string $email)
    {
        $cleanEmail = $this->validator->trimString($email);

        if ($this->validator->isEmail($cleanEmail)) {
            $this->webmaster = $cleanEmail;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
}
