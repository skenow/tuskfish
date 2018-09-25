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
 * Generates RSS feeds for the whole site, individual collections and tags.
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
    protected $webMaster;
    protected $generator;
    protected $items;
    protected $template;

    /**
     * Constructor.
     * 
     * @param TfValidator $validator An instance of the Tuskfish data validator class.
     * @param TfPreference $preference An instance of the Tuskfish site preferences class.
     */
    public function __construct(TfValidator $validator, TfPreference $preference)
    {
        
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator; 
        } else {
            trigger_error(TFISH_ERROR_NOT_VALIDATOR, E_USER_ERROR);
        }
        
        if (!is_a($preference, 'TfPreference')) {
            trigger_error(TFISH_ERROR_NOT_PREFERENCE, E_USER_ERROR);
        }
        
        $this->setTitle($preference->siteName);
        $this->setLink(TFISH_RSS_URL);
        $this->setDescription($preference->siteDescription);
        $this->setCopyright($preference->siteCopyright);
        $this->setManagingEditor($preference->siteEmail);
        $this->setWebMaster($preference->siteEmail);
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
            trigger_error(TFISH_ERROR_NOT_COLLECTION_OBJECT, E_USER_ERROR);
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
     * Returns the feed copyright notice XSS escaped for display.
     * 
     * @return string Copyright notice.
     */
    public function getCopyright()
    {
        return $this->validator->escapeForXss($this->copyright);
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
     * Returns the feed description with tags removed and XSS escaped for display.
     * 
     * @return string Description of feed.
     */
    public function getDescription()
    {
        $description = strip_tags($this->description);
        
        return $this->validator->escapeForXss($description);
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
     * Returns the generator of this feed XSS escaped for display.
     * 
     * Not currently in use.
     * 
     * @return string Generator.
     */
    public function getGenerator()
    {
        return $this->validator->escapeForXss($this->generator);
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
     * Returns an array of content items associated with this feed.
     * 
     * Note that the items are objects and their individual properties need to be XSS escaped 
     * separately.
     * 
     * @return array Array of content objects associated with this feed.
     */
    public function getItems()
    {
        return $this->items;
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
     * Returns the base URL of the feed XSS escaped for display.
     * 
     * @return string Base URL of feed.
     */
    public function getLink()
    {
        return $this->validator->escapeForXss($this->link);
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
     * Returns the managing editor of this feed.
     * 
     * @return string Managing editor.
     */
    public function getManagingEditor()
    {
        return $this->validator->escapeForXss($this->managingEditor);
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
     * Returns the title of the feed XSS escaped for display.
     * 
     * @return string Title.
     */
    public function getTitle()
    {
        return $this->validator->escapeForXss($this->title);
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
     * Set the webMaster property.
     * 
     * @param string $email email of the webmaster responsible for the feed.
     */
    public function setWebMaster(string $email)
    {
        $cleanEmail = $this->validator->trimString($email);

        if ($this->validator->isEmail($cleanEmail)) {
            $this->webMaster = $cleanEmail;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    /**
     * Get the wemaster for this feed XSS escaped for display.
     * 
     * @return string Webmaster.
     */
    public function getWebMaster()
    {
        return $this->validator->escapeForXss($this->webMaster);
    }
    
}
