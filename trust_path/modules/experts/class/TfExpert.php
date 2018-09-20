<?php

/**
 * Expert class file.
 * 
 * @copyright   Simon Wilkinson 2018+(https://isengard.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Your name <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     experts
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Represents the public profile of an expert.
 *
 * @copyright   Simon Wilkinson 2018+ (https://isengard.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     experts
 */

class TfExpert
{
    
    use TfExpertTrait;
    use TfLanguage;
    use TfMagicMethods;
    use TfMimetypes;
    
    protected $validator = 0;
    protected $id = '';
    protected $type = '';
    protected $salutation = '';
    protected $firstName = '';
    protected $midName = '';
    protected $lastName = '';
    protected $gender = '';
    protected $tags = array();
    protected $job = '';
    protected $experience = '';
    protected $projects = '';
    protected $publications = '';
    protected $businessUnit = '';
    protected $organisation = '';
    protected $address = '';
    protected $country = '';
    protected $email = '';
    protected $mobile = '';
    protected $fax = '';
    protected $profileLink = '';
    protected $submissionTime = '';
    protected $lastUpdated = '';
    protected $expiresOn = '';
    protected $image = '';
    protected $online = 1;
    protected $counter = 0;
    protected $metaTitle = '';
    protected $metaDescription = '';
    protected $seo = '';
    protected $handler = '';
    protected $template = 'expert';
    protected $module = 'experts';
    protected $icon = '<i class="fas fa-user"></i>';
    
