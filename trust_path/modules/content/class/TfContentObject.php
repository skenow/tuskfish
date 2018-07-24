<?php

/**
 * TfContentObject class file.
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
 * Parent content object class, represents a single content object.
 *
 * There is only one 'archtype' of content object in Tuskfish; it uses a subset of standard
 * Dublin Core metadata fields plus a few more that are common to most content objects. Why? If you
 * look at most common content types - articles, photos, downloads etc. - you will see that for the
 * most part they all use the same fields. For example, everything has a title, everything has a
 * description, everything has an author, everything has a hit counter.
 * 
 * Traditionally, most modular CMS create a separate database table for every type of content so you
 * get duplication of column names across tables. And it works just fine until you want to publish
 * a single content stream containing different kinds of content objects. Then, suddenly, your
 * queries are full of complex joins and other rubbish and it becomes very painful to work with.
 * 
 * By using a single table for content objects with common field names our queries become very
 * simple and much redundancy is avoided. Of course, some types of content might not require a few
 * particular properties; and so subclassed content types simply unset() any properties that they 
 * don't need in their constructor.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @properties  int $id Auto-increment, set by database.
 * @properties  string $type Content object type eg. TfArticle etc. [ALPHA]
 * @properties  string $title The name of this content.
 * @properties  string $teaser A short (one paragraph) summary or abstract for this content. [HTML]
 * @properties  string $description The full article or description of the content. [HTML]
 * @properties  string $media An associated download/audio/video file. [FILEPATH OR URL]
 * @properties  string $format Mimetype
 * @properties  string $fileSize Specify in bytes.
 * @properties  string $creator Author.
 * @properties  string image An associated image file, eg. a screenshot a good way to handle it. [FILEPATH OR URL]
 * @properties  string $caption Caption of the image file.
 * @properties  string $date Date of publication expressed as a string.
 * @properties  int $parent A source work or collection of which this content is part.
 * @properties  string $language Future proofing.
 * @properties  int $rights Intellectual property rights scheme or license under which the work is distributed.
 * @properties  string $publisher The entity responsible for distributing this work.
 * @properties  array $tags Tag IDs associated with this object; not persistent (stored as taglinks in taglinks table).
 * @properties  int $online Toggle object on or offline.
 * @properties  int $submissionTime Timestamp representing submission time.
 * @properties  int $counter Number of times this content was viewed or downloaded.
 * @properties  string $metaTitle Set a custom page title for this content.
 * @properties  string $metaDescription Set a custom page meta description for this content.
 * @properties  string $seo SEO-friendly string; it will be appended to the URL for this content.
 * @properties  string $handler Handler for this object (not persistent).
 * @properties  string $template The template that should be used to display this object (not persistent).
 * @properties  string $module The module that handles this content type (not persistent).
 * @properties  string $icon The vector icon that represents this object type (not persistent).
 */
class TfContentObject
{
    
    use TfLanguage;
    use TfMagicMethods;
    use TfMimetypes;

    /** @var array Holds values of permitted preference object properties. */
    protected $validator;
    
    protected $id = '';
    protected $type = '';
    protected $title = '';
    protected $teaser = '';
    protected $description = '';
    protected $media = '';
    protected $format = '';
    protected $fileSize = '';
    protected $creator = '';
    protected $image = '';
    protected $caption = '';
    protected $date = '';
    protected $parent = '';
    protected $language = '';
    protected $rights = '';
    protected $publisher = '';
    protected $tags = '';
    protected $online = '';
    protected $submissionTime = '';
    protected $counter = '';
    protected $metaTitle = '';
    protected $metaDescription = '';
    protected $seo = '';
    protected $handler = '';
    protected $template = '';
    protected $module = '';
    protected $icon = '';
    
