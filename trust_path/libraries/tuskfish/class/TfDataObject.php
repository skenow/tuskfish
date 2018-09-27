<?php

/**
 * TfDataObject class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1.2
 * @package     core
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Base class for data objects providing properties and methods that are standard in Tuskfish.
 *
 * I thought about making this a trait, since it will apply across otherwise unrelated families of
 * content objects, but I'm still lukewarm on using traits for anything other than static lists.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1.2
 * @package     core
 * @properties  int $id Auto-increment, set by database.
 * @properties  int $online Toggle object on or offline.
 * @properties  int $submissionTime Timestamp representing submission time.
 * @properties  int $lastUpdated Timestamp representing last time this object was updated.
 * @properties  int $expiresOn Timestamp indicating the expiry date for this object.
 * @properties  int $counter Number of times this content was viewed or downloaded.
 * @properties  string $metaTitle Set a custom page title for this content.
 * @properties  string $metaDescription Set a custom page meta description for this content.
 * @properties  string $seo SEO-friendly string; it will be appended to the URL for this content.
 * @properties  string $handler Handler for this object (not persistent).
 * @properties  string $template The template that should be used to display this object (not persistent).
 * @properties  string $module The module that handles this content type (not persistent).
 * @properties  string $icon The vector icon that represents this object type (not persistent).
 */
class TfDataObject
{

    /** Common properties. */
    protected $id = '';
    protected $submissionTime = '';
    protected $lastUpdated = '';
    protected $expiresOn = '';
    protected $counter = '';
    protected $online = '';
    protected $metaTitle = '';
    protected $metaDescription = '';
    protected $seo = '';
    protected $handler = '';
    protected $template = '';
    protected $module = '';
    protected $icon = '';

    /**
     * Common utilities.
     */
    
    /**
     * Convert URLs back to TFISH_LINK and back for insertion or update, to aid portability.
     * 
     * This is a helper method for loadPropertiesFromArray(). Only useful on HTML fields. Basically
     * it converts the base URL of your site to the TFISH_LINK constant for storage or vice versa
     * for display. If you change the base URL of your site (eg. domain) all your internal links
     * will automatically update when they are displayed.
     * 
     * @param string $html A HTML field that makes use of the TFISH_LINK constant.
     * @param bool $liveUrls Flag to convert urls to constants (true) or constants to urls (false).
     * @return string HTML field with converted URLs.
     */
    protected function convertBaseUrlToConstant(string $html, bool $liveUrls = false)
    {
        if ($liveUrls === true) {
            $html = str_replace(TFISH_LINK, 'TFISH_LINK', $html);
        } else {
                $html = str_replace('TFISH_LINK', TFISH_LINK, $html);
        }
        
        return $html;
    }

    /**
     * Generates a URL to access this object in single view mode.
     * 
     * URL can point relative to either the home page (index.php, or other custom content stream
     * page defined by modifying TFISH_PERMALINK_URL in config.php) or to an arbitrary page in the
     * web root. For example, you could rename index.php to 'blog.php' to free up the index page
     * for a landing page (this requires you to append the name of the new page to the 
     * TFISH_PERMALINK_URL constant).
     * 
     * @param string $customPage Use an arbitrary target page or the home page (index.php).
     * @return string URL to view this object.
     */
    public function getUrl(string $customPage = '')
    {
        $url = empty($customPage) ? TFISH_PERMALINK_URL : TFISH_URL;
        
        if ($customPage) {
            $url .= $this->validator->isAlnumUnderscore($customPage)
                    ? $this->validator->trimString($customPage) . '.php' : '';
        }
        
        $url .= '?id=' . (int) $this->id;
        
        if ($this->seo) {
            $url .= '&amp;title=' . $this->validator->encodeEscapeUrl($this->seo);
        }

        return $url;
    }
    
    /**
     * Reset the last updated time for this content object (timestamp).
     */
    public function updateLastUpdated()
    {
        $this->lastUpdated = time();
    }

    /**
     * Common accessors and mutators.
     */
    
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
     * Returns the ID of the content object, XSS safe.
     * 
     * @return int ID of content object.
     */
    public function getId()
    {
        return (int) $this->id;
    }
    
