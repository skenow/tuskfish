<?php

/**
 * TfMachine class file.
 * 
 * @copyright   Simon Wilkinson 2018+(https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@tuskfish.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Represents a remote machine or sensor that logs data to Tuskfish, typically an Internet of Things device.
 *
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */

class TfMachine
{
    
    use TfMagicMethods;
    
    protected $validator;
    protected $id = '';
    protected $type = '';
    protected $title = '';
    protected $teaser = '';
    protected $description = '';
    protected $parent = '';
    protected $tags = '';
    protected $online = '';
    protected $submissionTime = '';
    protected $lastUpdated = '';
    protected $counter = '';
    protected $metaTitle = '';
    protected $metaDescription = '';
    protected $seo = '';
    protected $handler = 'TfMachineHandler';
    protected $template = 'machine';
    protected $module = 'machines';
    protected $icon = '';
    
    public function __construct(TfValidator $validator)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the ID for this object.
     * 
     * @param int $id ID of this object.
     */
    public function setId(int $id)
    {
        if ($this->validator->isInt($id, 0)) {
            $this->id = $id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the type of machine.
     * 
     * Type must be the name of a machine subclass.
     * 
     * @param string $type Class name for this machine.
     */
    public function setType(string $type)
    {
        $cleanType = (string) $this->validator->trimString($type);

        if ($this->validator->isAlpha($cleanType)) {
            $this->type = $cleanType;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
        }
    }
    
    /**
     * Set the title of this machine.
     * 
     * @param string $title Title of this object.
     */
    public function setTitle(string $title)
    {
        $this->title = $this->validator->trimString($title);
    }
    
    /**
     * Set the teaser (short form description) for this machine.(HTML).
     * 
     * @param string $teaser Teaser (in HTML).
     */
    public function setTeaser(string $teaser) // HTML
    {
        $teaser = $this->validator->trimString($teaser);
        $this->teaser = $this->validator->filterHtml($teaser);
    }
    
    /**
     * Set the description of this object (HTML field).
     * 
     * @param string $description Description in HTML.
     */
    public function setDescription(string $description) // HTML
    {
        $description = $this->validator->trimString($description);
        $this->description = $this->validator->filterHtml($description);
    }
    
    /**
     * Set the ID of the parent for this object (must be a collection).
     * 
     * Parent ID must be different to machine ID (cannot declare self as parent).
     * 
     * @param int $parent ID of parent object.
     */
    public function setParent (int $parent)
    {
        if (!$this->validator->isInt($parent, 0)) {
                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }

        if ($parent === $this->id && $parent > 0) {
            trigger_error(TFISH_ERROR_CIRCULAR_PARENT_REFERENCE);
        } else {
            $this->parent = $parent;
        }
    }
    
    /**
     * Set the tags associated with this machine.
     * 
     * @param array $tags IDs of associated tags.
     */
    public function setTags(array $tags)
    {
        if ($this->validator->isArray($tags)) {
            $cleanTags = array();

            foreach ($tags as $tag) {
                $cleanTag = (int) $tag;

                if ($this->validator->isInt($cleanTag, 1)) {
                    $cleanTags[] = $cleanTag;
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
                unset($cleanTag);
            }

            $this->tags = $cleanTags;
        } else {
            trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
        }
    }
    
    /**
     * Set this machine as online (1) or offline (0).
     * 
     * Offline objects are not displayed on the front end or returned in search results.
     * 
     * @param int $online Online (1) or offline (0).
     */
    public function setOnline(int $online)
    {
        if ($this->validator->isInt($online, 0, 1)) {
            $this->online = $online;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the submission time for this machine (timestamp).
     * 
     * @param int $submissionTime Timestamp.
     */
    public function setSubmissionTime(int $submissionTime)
    {
        if ($this->validator->isInt($submissionTime, 1)) {
            $this->submissionTime = $submissionTime;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the last updated time for this machine (timestamp).
     * 
     * @param int $lastUpdated Timestamp.
     */
    public function setLastUpdated(int $lastUpdated)
    {
        if ($this->validator->isInt($lastUpdated, 0)) {
            $this->lastUpdated = $lastUpdated;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the view counter for this machine.
     * 
     * @param int $counter Counter value.
     */
    public function setCounter(int $counter)
    {
        if ($this->validator->isInt($counter, 0)) {
            $this->counter = $counter;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the meta title for this object.
     * 
     * @param string $metaTitle Meta title for this object.
     */
    public function setMetaTitle(string $metaTitle)
    {
        $this->metaTitle = $this->validator->trimString($metaTitle);
    }
    
    /**
     * Set the meta description for this machine.
     * 
     * @param string $metaDescription Meta description of this object.
     */
    public function setMetaDescription(string $metaDescription)
    {
        $this->metaDescription = $this->validator->trimString($metaDescription);
    }
    
    /**
     * Set the SEO-friendly search string for this machine.
     * 
     * The SEO string will be appended to the URL for this object.
     * 
     * @param string $seo Dash-separated-title-of-this-object.
     */
    public function setSeo(string $seo)
    {
        $cleanSeo = $this->validator->trimString($seo);
        $cleanSeo = str_replace(' ', '-', $cleanSeo);        
        $this->seo = $cleanSeo;
    }
    
    /**
     * Set the handler class for this machine type.
     * 
     * @param string $handler Handler name (alphabetical characters only).f
     */
    public function setHandler(string $handler)
    {
        $cleanHandler = $this->validator->trimString($handler);

        if ($this->validator->isAlpha($cleanHandler)) {
            $this->handler = $cleanHandler;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
        }
    }
    
    /**
     * Set the template file for displaying this machine.
     * 
     * The equivalent HTML template file must be present in the active theme.
     * 
     * @param string $template Template filename without extension, eg. 'camera'.
     */
    public function setTemplate(string $template)
    {
        $cleanTemplate = $this->validator->trimString($template);

        if ($this->validator->isAlnumUnderscore($cleanTemplate)) {
            $this->template = $cleanTemplate;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
        }
    }
    
    /**
     * Set the module for this machine.
     * 
     * Usually handled by the machine's constructor.
     * 
     * @param string $module Module name (alphabetical characters only).
     */
    public function setModule(string $module)
    {
        $cleanModule = $this->validator->trimString($module);
        
        if ($this->validator->isAlpha($module)) {
            $this->module = $cleanModule;
        }
    }
    
    /**
     * Set the icon for this machine.
     * 
     * This is a HTML field.
     * 
     * @param string $icon Icon expressed as a FontAwesome tag, eg. '<i class="fas fa-file-alt"></i>'
     */
    public function setIcon(string $icon) // HTML
    {
        $icon = $this->validator->trimString($icon);
        $this->icon = $this->validator->filterHtml($icon);
    }
        
}