    /**
     * Constructor.
     * 
     * @param TfValidator $validator Instance of the Tuskfish data validator class.
     */
    public function __construct(TfValidator $validator)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator;
        } else {
            trigger_error(TFISH_ERROR_NOT_VALIDATOR, E_USER_ERROR);
        }
        
        $this->setType(get_class($this));
        $this->setHandler($this->type . 'Handler');
    }
    
    /**
     * Escapes object properties for output to browser.
     * 
     * Use this method to retrieve object properties when you want to send them to the browser.
     * They will be automatically escaped with htmlspecialchars() to mitigate cross-site scripting
     * attacks.
     * 
     * Note that the method excludes the experience/project/publication/icon fields by default,
     * which are  returned unescaped; these are dedicated HTML fields that have been input-validated
     * with the HTMLPurifier library, and so *should* be safe. However, when editing these fields
     * it is necessary to escape them in order to prevent TinyMCE deleting them, as the '&' part of
     * entity encoding also needs to be escaped when in a textarea for some highly annoying reason.
     * 
     * @param string $property Name of property.
     * @param bool $escapeHtml Whether to escape HTML fields (teaser, description).
     * @return string|null Human readable value escaped for display or null if property does not
     * exist.
     */
    public function escapeForXss(string $property, bool $escapeHtml = false)
    {
        $cleanProperty = $this->validator->trimString($property);
        
        // If property is not set return null.
        if (!isset($this->$cleanProperty)) {
            return null;
        }
        
        // Format all data for display and convert TFISH_LINK to URL.
        $humanReadableData = (string) $this->makeDataHumanReadable($cleanProperty);
        
        // Output HTML for display: Do not escape as it has been input filtered with HTMLPurifier.
        $htmlFields = array('experience', 'projects', 'publications', 'icon');
        
        if (in_array($property, $htmlFields, true) && $escapeHtml === false) {
            return $humanReadableData;
        }
        
        // Output for display in the TinyMCE editor (edit mode): HTML must be DOUBLE
        // escaped to meet specification requirements.
        if (in_array($property, $htmlFields, true) && $escapeHtml === true) {    
            return htmlspecialchars($humanReadableData, ENT_NOQUOTES, 'UTF-8', 
                    true);
        }
                
        // All other cases: Escape data for display.        
        return htmlspecialchars($humanReadableData, ENT_NOQUOTES, 'UTF-8', false);
    }
    
    /**
     * Converts properties to human readable form in preparation for output.
     * 
     * Note that data processed by this function must be escaped for XSS before being sent to
     * display. You can use escapeForXSS().
     * 
     * @param string $property Name of property.
     * @return string Property formatted to human readable form for output.
     */
    protected function makeDataHumanReadable(string $cleanProperty)
    {        
        switch ($cleanProperty) {
            case "experience":
            case "projects":
            case "publications":
                // Do a simple string replace to allow TFISH_URL to be used as a constant.
                $tfUrlEnabled = str_replace('TFISH_LINK', TFISH_LINK, $this->$cleanProperty);
                return $tfUrlEnabled; 
                break;

            case "submissionTime":
            case "lastUpdated":
            case "expiresOn":
                $date = date('j F Y', $this->$cleanProperty);
                return $date;
                break;
            
            case "gender":
                $genderList = $this->getGenderList();
                return $genderList($this->$cleanProperty);
                break;
            
            case "salutation":
                $saluationList = $this->getSalutationList();
                return $salutationList[$this->$cleanProperty];
                break;
            /**case "tags":
                $tags = array();

                foreach ($this->$cleanProperty as $value) {
                    $tags[] = (int) $value;
                    unset($value);
                }

                return $tags;
                break;*/
                
            // No special handling required. Return unmodified value.
            default:
                return $this->$cleanProperty;
                break;
        }
    }
    
    /**
     * Converts an expert object to an array suitable for insert/update calls to the database.
     * 
     * @return array Array of object property/values.
     */
    public function convertObjectToArray()
    {        
        $keyValues = array();
        
        foreach ($this as $key => $value) {
            $keyValues[$key] = $value;
        }
        
        // Unset non-persistant properties that are not stored in the machine table.
        unset(
            $keyValues['validator'],
            $keyValues['icon'],
            $keyValues['handler'],
            $keyValues['module'],
            $keyValues['template']
        );
        
        return $keyValues;
    }
    
    /**
     * Resizes and caches an associated image and returns a URL to the cached copy.
     * 
     * Allows arbitrary sized thumbnails to be produced from the object's image property. These are
     * saved in the cache for future lookups. Image proportions are always preserved, so if both
     * width and height are specified, the larger dimension will take precedence for resizing and
     * the other will be ignored.
     * 
     * Usually, you want to produce an image of a specific width or (less commonly) height to meet
     * a template/presentation requirement.
     * 
     * Requires GD library.
     * 
     * @param int $width Width of the cached image output.
     * @param int $height Height of the cached image output.
     * @return string $url URL to the cached image.
     */
    public function getCachedImage(int $width = 0, int $height = 0)
    {
        // Validate parameters; and at least one must be set.
        $cleanWidth = $this->validator->isInt($width, 1) ? (int) $width : 0;
        $cleanHeight = $this->validator->isInt($height, 1) ? (int) $height : 0;
        
        if (!$cleanWidth && !$cleanHeight) {
            return false;
        }

        // Check if this object actually has an associated image, and that it is readable.
        if (!$this->image || !is_readable(TFISH_IMAGE_PATH . $this->image)) {
            return false;
        }

        // Check if a cached copy of the requested dimensions already exists in the cache and return
        // URL. CONVENTION: Thumbnail name should follow the pattern:
        // imageFileName . '-' . $width . 'x' . $height
        $filename = pathinfo($this->image, PATHINFO_FILENAME);
        $extension = '.' . pathinfo($this->image, PATHINFO_EXTENSION);
        $cachedPath = TFISH_PUBLIC_CACHE_PATH . $filename . '-';
        $cachedUrl = TFISH_CACHE_URL . $filename . '-';
        $originalPath = TFISH_IMAGE_PATH . $filename . $extension;
        
        if ($cleanWidth > $cleanHeight) {
            $cachedPath .= $cleanWidth . 'w' . $extension;
            $cachedUrl .= $cleanWidth . 'w' . $extension;
        } else {
            $cachedPath .= $cleanHeight . 'h' . $extension;
            $cachedUrl .= $cleanHeight . 'h' . $extension;
        }

        // Security check - is the cachedPath actually pointing at the cache directory? Because
        // if it isn't, then we don't want to cooperate by returning anything.
        if (is_readable($cachedPath)) {
            return $cachedUrl;
        }

        // Get the size. Note that:
        // $properties['mime'] holds the mimetype, eg. 'image/jpeg'.
        // $properties[0] = width, [1] = height, [2] = width = "x" height = "y" which is useful
        // for outputting size attribute.
        $properties = getimagesize($originalPath);

        if (!$properties) {
            return false;
        }

        // In order to preserve proportions, need to calculate the size of the other dimension.
        if ($cleanWidth > $cleanHeight) {
            $destinationWidth = $cleanWidth;
            $destinationHeight = (int) (($cleanWidth / $properties[0]) * $properties[1]);
        } else {
            $destinationWidth = (int) (($cleanHeight / $properties[1]) * $properties[0]);
            $destinationHeight = $cleanHeight;
        }
        
        $result = $this->scaleAndCacheImage($properties, $originalPath, $cachedPath, 
            $destinationWidth, $destinationHeight);
        
        if (!$result) {
            return false;
        }
        
        return $cachedUrl;
    }
    
    /**
     * Generates thumbnails of content->image property and saves them to the image cache.
     * 
     * @param array $properties Original image size properties as returned by getimagesize().
     * @param string $originalPath Path to the original image file stored on the server.
     * @param string $cachedPath Path to the scaled version of the image, stored in the image cache.
     * @param int $destinationWidth Width to scale image to.
     * @param int $destinationHeight Height to scale image to.
     * @return boolean True on success, false on failure.
     */
    private function scaleAndCacheImage(array $properties, string $originalPath,
            string $cachedPath, int $destinationWidth, int $destinationHeight)
    {
        // Create a blank (black) image RESOURCE of the specified size.
        $thumbnail = imagecreatetruecolor($destinationWidth, $destinationHeight);
        
        $result = false;

        switch ($properties['mime']) {
            case "image/jpeg":
                $original = imagecreatefromjpeg($originalPath);
                imagecopyresampled($thumbnail, $original, 0, 0, 0, 0, $destinationWidth,
                        $destinationHeight, $properties[0], $properties[1]);
                // Optional third quality argument 0-99, higher is better quality.
                $result = imagejpeg($thumbnail, $cachedPath, 80);
                break;

            case "image/png":
            case "image/gif":
                if ($properties['mime'] === "image/gif") {
                    $original = imagecreatefromgif($originalPath);
                } else {
                    $original = imagecreatefrompng($originalPath);
                }

                /**
                 * Handle transparency
                 * 
                 * The following code block (only) is a derivative of
                 * the PHP_image_resize project by Nimrod007, which is a fork of the
                 * smart_resize_image project by Maxim Chernyak. The source code is available
                 * from the link below, and it is distributed under the following license terms:
                 * 
                 * Copyright © 2008 Maxim Chernyak
                 * 
                 * Permission is hereby granted, free of charge, to any person obtaining a copy
                 * of this software and associated documentation files (the "Software"), to deal
                 * in the Software without restriction, including without limitation the rights
                 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
                 * copies of the Software, and to permit persons to whom the Software is
                 * furnished to do so, subject to the following conditions:
                 * 
                 * The above copyright notice and this permission notice shall be included in
                 * all copies or substantial portions of the Software.
                 * 
                 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
                 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
                 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
                 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
                 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
                 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
                 * THE SOFTWARE.
                 * 
                 * https://github.com/Nimrod007/PHP_image_resize 
                 */
                // Sets the transparent colour in the given image, using a colour identifier
                // created with imagecolorallocate().
                $transparency = imagecolortransparent($original);
                $numberOfColours = imagecolorstotal($original);

                if ($transparency >= 0 && $transparency < $numberOfColours) {
                    // Get the colours for an index.
                    $transparentColour = imagecolorsforindex($original, $transparency);
                    // Allocate a colour for an image. The first call to imagecolorallocate() 
                    // fills the background colour in palette-based images created using 
                    // imagecreate().
                    $transparency = imagecolorallocate($thumbnail, $transparentColour['red'],
                            $transparentColour['green'], $transparentColour['blue']);
                    // Flood fill with the given colour starting at the given coordinate
                    // (0,0 is top left).
                    imagefill($thumbnail, 0, 0, $transparency);
                    // Define a colour as transparent.
                    imagecolortransparent($thumbnail, $transparency);
                }

                // Bugfix from original: Changed next block to be an independent if, instead of
                // an elseif linked to previous block. Otherwise PNG transparency doesn't work.
                if ($properties['mime'] === "image/png") {
                    // Set the blending mode for an image.
                    imagealphablending($thumbnail, false);
                    // Allocate a colour for an image ($image, $red, $green, $blue, $alpha).
                    $colour = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
                    // Flood fill again.
                    imagefill($thumbnail, 0, 0, $colour);
                    // Set the flag to save full alpha channel information (as opposed to single
                    // colour transparency) when saving png images.
                    imagesavealpha($thumbnail, true);
                }
                /**
                 * End code derived from PHP_image_resize project.
                 */

                // Copy and resize part of an image with resampling.
                imagecopyresampled($thumbnail, $original, 0, 0, 0, 0, $destinationWidth,
                        $destinationHeight, $properties[0], $properties[1]);

                // Output a useable png or gif from the image resource.
                if ($properties['mime'] === "image/gif") {
                    $result = imagegif($thumbnail, $cachedPath);
                } else {
                    // Quality is controlled through an optional third argument (0-9, lower is
                    // better).
                    $result = imagepng($thumbnail, $cachedPath, 0);
                }
                break;

            // Anything else, no can do.
            default:
                return false;
        }

        if (!$result) {
            return false;
        }
        
        imagedestroy($thumbnail); // Free memory.
        
        return true;
    }
    
    /**
     * Returns an array of image mimetypes that are permitted for content objects.
     * 
     * @return array Array of permitted image mimetypes in file extension => mimetype format.
     */
    public function getListOfAllowedImageMimetypes()
    {
        return array(
            "gif" => "image/gif",
            "jpg" => "image/jpeg",
            "png" => "image/png"
        );
    }
    
    /**
     * Returns the full name of an expert XSS escaped for display.
     * 
     * @return string Full name of expert.
     */
    public function getName()
    {
        $salutationList = $this->getSalutationList();
        
        $name = '';
        
        if ($this->salutation) $name .= $salutationList[$this->salutation] . ' ';
        if ($this->firstName) $name .= $this->firstName . ' ';
        if ($this->midName) $name .= $this->midName . ' ';
        if ($this->lastName) $name .= $this->lastName;
        
        $cleanName = $this->validator->escapeForXss($name);
        
        return $cleanName;
    }
    
    /**
     * Returns the job title XSS escaped for display.
     * 
     * @return string Job title
     */
    public function getJob()
    {
        $job = '';
        
        if ($this->job) $job = $this->validator->escapeforXss($this->job);
        
        return $job;
    }
    
    /**
     * returns the business unit XSS escaped for display.
     * 
     * @return string
     */
    public function getBusinessUnit()
    {
        $businessUnit = '';
        
        if ($this->businessUnit) $businessUnit = $this->validator->escapeForXss($this->businessUnit);
        
        return $businessUnit;
    }
    
    /**
     * Returns the organisation XSS escaped for display.
     * 
     * @return string
     */
    public function getOrganisation()
    {
        $organisation = '';
        
        if ($this->organisation) $organisation = $this->validator->escapeForXss($this->organisation);
        
        return $organisation;
    }
    
    /**
     * Returns full name and job title, comma seperated.
     * 
     * @return string Name and job title
     */
    public function getNameAndJob()
    {
        $nameAndJob = '';
        
        $name = $this->getName();
        $job = $this->getJob();
        
        $nameAndJob .= $name;
        
        if ($name && $job) {
            $nameAndJob .= ', ';
        }
        
        $nameAndJob .= $job;
        
        return $nameAndJob;
    }
    
    public function getAffiliation()
    {
        $affiliation = '';
        
        $businessUnit = $this->getBusinessUnit();
        $organisation = $this->getOrganisation();
        
        $affiliation = $businessUnit;
        
        if ($businessUnit && $organisation) {
            $affiliation .= ', ';
        }
        
        $affiliation .= $organisation;
        
        return $affiliation;
    }
    
    public function getContactDetails()
    {
        $contactDetails = array();
        
        if ($this->address) {
            $contactDetails[] = nl2br($this->address);
        }
        
        $countryList = $this->getCountryList();
        
        if ($this->country) {
            $contactDetails[] = mb_strtoupper($countryList[$this->country]);
        }
        
        if ($this->mobile) {
            $contactDetails[] = TFISH_EXPERTS_MOBILE . ': ' . $this->mobile; 
        }
        
        if ($this->fax) {
            $contactDetails[] = TFISH_EXPERTS_FAX . ': ' . $this->fax;
        }
        
        if ($this->email) {
            $contactDetails[] = TFISH_EXPERTS_EMAIL . ': ' . $this->email;
        }
        
        $contactDetails = implode('<br />', $contactDetails);
        $cleanContactDetails = $this->validator->escapeForXSS($contactDetails);
        
        return $contactDetails;
    }
    
    /**
     * Returns a whitelist of object properties whose values are allowed be set.
     * 
     * This function is used to build a list of $allowedVars for a content object. Child classes
     * use this list to unset properties they do not use. Properties that are not resident in the
     * database are also unset here (handler, template, module and icon).
     * 
     * @return array Array of object properties as keys.
     */
    public function getPropertyWhitelist()
    {        
        $properties = array();
        
        foreach ($this as $key => $value) {
            $properties[$key] = '';
        }
        
        unset($properties['validator'], $properties['handler'], $properties['template'],
                $properties['module'], $properties['icon']);
        
        return $properties;
    }
    
    /**
     * Populates the properties of the object from external (untrusted) data source.
     * 
     * Note that the supplied data is internally validated by __set().
     * 
     * @param array $dirtyInput Usually raw form $_REQUEST data.
     * @param bool $liveUrls Convert base url to TFISH_LINK (true) or TFISH_LINK to base url (false).
     */
    public function loadPropertiesFromArray(array $dirtyInput, $liveUrls = true)
    {
        $deleteImage = (isset($dirtyInput['deleteImage']) && !empty($dirtyInput['deleteImage']))
                ? true : false;
        $deleteMedia = (isset($dirtyInput['deleteMedia']) && !empty($dirtyInput['deleteMedia']))
                ? true : false;
        
        $this->loadProperties($dirtyInput);

        // Convert URLs back to TFISH_LINK for insertion or update, to aid portability.        
        if (isset($this->experience) && !empty($dirtyInput['experience'])) {
            $experience = $this->convertBaseUrlToConstant($dirtyInput['experience'], $liveUrls);            
            $this->setExperience($experience);
        }
        
        if (isset($this->projects) && !empty($dirtyInput['projects'])) {
            $projects = $this->convertBaseUrlToConstant($dirtyInput['projects'], $liveUrls);            
            $this->setProjects($projects);
        }

        if (isset($this->publications) && !empty($dirtyInput['publications'])) {
            $publications = $this->convertBaseUrlToConstant($dirtyInput['publications'], $liveUrls);            
            $this->setPublications($publications);
        }

        $propertyWhitelist = $this->getPropertyWhitelist();
        $this->loadImage($propertyWhitelist);
    }
    
    /**
     * Assign form data to expert object.
     * 
     * Note that data validation is carried out internally via the setters. This is a helper method
     * for loadPropertiesFromArray().
     * 
     * @param array $dirtyInput Array of untrusted form input.
     */
    private function loadProperties(array $dirtyInput)
    {
        if (isset($this->id) && isset($dirtyInput['id']))
            $this->setId((int) $dirtyInput['id']);
        if (isset($this->type) && isset($dirtyInput['type']))
            $this->setType((string) $dirtyInput['type']);
        if (isset($this->salutation) && isset($dirtyInput['salutation']))
            $this->setSalutation((int) $dirtyInput['salutation']);
        if (isset($this->firstName) && isset($dirtyInput['firstName']))
            $this->setFirstName((string) $dirtyInput['firstName']);
        if (isset($this->midName) && isset($dirtyInput['midName']))
            $this->setMidName((string) $dirtyInput['midName']);
        if (isset($this->lastName) && isset($dirtyInput['lastName']))
            $this->setLastName((string) $dirtyInput['lastName']);
        if (isset($this->gender) && isset($dirtyInput['gender']))
            $this->setGender((int) $dirtyInput['gender']);
        if (isset($this->job) && isset($dirtyInput['job']))
            $this->setJob((string) $dirtyInput['job']);
        if (isset($this->experience) && isset($dirtyInput['experience']))
            $this->setExperience((string) $dirtyInput['experience']);
        if (isset($this->projects) && isset($dirtyInput['projects']))
            $this->setProjects((string) $dirtyInput['projects']);
        if (isset($this->publications) && isset($dirtyInput['publications']))
            $this->setPublications((string) $dirtyInput['publications']);
        if (isset($this->businessUnit) && isset($dirtyInput['businessUnit']))
            $this->setbusinessUnit((string) $dirtyInput['businessUnit']);
        if (isset($this->organisation) && isset($dirtyInput['organisation']))
            $this->setOrganisation((string) $dirtyInput['organisation']);
        if (isset($this->address) && isset($dirtyInput['address']))
            $this->setAddress((string) $dirtyInput['address']);
        if (isset($this->country) && isset($dirtyInput['country']))
            $this->setCountry((int) $dirtyInput['country']);
        if (isset($this->email) && isset($dirtyInput['email']))
            $this->setEmail((string) $dirtyInput['email']);
        if (isset($this->mobile) && isset($dirtyInput['mobile']))
            $this->setMobile((string) $dirtyInput['mobile']);
        if (isset($this->fax) && isset($dirtyInput['fax']))
            $this->setFax((string) $dirtyInput['fax']);
        if (isset($this->profileLink) && isset($dirtyInput['profileLink']))
            $this->setProfileLink((string) $dirtyInput['profileLink']);
        if (isset($this->image) && isset($dirtyInput['image']))
            $this->setImage((string) $dirtyInput['image']);
        if (isset($this->tags) && isset($dirtyInput['tags']))
            $this->setTags((array) $dirtyInput['tags']);
        if (isset($this->online) && isset($dirtyInput['online']))
            $this->setOnline((int) $dirtyInput['online']);
        if (isset($this->submissionTime) && isset($dirtyInput['submissionTime']))
            $this->setSubmissionTime((int) $dirtyInput['submissionTime']);
        if (isset($this->lastUpdated) && isset($dirtyInput['lastUpdated']))
            $this->setLastUpdated((int) $dirtyInput['lastUpdated']);
        if (isset($this->expiresOn) && isset($dirtyInput['expiresOn']))
            $this->setExpiresOn((int) $dirtyInput['expiresOn']);
        if (isset($this->counter) && isset($dirtyInput['counter']))
            $this->setCounter((int) $dirtyInput['counter']);
        if (isset($this->online) && isset($dirtyInput['online']))
            $this->setOnline((int) $dirtyInput['online']);
        if (isset($this->metaTitle) && isset($dirtyInput['metaTitle']))
            $this->setMetaTitle((string) $dirtyInput['metaTitle']);
        if (isset($this->metaDescription) && isset($dirtyInput['metaDescription']))
            $this->setMetaDescription((string) $dirtyInput['metaDescription']);
        if (isset($this->seo) && isset($dirtyInput['seo']))
            $this->setSeo((string) $dirtyInput['seo']);
    }
    
    /**
     * Sets the image property from untrusted form data.
     * 
     * This is a helper method for loadPropertiesFromArray(). 
     * 
     * @param array $propertyWhitelist List of permitted object properties.
     */
    private function loadImage(array $propertyWhitelist)
    {
        if (array_key_exists('image', $propertyWhitelist) && !empty($_FILES['image']['name'])) {
            $cleanImageFilename = $this->validator->trimString($_FILES['image']['name']);
            
            if ($cleanImageFilename) {
                $this->setImage($cleanImageFilename);
            }
        }
    }
    
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
    private function convertBaseUrlToConstant(string $html, bool $liveUrls = false)
    {
        if ($liveUrls === true) {
            $html = str_replace(TFISH_LINK, 'TFISH_LINK', $html);
        } else {
                $html = str_replace('TFISH_LINK', TFISH_LINK, $html);
        }
        
        return $html;
    }
    
    /**
     * Set the ID of this expert.
     * 
     * @param int $id ID of this expert.
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
     * Set the type of expert.
     * 
     * @param string $type Expert type.
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
     * Set the salutation (Dr, Prof. etc) for this expert.
     * 
     * @param int $salutation Key of relevant salutation.
     */
    public function setSalutation(int $salutation)
    {
        if ($this->validator->isInt($salutation, 0)) {
            $this->salutation = $salutation;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the first name of this expert.
     * 
     * @param string $firstName First (given) name.
     */
    public function setFirstName(string $firstName)
    {
        $this->firstName = $this->validator->trimString($firstName);
    }
    
    /**
     * Set the middle name(s) of this expert.
     * 
     * @param string $midName Middle name(s) of this expert.
     */
    public function setMidName(string $midName)
    {
        $this->midName = $this->validator->trimString($midName);
    }
    
    /**
     * Set the last name of this expert.
     * 
     * @param string $lastName Last name (surname) of this expert.
     */
    public function setLastName(string $lastName)
    {
        $this->lastName = $this->validator->trimString($lastName);
    }
    
    /**
     * Set the gender of this expert.
     * 
     * @param int $gender Key for the relevant gender (0 male, 1 female, 2 unknown).
     */
    public function setGender(int $gender)
    {
        if ($this->validator->isInt($gender, 0, 2)) {
            $this->gender = $gender;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the tags associated with this expert.
     * 
     * @param array $tags Array of tags as key = > value.
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
     * Set the job title of this expert.
     * 
     * @param string $job Job title.
     */
    public function setJob(string $job)
    {
        $this->job = $this->validator->trimString($job);
    }
    
    /**
     * Set the experience description for this expert.
     * 
     * @param string $experience Summary description of experience (HTML).
     */
    public function setExperience(string $experience)
    {
        $experience = $this->validator->trimString($experience);
        $this->experience = $this->validator->filterHtml($experience);
    }
    
    /**
     * Set the projects description for this expert.
     * 
     * @param string $projects Description of recent projects (HTML).
     */
    public function setProjects(string $projects)
    {
        $projects = $this->validator->trimString($projects);
        $this->projects = $this->validator->filterHtml($projects);
    }
    
    /**
     * Set the publications description for this expert.
     * 
     * @param string $publications Description of key publications (HTML).
     */
    public function setPublications(string $publications)
    {
        $publications = $this->validator->trimString($publications);
        $this->publications = $this->validator->filterHtml($publications);
    }
    
    /**
     * Set the business unit for this expert.
     * 
     * @param string $businessUnit The direct administrative unit this expert works for.
     */
    public function setBusinessUnit(string $businessUnit)
    {
        $this->businessUnit = $this->validator->trimString($businessUnit);
    }
    
    /**
     * Set the organisation this expert belongs to.
     * 
     * @param string $organisation The higher level (main) organisation this expert works form.
     */
    public function setOrganisation(string $organisation)
    {
        $this->organisation = $this->validator->trimString($organisation);
    }
    
    /**
     * Set the postal address of this expert (street, city, state, postcode).
     * 
     * @param string $address Address of this expert.
     */
    public function setAddress(string $address)
    {
        $this->address = $this->validator->trimString($address);
    }
    
    /**
     * Set the country of residence for this expert.
     * 
     * @param int $country Key of relevant country from list.
     */
    public function setCountry (int $country)
    {
        if ($this->validator->isInt($country, 0)) {
            $this->country = $country;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    /**
     * Set the email address of this expert.
     * 
     * @param string $email Valid email address.
     */
    public function setEmail(string $email)
    {
        $cleanEmail = $this->validator->trimString($email);
        
        if ($cleanEmail === '' || $this->validator->isEmail($cleanEmail)) {
            $this->email = $cleanEmail;
        } else {
            trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
        }
    }
    
    /**
     * Set the mobile phone number for this expert.
     * 
     * @param string $mobile Mobile phone number.
     */
    public function setMobile(string $mobile)
    {
        $this->mobile = $this->validator->trimString($mobile);
    }
    
    /**
     * Set the fax number of this expert.
     * 
     * @param string $fax Fax number.
     */
    public function setFax(string $fax)
    {
        $this->fax = $this->validator->trimString($fax);
    }
    
    /**
     * Set the personal profile link of this expert (their blog or social media page).
     * 
     * @param string $url URL to the expert's personal website.
     */
    public function setProfileLink(string $url)
    {
        $cleanUrl = $this->validator->trimString($url);
        
        if ($cleanUrl === '' || $this->validator->isUrl($cleanUrl)) {
            $this->profileLink = $cleanUrl;
        } else {
            trigger_error(TFISH_ERROR_NOT_URL, E_USER_ERROR);
        }
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
     * Set the profile photo for this expert
     * 
     * @param string $image Filename of photo.
     */
    public function setImage(string $image)
    {
        $image = (string) $this->validator->trimString($image);
        
        // Check image/media paths for directory traversals and null byte injection.
        if ($this->validator->hasTraversalorNullByte($image)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            exit; // Hard stop due to high probability of abuse.
        }

        // Check image file is a permitted mimetype.
        $mimetypeWhitelist = $this->getListOfAllowedImageMimetypes();
        $extension = mb_strtolower(pathinfo($image, PATHINFO_EXTENSION), 'UTF-8');
        
        if (!empty($extension) && !array_key_exists($extension, $mimetypeWhitelist)) {
            $this->image = '';
            trigger_error(TFISH_ERROR_ILLEGAL_MIMETYPE, E_USER_ERROR);
        } else {
            $this->image = $image;
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
     * Set the handler for this expert object or subclass.
     *  
     * @param string $handler Name of the handler for this Expert or subclass.
     */
    public function setHandler(string $handler)
    {
        $cleanHandler = $this->validator->trimString($handler);
        $this->handler = $cleanHandler;
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
     * Set the meta description for this expert.
     * 
     * @param string $metaDescription Meta description.
     */
    public function setMetaDescription(string $metaDescription)
    {
        $this->metaDescription = $this->validator->trimString($metaDescription);
    }
    
    /**
     * Set the SEO-friendly search string for this expert.
     * 
     * Suggest to use the full salutation / name of the expert, eg. dr-joe-bloggs
     * 
     * @param string $seo Hyphen-delimited search string.
     */
    public function setSeo(string $seo)
    {
        $this->seo = $this->validator->trimString($seo);
    }
    
    /**
     * Reset the last updated time for this content object (timestamp).
     */
    public function updateLastUpdated()
    {
        $this->lastUpdated = time();
    }
    
}