    /**
     * Set the submission time for this expert.
     * 
     * @param int $submissionTime Timestamp.
     */
    public function setSubmissionTime(int $submissionTime)
    {
        if ($this->validator->isInt($submissionTime, 0)) {
            $this->submissionTime = $submissionTime;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Return formatted date that this expert was submitted.
     * 
     * @return string Date/time of submission.
     */
    public function getSubmissionTime()
    {
        $date = date('j F Y', $this->$submissionTime);
        return $this->validator->escapeForXss($date);
    }
    
    /**
     * Set the time this expert was last updated.
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
     * Return formatted date/time this expert was last updated, escaped for display.
     * 
     * @return string Date/time last updated.
     */
    public function getLastUpdated()
    {
        $date = date('j F Y', $this->$lastUpdated);
        return $this->validator->escapeForXss($date);
    }
    
    /**
     * Set the time this expert profile expires.
     * 
     * @param int $expiresOn Timestamp.
     */
    public function setExpiresOn(int $expiresOn)
    {
        if ($this->validator->isInt($expiresOn, 0)) {
            $this->expiresOn = $expiresOn;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set this profile as online or offline.
     * 
     * Offline profiles are not publicly accessible and are not returned in search results.
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
     * Returns the XSS-safe online status of this record.
     * 
     * @return boolean True if online, false otherwise.
     */
    public function getOnline()
    {
        if ($this->online === 1) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Set the view counter for this profile.
     * 
     * @param int $counter Number of times this profile has been viewed.
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
     * Returns the number of times this expert was viewed, XSS safe.
     * 
     * @return int View counter.
     */
    public function getCounter()
    {
        return (int) $this->counter;
    }
    
    /**
     * Set the meta title for this expert.
     * 
     * @param string $metaTitle Meta title.
     */
    public function setMetaTitle(string $metaTitle)
    {
        $this->metaTitle = $this->validator->trimString($metaTitle);
    }
    
    /**
     * Returns the meta title for this expert XSS escaped for display.
     * 
     * @return string Meta title.
     */
    public function getMetaTitle()
    {
        return $this->validator->escapeForXss($this->metaTitle);
    }
    
    /**
     * Set the meta description for this expert.
     * 
     * @param string $metaDescription Meta description.
     */
    public function setMetaDescription(string $metaDescription)
    {
        $this->metaDescription = $this->validator->trimString($metaDescription);
    }
    
    /**
     * Return the meta description of this expert XSS escaped for display.
     * 
     * @return string Meta description.
     */
    public function getMetaDescription()
    {
        return $this->validator->escapeForXss($this->metaDescription);
    }
    
    /**
     * Set the SEO-friendly search string for this content object.
     * 
     * The SEO string will be appended to the URL for this object.
     * 
     * @param string $seo Dash-separated-title-of-this-object.
     */
    public function setSeo(string $seo)
    {
        $cleanSeo = (string) $this->validator->trimString($seo);

        // Replace spaces with dashes.
        if ($this->validator->isUtf8($cleanSeo)) {
            $cleanSeo = str_replace(' ', '-', $cleanSeo);
        } else {
            trigger_error(TFISH_ERROR_NOT_UTF8, E_USER_ERROR);
        }
        
        $this->seo = $cleanSeo;
    }
    
    /**
     * Return the SEO string for this expert XSS for display.
     * 
     * @return string SEO-friendly URL string.
     */
    public function getSeo()
    {
        return $this->validator->escapeForXss($this->seo);
    }
    
    /**
     * Set the handler class for this sensor type.
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
     * Set the module for this sensor.
     * 
     * Usually handled by the sensor's constructor.
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
     * Set the template file for displaying this sensor.
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
     * Set (override) the icon for this expert.
     * 
     * @param string $icon Icon expressed as a FontAwesome tag, eg. '<i class="fas fa-file-alt"></i>'
     */
    public function setIcon(string $icon)
    {
        $icon = $this->validator->trimString($icon);
        $this->icon = $this->validator->filterHtml($icon);
    }

    /**
     * Returns the Font Awesome icon for this expert, XSS safe (prevalidated with HTMLPurifier).
     * 
     * @return string FontAwesome icon for this expert (HTML).
     */
    public function getIcon()
    {
        return $this->icon;
    }
    
}
