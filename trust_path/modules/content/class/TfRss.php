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
 * @uses        TfMagicMethods Implementation of magic methods to restrict direct property access.
 * @property    TfValidator $validator Instance of the Tuskfish data validator class.
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
    protected $managingEditor;
    protected $webmaster;
    protected $generator;
    protected $items;
    protected $template;

    /**
     * Constructor.
     * 
     * @param TfValidator $validator An instance of the Tuskfish data validator class.
     * @param TfPreference $tfPreference An instance of the Tuskfish site preferences class.
     */
    public function __construct(TfValidator $validator, TfPreference $tfPreference)
    {
        
        $this->validator = $validator;
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
     * @param TfCollection $obj A collection object.
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
    
    /**
     * Set the copyright notice for this feed.
     * 
     * @param string $copyright Copyright notice.
     */
    public function setCopyright(string $copyright)
    {
        $cleanCopyright = $this->validator->trimString($copyright);
        $this->copyright = $cleanCopyright;
    }
    
    /**
     * Set the description of this feed.
     * 
     * @param string $description Description of feed.
     */
    public function setDescription(string $description)
    {
        $cleanDescription = $this->validator->trimString($description);
        $this->description = $cleanDescription;
    }
    
    /**
     * Set the software generator for this feed.
     * 
     * @param string $generator Name of the software used to generate this feed. For security
     * reasons, the generator tag has been removed from the Tuskfish RSS template, but you can
     * add it in if you don't mind people knowing what software your site is running.
     */
    public function setGenerator(string $generator)
    {
        $cleanGenerator = $this->validator->trimString($generator);
        $this->generator = $cleanGenerator;
    }
    
    /**
     * Set the image for this feed (not implemented).
     * 
     * @param string $image Image for this feed.
     */
    public function setImage(string $image)
    {
        // Not implemented.
    }
    
    /**
     * Set the content/posts for this feed.
     * 
     * @param array $items Items to be included in the feed.
     */
    public function setItems(array $items)
    {
        if ($this->validator->isArray($items)) {
            $cleanItems = array();

            foreach ($items as $item) {
                if (is_object($item)) {
                    $cleanItems[] = $item;
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }

                unset($item);
            }

            $this->items = $cleanItems;
        } else {
            trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
        }
    }
    
    /**
     * Set the base URL for this feed.
     * 
     * Defaults to the value of TFISH_RSS.
     * 
     * @param string $url Base URL for this feed.
     */
    public function setLink(string $url)
    {
        $cleanUrl = $this->validator->trimString($url);

        if ($this->validator->isUrl($cleanUrl)) {
            $this->link = $cleanUrl;
        } else {
            trigger_error(TFISH_ERROR_NOT_URL, E_USER_ERROR);
        }
    }
    
    /**
     * Set the managing editor of this feed.
     * 
     * @param string $email Email address of the managing editor.
     */
    public function setManagingEditor(string $email)
    {
        $cleanEmail = $this->validator->trimString($email);

        if ($this->validator->isEmail($cleanEmail)) {
            $this->managingEditor = $cleanEmail;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    /**
     * Set the title of this feed.
     * 
     * @param string $title Title of the feed.
     */
    public function setTitle(string $title)
    {
        $cleanTitle = $this->validator->trimString($title);
        $this->title = $cleanTitle;
    }
    
    /**
     * Set the template (theme) to display this feed, defaults to 'rss'.
     * 
     * @param string $template Name of the template set.
     */
    private function setTemplate(string $template)
    {
        $cleanTemplate = $this->validator->trimString($template);
        $this->template = $cleanTemplate;
    }
    
    /**
     * Set the webmaster property.
     * 
     * @param string $email email of the webmaster responsible for the feed.
     */
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