    /** Initialise default content object properties and values. */
    function __construct(TfValidator $tfValidator)
    {
        if (is_a($tfValidator, 'TfValidator')) {
            $this->validator = $tfValidator;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
               
        /**
         * Set default values of permitted properties.
         */
        $this->setId(0);
        $this->setType(get_class($this));
        $this->setHandler($this->type . 'Handler');
        $this->setRights(1);
        $this->setOnline(1);
        $this->setCounter(0);
        $this->setTags(array());
    }
    
    /**
     * Converts a content object to an array suitable for insert/update calls to the database.
     * 
     * Note that the returned array observes the PARENT object's getPropertyWhitelist() as a 
     * restriction on the setting of keys. This whitelist explicitly excludes the handler, 
     * template and module properties as these are part of the class definition and are not stored
     * in the database. Calling the parent's property whitelist ensures that properties that are
     * unset by child classes are zeroed (this is important when an object is changed to a
     * different subclass, as the properties used may differ).
     * 
     * @return array Array of object property/values.
     */
    public function convertObjectToArray()
    {        
        $keyValues = array();
        
        foreach ($this as $key => $value) {
            $keyValues[$key] = $value;
        }
        
        // Unset non-persistanet properties that are not stored in the content table.
        unset(
            $keyValues['tags'],
            $keyValues['icon'],
            $keyValues['handler'],
            $keyValues['module'],
            $keyValues['template']
            );
        
        return $keyValues;
    }
    
    /**
     * Escapes object properties for output to browser.
     * 
     * Use this method to retrieve object properties when you want to send them to the browser.
     * They will be automatically escaped with htmlspecialchars() to mitigate cross-site scripting
     * attacks.
     * 
     * Note that the method excludes the teaser and description fields by default, which are 
     * returned unescaped; these are dedicated HTML fields that have been input-validated
     * with the HTMLPurifier library, and so *should* be safe. However, when editing these fields
     * it is necessary to escape them in order to prevent TinyMCE deleting them, as the '&' part of
     * entity encoding also needs to be escaped when in a textarea for some highly annoying reason.
     * 
     * @param string $property Name of property.
     * @param bool $escape_html Whether to escape HTML fields (teaser, description).
     * @return string Human readable value escaped for display.
     */
    public function escapeForXss(string $property, bool $escape_html = false)
    {
        $cleanProperty = $this->validator->trimString($property);
        
        // If property is not set return null.
        if (!isset($this->$cleanProperty)) {
            return null;
        }
        
        // Format all data for display and convert TFISH_LINK to URL.
        $human_readable_data = (string) $this->makeDataHumanReadable($cleanProperty);
        
                $html_fields = array('teaser', 'description', 'icon');
        
        // Output HTML for display: Do not escape as it has been input filtered with HTMLPurifier.
        if (in_array($property, $html_fields, true) && $escape_html === false) {
            return $human_readable_data;
        }
        
        // Output for display in the TinyMCE editor (edit mode): HTML must be DOUBLE
        // escaped to meet specification requirements.
        if (in_array($property, $html_fields, true) && $escape_html === true) {    
            return htmlspecialchars($human_readable_data, ENT_NOQUOTES, 'UTF-8', 
                    true);
        }
                
        // All other cases: Escape data for display.        
        return htmlspecialchars($human_readable_data, ENT_NOQUOTES, 'UTF-8', false);
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
     * @param int $width Width of the cached image output.
     * @param int $height Height of the cached image output.
     * @return string $url URL to the cached image.
     */
    public function getCachedImage(int $width = 0, int $height = 0)
    {
        // Validate parameters; and at least one must be set.
        $clean_width = $this->validator->isInt($width, 1) ? (int) $width : 0;
        $clean_height = $this->validator->isInt($height, 1) ? (int) $height : 0;
        
        if (!$clean_width && !$clean_height) {
            return false;
        }

        // Check if this object actually has an associated image, and that it is readable.
        if (!$this->image || !is_readable(TFISH_IMAGE_PATH . $this->image)) {
            return false;
        }

        // Check if a cached copy of the requested dimensions already exists in the cache and return
        // URL. CONVENTION: Thumbnail name should follow the pattern:
        // image_file_name . '-' . $width . 'x' . $height
        $filename = pathinfo($this->image, PATHINFO_FILENAME);
        $extension = '.' . pathinfo($this->image, PATHINFO_EXTENSION);
        $cached_path = TFISH_PUBLIC_CACHE_PATH . $filename . '-';
        $cached_url = TFISH_CACHE_URL . $filename . '-';
        $original_path = TFISH_IMAGE_PATH . $filename . $extension;
        
        if ($clean_width > $clean_height) {
            $cached_path .= $clean_width . 'w' . $extension;
            $cached_url .= $clean_width . 'w' . $extension;
        } else {
            $cached_path .= $clean_height . 'h' . $extension;
            $cached_url .= $clean_height . 'h' . $extension;
        }

        // Security check - is the cached_path actually pointing at the cache directory? Because
        // if it isn't, then we don't want to cooperate by returning anything.
        if (is_readable($cached_path)) {
            return $cached_url;
        } else {

            // Get the size. Note that:
            // $properties['mime'] holds the mimetype, eg. 'image/jpeg'.
            // $properties[0] = width, [1] = height, [2] = width = "x" height = "y" which is useful
            // for outputting size attribute.
            $properties = getimagesize($original_path);
            
            if (!$properties) {
                return false;
            }

            /**
             * Resizing image with GD installed.
             */
            // In order to preserve proportions, need to calculate the size of the other dimension.
            if ($clean_width > $clean_height) {
                $destination_width = $clean_width;
                $destination_height = (int) (($clean_width / $properties[0]) * $properties[1]);
            } else {
                $destination_width = (int) (($clean_height / $properties[1]) * $properties[0]);
                $destination_height = $clean_height;
            }

            // Get a reference to a new image resource.
            // Creates a blank (black) image RESOURCE of the specified size.
            $thumbnail = imagecreatetruecolor($destination_width, $destination_height);
            // Different image types require different handling. JPEG and PNG support optional
            // quality parameter
            // TODO: Create a preference.
            $result = false;
            
            switch ($properties['mime']) {
                case "image/jpeg":
                    $original = imagecreatefromjpeg($original_path);
                    imagecopyresampled($thumbnail, $original, 0, 0, 0, 0, $destination_width,
                            $destination_height, $properties[0], $properties[1]);
                    // Optional third quality argument 0-99, higher is better quality.
                    $result = imagejpeg($thumbnail, $cached_path, 80);
                    break;

                case "image/png":
                case "image/gif":
                    if ($properties['mime'] === "image/gif") {
                        $original = imagecreatefromgif($original_path);
                    } else {
                        $original = imagecreatefrompng($original_path);
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
                    $number_of_colours = imagecolorstotal($original);

                    if ($transparency >= 0 && $transparency < $number_of_colours) {
                        // Get the colours for an index.
                        $transparent_colour = imagecolorsforindex($original, $transparency);
                        // Allocate a colour for an image. The first call to imagecolorallocate() 
                        // fills the background colour in palette-based images created using 
                        // imagecreate().
                        $transparency = imagecolorallocate($thumbnail, $transparent_colour['red'],
                                $transparent_colour['green'], $transparent_colour['blue']);
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
                    imagecopyresampled($thumbnail, $original, 0, 0, 0, 0, $destination_width,
                            $destination_height, $properties[0], $properties[1]);

                    // Output a useable png or gif from the image resource.
                    if ($properties['mime'] === "image/gif") {
                        $result = imagegif($thumbnail, $cached_path);
                    } else {
                        // Quality is controlled through an optional third argument (0-9, lower is
                        // better).
                        $result = imagepng($thumbnail, $cached_path, 0);
                    }
                    break;

                // Anything else, no can do.
                default:
                    return false;
            }

            if ($result) {
                imagedestroy($thumbnail); // Free memory.
                return $cached_url; // Return the URL to the cached file.
            } else {
                return false;
            }

            return $cached_url;
        }
    }
    
    /**
     * Returns an array of audio mimetypes that are permitted for content objects.
     * 
     * Note that ogg audio files should use the .oga extension, although the legacy .ogg extension
     * is still acceptable, although it must no longer be used for video files.
     * 
     * @return array Array of permitted audio mimetypes in file extension => mimetype format.
     */
    public function getListOfAllowedAudioMimetypes()
    {
        return array(
            "mp3" => "audio/mpeg",
            "oga" => "audio/ogg",
            "ogg" => "audio/ogg",
            "wav" => "audio/x-wav"
        );
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
     * Returns an array of video mimetypes that are permitted for upload.
     * 
     * Note that ogg video files must use the .ogv file extension. Please do not use .ogg for
     * video files as this practice has been deprecated in favour of .ogv. While .ogg is still in
     * wide use it is now presumed to refer to audio files only.
     * 
     * @return array Array of permitted video mimetypes in file extension => mimetype format.
     */
    public function getListOfAllowedVideoMimetypes()
    {
        return array(
            "mp4" => "video/mp4",
            "ogv" => "video/ogg",
            "webm" => "video/webm"
        );
    }
    
    /**
     * Returns a list of intellectual property rights licenses for the content submission form.
     * 
     * In the interests of brevity and sanity, a comprehensive list is not provided. Add entries
     * that you want to use to the array below. Be aware that deleting entries that are in use by
     * your content objects will cause errors.
     * 
     * @return array Array of copyright licenses.
     */
    public function getListOfRights()
    {
        return array(
            '1' => TFISH_RIGHTS_COPYRIGHT,
            '2' => TFISH_RIGHTS_ATTRIBUTION,
            '3' => TFISH_RIGHTS_ATTRIBUTION_SHARE_ALIKE,
            '4' => TFISH_RIGHTS_ATTRIBUTION_NO_DERIVS,
            '5' => TFISH_RIGHTS_ATTRIBUTION_NON_COMMERCIAL,
            '6' => TFISH_RIGHTS_ATTRIBUTION_NON_COMMERCIAL_SHARE_ALIKE,
            '7' => TFISH_RIGHTS_ATTRIBUTION_NON_COMMERCIAL_NO_DERIVS,
            '8' => TFISH_RIGHTS_GPL2,
            '9' => TFISH_RIGHTS_GPL3,
            '10' => TFISH_RIGHTS_PUBLIC_DOMAIN,
        );
    }
    
    /**
     * Returns an array of base object properties that are not used by this subclass.
     * 
     * This list is also used in update calls to the database to ensure that unused columns are
     * cleared and reset with default values.
     * 
     * @return array
     */
    public function getListOfZeroedProperties()
    {
        return array();
    }
    
    /**
     * Returns a whitelist of object properties whose values are allowed be set.
     * 
     * This function is used to build a list of $allowed_vars for a content object. Child classes
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
        
        unset($properties['handler'], $properties['template'], $properties['module'],
                $properties['icon']);
        
        return $properties;
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
     * You can set up an articles.php page to display only TfArticle objects. The 
     * subclass-specific pages are found in the trust_path/extras folder. Just drop
     * them into your site root to use them.
     * 
     * @param string $custom_page Use an arbitrary target page or the home page (index.php).
     * @return string URL to view this object.
     */
    public function getUrl(string $custom_page = '')
    {
        $url = empty($custom_page) ? TFISH_PERMALINK_URL : TFISH_URL;
        
        if ($custom_page) {
            $url .= $this->validator->isAlnumUnderscore($custom_page)
                    ? $this->validator->trimString($custom_page) . '.php' : '';
        }
        
        $url .= '?id=' . (int) $this->id;
        
        if ($this->seo) {
            $url .= '&amp;title=' . $this->validator->encodeEscapeUrl($this->seo);
        }

        return $url;
    }
    
    /**
     * Determine if the media file (mime) type is valid for this content type.
     * 
     * Used in templates to determine whether a media file should be displayed or not.
     * For example, if you attach a video file to an audio content object, the
     * inline player will not be displayed (because it will not work).
     * 
     * @return boolean True if media mimetype is valid for this content type, otherwise false.
     */
    public function isValidMedia()
    {
        if (!$this->media) {
            return false;
        }
        
        $allowed_mimetypes = array();

        switch($this->type) {
            case "TfAudio":
                $allowed_mimetypes = $this->getListOfAllowedAudioMimetypes();
                break;
            case "TfImage":
                $allowed_mimetypes = $this->getListOfAllowedImageMimetypes();
                break;
            case "TfVideo":
                $allowed_mimetypes = $this->getListOfAllowedVideoMimetypes();
                break;
            default:
                $allowed_mimetypes = $this->getListOfPermittedUploadMimetypes();
        }

        if (in_array($this->format, $allowed_mimetypes, true)) {
            return true;
        }
        
        return false;
    }

    /**
     * Populates the properties of the object from external (untrusted) data source.
     * 
     * Note that the supplied data is internally validated by __set().
     * 
     * @param array $dirty_input Usually raw form $_REQUEST data.
     * @param bool $live_urls Convert base url to TFISH_LINK (true) or TFISH_LINK to base url (false).
     */
    public function loadPropertiesFromArray(array $dirty_input, $live_urls = true)
    {
        $delete_image = (isset($dirty_input['deleteImage']) && !empty($dirty_input['deleteImage']))
                ? true : false;
        $delete_media = (isset($dirty_input['deleteMedia']) && !empty($dirty_input['deleteMedia']))
                ? true : false;

        // Note that handler, template and module are not accessible through this method.
        $property_whitelist = $this->getPropertyWhitelist();

        foreach ($property_whitelist as $key => $type) {
            if (array_key_exists($key, $dirty_input)) {
                $this->__set($key, $dirty_input[$key]);
            }
            unset($key, $type);
        }

        if (array_key_exists('date', $property_whitelist) && empty($dirty_input['date'])) {
            $this->setDate(date(DATE_RSS, time()));
        }

        // Convert URLs back to TFISH_LINK for insertion or update, to aid portability.
        if (array_key_exists('teaser', $property_whitelist) && !empty($dirty_input['teaser'])) {
            
            if ($live_urls === true) {
                $teaser = str_replace(TFISH_LINK, 'TFISH_LINK', $dirty_input['teaser']);
            } else {
                $teaser = str_replace('TFISH_LINK', TFISH_LINK, $dirty_input['teaser']);
            }
            
            $this->setTeaser($teaser);
        }

        if (array_key_exists('description', $property_whitelist)
                && !empty($dirty_input['description'])) {
            
            if ($live_urls === true) {
                $description = str_replace(TFISH_LINK, 'TFISH_LINK', $dirty_input['description']);
            } else {
                $description = str_replace('TFISH_LINK', TFISH_LINK, $dirty_input['description']);
            }
            
            $this->setDescription($description);
        }

        if (array_key_exists('image', $property_whitelist) && !empty($_FILES['image']['name'])) {
            $clean_image_filename = $this->validator->trimString($_FILES['image']['name']);
            
            if ($clean_image_filename) {
                $this->setImage($clean_image_filename);
            }
        }
      
        if (array_key_exists('media', $property_whitelist) && !empty($_FILES['media']['name'])) {
            $clean_media_filename = $this->validator->trimString($_FILES['media']['name']);
            
            if ($clean_media_filename) {
                $mimetypeWhitelist = $this->getListOfPermittedUploadMimetypes();
                $extension = mb_strtolower(pathinfo($clean_media_filename, PATHINFO_EXTENSION), 'UTF-8');
                
                $this->setMedia($clean_media_filename);
                $this->setFormat($mimetypeWhitelist[$extension]);
                $this->setFileSize($_FILES['media']['size']);
            }
        }
    }
    
    /**
     * Converts properties to human readable form in preparation for output.
     * 
     * This method is overridden in child subclasses, to allow for the possibility of handling
     * additional properties. The overrides refer back to this parent method for handling base
     * (standard) properties of this parent class.
     * 
     * @param string $property Name of property.
     * @return string Property formatted to human readable form for output.
     */
    protected function makeDataHumanReadable(string $cleanProperty)
    {        
        switch ($cleanProperty) {
            case "date": // Stored in format yyyy-mm-dd
                $date = new DateTime($this->$cleanProperty);
                
                return $date->format('j F Y');
                break;

            case "fileSize": // Convert to human readable.
                $bytes = (int) $this->$cleanProperty;
                $unit = $val = '';

                if ($bytes === 0 || $bytes < ONE_KILOBYTE) {
                    $unit = ' bytes';
                    $val = $bytes;
                } elseif ($bytes >= ONE_KILOBYTE && $bytes < ONE_MEGABYTE) {
                    $unit = ' KB';
                    $val = ($bytes / ONE_KILOBYTE);
                } elseif ($bytes >= ONE_MEGABYTE && $bytes < ONE_GIGABYTE) {
                    $unit = ' MB';
                    $val = ($bytes / ONE_MEGABYTE);
                } else {
                    $unit = ' GB';
                    $val = ($bytes / ONE_GIGABYTE);
                }

                $val = round($val, 2);

                return $val . ' ' . $unit;
                break;

            case "format": // Output the file extension as user-friendly "mimetype".
                $mimetypeWhitelist = $this->getListOfPermittedUploadMimetypes();
                $mimetype = array_search($this->$cleanProperty, $mimetypeWhitelist);

                if (!empty($mimetype)) {
                    return $mimetype;
                }
                break;

            case "description":
            case "teaser":
                // Do a simple string replace to allow TFISH_URL to be used as a constant,
                // making the site portable.
                $trUrlEnabled = str_replace('TFISH_LINK', TFISH_LINK,
                        $this->$cleanProperty);

                return $trUrlEnabled; 
                break;

            case "rights":
                $rights = $this->getListOfRights();

                return $rights[$this->$cleanProperty];
                break;

            case "submissionTime":
                $date = date('j F Y', $this->$cleanProperty);

                return $date;
                break;

            case "tags":
                $tags = array();

                foreach ($this->$cleanProperty as $value) {
                    $tags[] = (int) $value;
                    unset($value);
                }

                return $tags;
                break;
                
            // No special handling required. Return unmodified value.
            default:
                return $this->$cleanProperty;
                break;
        }
    }
    
    /**
     * Intercept direct setting of properties to permit data validation.
     * 
     * It is best to set properties using the relevant setter method directly, as it is more
     * efficient. However, due to use of PDO::FETCH_CLASS to automate instantiation of objects
     * directly from the database, it is also necessary to allow them to be set via a magic method
     * call.
     * 
     * @param string $property Name of property.
     * @param mixed $value Value of property.
     */
    public function __set(string $property, $value)
    {
        $cleanProperty = $this->validator->trimString($property);
        
        if (isset($this->$cleanProperty)) {
            switch ($cleanProperty) {
                case "id":
                    $this->setId((int) $value);
                    break;
                case "type":
                    $this->setType((string) $value);
                    break;
                case "title":
                    $this->setTitle((string) $value);
                    break;
                case "teaser":
                    $this->setTeaser((string) $value);
                    break;
                case "description":
                    $this->setDescription((string) $value);
                    break;
                case "media":
                    $this->setMedia((string) $value);
                    break;
                case "format":
                    $this->setFormat((string) $value);
                    break;
                case "fileSize":
                    $this->setFileSize((int) $value);
                    break;
                case "creator":
                    $this->setCreator((string) $value);
                    break;
                case "image":
                    $this->setImage((string) $value);
                    break;
                case "caption":
                    $this->setCaption((string) $value);
                    break;
                case "date":
                    $this->setDate((string) $value);
                    break;
                case "parent":
                    $this->setParent((int) $value);
                    break;
                case "language":
                    $this->setLanguage((string) $value);
                    break;
                case "rights":
                    $this->setRights((int) $value);
                    break;
                case "publisher":
                    $this->setPublisher((string) $value);
                    break;
                case "tags":
                    $this->setTags((array) $value);
                    break;
                case "online":
                    $this->setOnline((int) $value);
                    break;
                case "submissionTime":
                    $this->setSubmissionTime((int) $value);
                    break;
                case "counter":
                    $this->setCounter((int) $value);
                    break;
                case "metaTitle":
                    $this->setMetaTitle((string) $value);
                    break;
                case "metaDescription":
                    $this->setMetaDescription((string) $value);
                    break;
                case "seo":
                    $this->setSeo((string) $value);
                    break;
                case "handler":
                    $this->setHandler((string) $value);
                    break;
                case "template":
                    $this->setTemplate((string) $value);
                    break;
                case "module":
                    $this->setModule((string) $value);
                    break;
                case "icon":
                    $this->setIcon((string) $value);
                    break;
            }
        } else {
            // Not a permitted property, do not set.
        }
    }
    
    public function setCaption(string $caption)
    {
        $clean_caption = (string) $this->validator->trimString($caption);
        $this->caption = $clean_caption;
    }
    
    public function setCounter(int $counter)
    {
        if ($this->validator->isInt($counter, 0)) {
            $this->counter = $counter;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setCreator(string $creator)
    {
        $clean_creator = (string) $this->validator->trimString($creator);
        $this->creator = $clean_creator;
    }
    
    public function setDate(string $date)
    {
        $date = (string) $this->validator->trimString($date);

        // Ensure format complies with DATE_RSS
        $check_date = date_parse_from_format('Y-m-d', $date);

        if (!$check_date || $check_date['warning_count'] > 0
                || $check_date['error_count'] > 0) {
            // Bad date supplied, default to today.
            $date = date(DATE_RSS, time());
            trigger_error(TFISH_ERROR_BAD_DATE_DEFAULTING_TO_TODAY, E_USER_WARNING);
        }
        
        $this->date = $date;
    }
    
    public function setDescription(string $description)
    {
        $description = (string) $this->validator->trimString($description);
        $this->description = $this->validator->filterHtml($description);
    }
    
    public function setFileSize(int $fileSize)
    {
        if ($this->validator->isInt($fileSize, 0)) {
            $this->fileSize = $fileSize;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setFormat(string $format)
    {
        $format = (string) $this->validator->trimString($format);

        $mimetypeWhitelist = $this->getListOfPermittedUploadMimetypes();
        if (!empty($format) && !in_array($format, $mimetypeWhitelist, true)) {
            trigger_error(TFISH_ERROR_ILLEGAL_MIMETYPE, E_USER_ERROR);
        }
        
        $this->format = $format;
    }
    
    public function setHandler(string $handler)
    {
        $clean_handler = (string) $this->validator->trimString($handler);

        if ($this->validator->isAlpha($clean_handler)) {
            $this->handler = $clean_handler;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
        }
    }
    
    public function setIcon(string $icon)
    {
        $icon = (string) $this->validator->trimString($icon);
        $this->icon = $this->validator->filterHtml($icon);
    }
    
    public function setId(int $id)
    {
        if ($this->validator->isInt($id, 0)) {
            $this->id = $id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
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
    
    public function setLanguage(string $language)
    {        
        $language = (string) $this->validator->trimString($language);
        $language_whitelist = $this->getListOfLanguages();

        if (!array_key_exists($language, $language_whitelist)) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
        }
        
        $this->language = $language;
    }
    
    public function setMedia(string $media)
    {
        $media = (string) $this->validator->trimString($media);

        // Check image/media paths for directory traversals and null byte injection.
        if ($this->validator->hasTraversalorNullByte($media)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            exit; // Hard stop due to high probability of abuse.
        }

        // Check media file is a permitted mimetype.
        $mimetypeWhitelist = $this->getListOfPermittedUploadMimetypes();
        $extension = mb_strtolower(pathinfo($media, PATHINFO_EXTENSION), 'UTF-8');

        if (empty($extension) 
                || (!empty($extension) && !array_key_exists($extension, $mimetypeWhitelist))) {
            $this->media = '';
            $this->format = '';
            $this->fileSize = '';
        } else {
            $this->media = $media;
        }        
    }
    
    public function setMetaDescription(string $metaDescription)
    {
        $clean_metaDescription = (string) $this->validator->trimString($metaDescription);
        $this->metaDescription = $clean_metaDescription;
    }
    
    public function setMetaTitle(string $metaTitle)
    {
        $clean_metaTitle = (string) $this->validator->trimString($metaTitle);
        $this->metaTitle = $clean_metaTitle;
    }
    
    public function setModule(string $module)
    {
        $clean_module = (string) $this->validator->trimString($module);
        $this->module = $clean_module;
    }
    
    public function setOnline(int $online)
    {
        if ($this->validator->isInt($online, 0, 1)) {
            $this->online = $online;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    // Parent ID must be different to content ID (cannot declare self as parent).
    public function setParent(int $parent)
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
    
    public function setPublisher(string $publisher)
    {
        $clean_publisher = (string) $this->validator->trimString($publisher);
        $this->publisher = $clean_publisher;
    }
    
    public function setRights(int $rights)
    {
        if ($this->validator->isInt($rights, 1)) {
            $this->rights = $rights;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setSeo(string $seo)
    {
        $clean_seo = (string) $this->validator->trimString($seo);

        // Replace spaces with dashes.
        if ($this->validator->isUtf8($clean_seo)) {
            $clean_seo = str_replace(' ', '-', $clean_seo);
        } else {
            trigger_error(TFISH_ERROR_NOT_UTF8, E_USER_ERROR);
        }
        
        $this->seo = $clean_seo;
    }
    
    public function setSubmissionTime(int $submissionTime)
    {
        if ($this->validator->isInt($submissionTime, 1)) {
            $this->submissionTime = $submissionTime;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
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
    
    public function setTeaser(string $teaser)
    {
        $teaser = (string) $this->validator->trimString($teaser);
        $this->teaser = $this->validator->filterHtml($teaser);
    }
    
    public function setTemplate(string $template)
    {
        $clean_template = (string) $this->validator->trimString($template);

        if ($this->validator->isAlnumUnderscore($clean_template)) {
            $this->template = $clean_template;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
        }
    }
    
    public function setTitle(string $title)
    {
        $clean_title = (string) $this->validator->trimString($title);
        $this->title = $clean_title;
    }
    
    public function setType(string $type)
    {
        $cleanType = (string) $this->validator->trimString($type);

        if ($this->validator->isAlpha($cleanType)) {
            $this->type = $cleanType;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
        }
    }

}
