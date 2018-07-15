<?php

/**
 * TfishMetadata class file.
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
 * Holds page-level metadata.
 * 
 * Generates metadata for the page. User-facing controller scripts can override the site-level
 * defaults by uncommenting the options at the bottom of each file. A good example of this is when
 * viewing a single content object; if it has the meta_title and meta_description fields set you can
 * assign those to this object in order to customise the page title and description to the object,
 * thereby improving your SEO.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @property    string $title Meta title of this website.
 * @property    string $description Meta description of this website.
 * @property    string $author Author of this website.
 * @property    string $copyright Copyright notice.
 * @property    string $generator Software system that generated this page.
 * @property    string $seo SEO optimisation string to append to page URL.
 * @property    string $robots Meta instructions to robots.
 * @property    int $pagination_elements Number of slots in the pagination control.
 */
class TfishMetadata
{
    use TfishMagicMethods;
    
    /** @var object $preference Instance of TfishPreference class, holds site preference info. */
    private $preference;
    
    protected $title = '';
    protected $description = '';
    protected $author = '';
    protected $copyright = '';
    protected $generator = '';
    protected $seo = '';
    protected $robots = '';

    /** Initialise object properties and default values.
     * 
     * @param TfishPreference $preference Instance of TfishPreference, holding site preferences.
     */
    function __construct(object $preference)
    {
        $this->setTitle($preference->site_name);
        $this->setDescription($preference->site_description);
        $this->setAuthor($preference->site_author);
        $this->setCopyright($preference->site_copyright);
        $this->setGenerator('Tuskfish CMS');
        $this->setSeo('');
        $this->setRobots('index,follow');
    }

    /**
     * Access an existing property and escape it for output to browser.
     * 
     * @param string $property Name of property.
     * @return string|bool Value of preference escaped for display if set, otherwise false.
     * 
     * Note that the ENT_QUOTES flag must be set on htmlspecialchars() as these properties are
     * used within attributes of meta tags, so a double quote would cause breakage.
     */
    public function __get(string $property)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($this->$clean_property)) {
            return htmlspecialchars((string) $this->$clean_property, ENT_QUOTES, "UTF-8",
                    false);
        } else {
            return null;
        }
    }

    /**
     * Set an existing property.
     * 
     * @param string $property Name of property.
     * @param mixed $value Value to assign to property.
     * 
     * Note that htmlspecialchars() should use the ENT_QUOTES flag, as most of these values are
     * used within attributes of meta tags, and a double quote would break them.
     */
    
    public function setTitle(string $value)
    {
        $this->setProperty('title', $value);
    }
    
    public function setDescription(string $value)
    {
        $this->setProperty('description', $value);
    }
    
    public function setAuthor(string $value)
    {
        $this->setProperty('author', $value);
    }
    
    public function setCopyright(string $value)
    {
        $this->setProperty('copyright', $value);
    }
    
    public function setGenerator(string $value)
    {
        $this->setProperty('generator', $value);
    }
    
    public function setSeo(string $value)
    {
        $this->setProperty('seo', $value);
    }
    
    public function setRobots(string $value)
    {
        $this->setProperty('robots', $value);
    }
    
    private function setProperty(string $property, string $value)
    {
        $clean_property = TfishDataValidator::trimString($property);
        $clean_value = TfishDataValidator::trimString($value);
        $this->$clean_property = htmlspecialchars($clean_value, ENT_QUOTES, "UTF-8", false);
    }
       
}