<?php

/**
 * TfMetadata class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Provides page-level metadata.
 * 
 * User-facing controller scripts can override the site-level defaults by uncommenting the options
 * at the bottom of each file. A good example of this is when viewing a single content object; if
 * it has the metaTitle and metaDescription fields set you can assign those to this object in order
 * to customise the page title and description to the object, thereby improving your SEO.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
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

    /**
     * Constructor.
     * 
     * @param TfValidator $validator An instance of the Tuskfish data validator class.
     * @param TfPreference $preference Instance of TfPreference, holding site preferences.
     */
    function __construct(TfValidator $validator, TfPreference $preference)
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
        $this->setDescription($preference->siteDescription);
        $this->setAuthor($preference->siteAuthor);
        $this->setCopyright($preference->siteCopyright);
        $this->setGenerator('Tuskfish CMS');
        $this->setSeo('');
        $this->setRobots('index,follow');
    }
    
    /**
     * Sets the page meta title property.
     * 
     * @param string $value Page title.
     */
    public function setTitle(string $value)
    {
        $this->title = $this->validator->trimString($value);
    }
    
    
    public function getTitle()
    {
        return $this->validator->escapeForXss($this->title);
    }
    
    /**
     * Sets the meta description property.
     * 
     * @param string $value Page description.
     */
    public function setDescription(string $value)
    {
        $this->description = $this->validator->trimString($value);
    }
    
    public function getDescription()
    {
        return $this->validator->escapeForXss($this->description);
    }
    
    /**
     * Sets the page meta author property.
     * 
     * @param string $value Page author.
     */
    public function setAuthor(string $value)
    {
        $this->author = $this->validator->trimString($value);
    }
    
    public function getAuthor()
    {
        return $this->validator->escapeForXss($this->author);
    }
    
    /**
     * Sets the page meta copyright property.
     * 
     * @param string $value Page copyright.
     */
    public function setCopyright(string $value)
    {
        $this->copyright = $this->validator->trimString($value);
    }
    
    public function getCopyright()
    {
        return $this->validator->escapeForXss($this->copyright);
    }
    
    /**
     * Sets the meta generatorf (software used) property, which is not used in the default theme.
     * 
     * @param string $value Site generator.
     */
    public function setGenerator(string $value)
    {
        $this->generator = $this->validator->trimString($value);
    }
    
    public function getGenerator()
    {
        return $this->validator->escapeForXss($this->generator);
    }
    
    /**
     * Sets the SEO-friendly URL string for this page.
     * 
     * @param string $value SEO string.
     */
    public function setSeo(string $value)
    {
        $this->seo = $this->validator->trimString($value);
    }
    
    public function getSeo()
    {
        return $this->validator->escapeForXss($this->seo);
    }
    
    /**
     * Sets the meta robots directive for this page.
     * 
     * @param string $value Robots directive.
     */
    public function setRobots(string $value)
    {
        $this->robots = $this->validator->trimString($value);
    }
    
    public function getRobots()
    {
        return $this->validator->escapeForXss($this->robots);
    }
       
}
