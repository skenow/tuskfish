<?php

/**
 * TfMetadata class file.
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
 * viewing a single content object; if it has the metaTitle and metaDescription fields set you can
 * assign those to this object in order to customise the page title and description to the object,
 * thereby improving your SEO.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @uses        trait TfMagicMethods Prevents direct setting of properties / unlisted properties.
 * @property    TfValidator $validator Instance of the Tuskfish data validator class.
 * @property    TfPreference $preference Instance of Tuskfish site preferences class.
 * @property    string $title Meta title of this website.
 * @property    string $description Meta description of this website.
 * @property    string $author Author of this website.
 * @property    string $copyright Copyright notice.
 * @property    string $generator Software system that generated this page.
 * @property    string $seo SEO optimisation string to append to page URL.
 * @property    string $robots Meta instructions to robots.
 * @property    int $paginationElements Number of slots in the pagination control.
 */
class TfMetadata
{
    use TfMagicMethods;
    
    protected $validator;
    protected $preference;
    protected $title = '';
    protected $description = '';
    protected $author = '';
    protected $copyright = '';
    protected $generator = '';
    protected $seo = '';
    protected $robots = '';

    /** Initialise object properties and default values.
     * 
     * @param TfPreference $preference Instance of TfPreference, holding site preferences.
     */
    function __construct(TfValidator $validator, TfPreference $preference)
    {
        $this->validator = $validator; 
        $this->setTitle($preference->siteName);
        $this->setDescription($preference->siteDescription);
        $this->setAuthor($preference->siteAuthor);
        $this->setCopyright($preference->siteCopyright);
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
        $cleanProperty = $this->validator->trimString($property);
        
        if (isset($this->$cleanProperty)) {
            return htmlspecialchars((string) $this->$cleanProperty, ENT_QUOTES, "UTF-8",
                    false);
        } else {
            return null;
        }
    }
    
    /**
     * Sets the page meta title property.
     * 
     * @param string $value Page title.
     */
    public function setTitle(string $value)
    {
        $this->setProperty('title', $value);
    }
    
    /**
     * Sets the meta description property.
     * 
     * @param string $value Page description.
     */
    public function setDescription(string $value)
    {
        $this->setProperty('description', $value);
    }
    
    /**
     * Sets the page meta author property.
     * 
     * @param string $value Page author.
     */
    public function setAuthor(string $value)
    {
        $this->setProperty('author', $value);
    }
    
    /**
     * Sets the page meta copyright property.
     * 
     * @param string $value Page copyright.
     */
    public function setCopyright(string $value)
    {
        $this->setProperty('copyright', $value);
    }
    
    /**
     * Sets the meta generatorf (software used) property, which is not used in the default theme.
     * 
     * @param string $value Site generator.
     */
    public function setGenerator(string $value)
    {
        $this->setProperty('generator', $value);
    }
    
    /**
     * Sets the SEO-friendly URL string for this page.
     * 
     * @param string $value SEO string.
     */
    public function setSeo(string $value)
    {
        $this->setProperty('seo', $value);
    }
    
    /**
     * Sets the meta robots directive for this page.
     * 
     * @param string $value Robots directive.
     */
    public function setRobots(string $value)
    {
        $this->setProperty('robots', $value);
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
    private function setProperty(string $property, string $value)
    {
        $cleanProperty = $this->validator->trimString($property);
        $cleanValue = $this->validator->trimString($value);
        $this->$cleanProperty = htmlspecialchars($cleanValue, ENT_QUOTES, "UTF-8", false);
    }
       
}