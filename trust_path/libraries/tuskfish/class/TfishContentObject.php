<?php

/**
 * TfishContentObject class file.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
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
 * There is only one 'archtype' of content object in Tuskfish; it uses standard Dublin Core metadata
 * fields plus a few more that are common to most content objects. Why? If you look at most common
 * content types - articles, photos, downloads etc. - you will see that for the most part they all
 * use the same fields. For example, everything has a title, everything has a description, 
 * everything has an author, everything has a hit counter.
 * 
 * Traditionally, most CMS create a separate database table for every type of content with
 * duplicate column names. And it works just fine until you want to publish a single content stream
 * containing different kinds of content objects. Then, suddenly, your queries are full of complex
 * joins and other rubbish and it becomes very painful to work with.
 * 
 * By using a single table for content objects with common field names our queries become very
 * simple and much redundancy is avoided. Of course, some types of content might not require a few
 * particular properties; and so subclassed content types simply unset() any properties that they 
 * don't need in their constructor.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @properties  int $id Auto-increment, set by database.
 * @properties  string $type Content object type eg. TfishArticle etc. [ALPHA]
 * @properties  string $title The name of this content.
 * @properties  string $teaser A short (one paragraph) summary or abstract for this content. [HTML]
 * @properties  string $description The full article or description of the content. [HTML]
 * @properties  string $media An associated download/audio/video file. [FILEPATH OR URL]
 * @properties  string $format Mimetype
 * @properties  string $file_size Specify in bytes.
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
 * @properties  int $submission_time Timestamp representing submission time.
 * @properties  int $counter Number of times this content was viewed or downloaded.
 * @properties  string $meta_title Set a custom page title for this content.
 * @properties  string $meta_description Set a custom page meta description for this content.
 * @properties  string $seo SEO-friendly string; it will be appended to the URL for this content.
 * @properties  string $handler Handler for this object (not persistent).
 * @properties  string $template The template that should be used to display this object (not persistent).
 * @properties  string $module The module that handles this content type (not persistent).
 * @properties  string $icon The vector icon that represents this object type (not persistent).
 */
class TfishContentObject extends TfishAncestralObject
{

    /** Initialise default content object properties and values. */
    function __construct()
    {
        /**
         * Whitelist of official properties and datatypes.
         */
        $this->__properties['id'] = 'int';
        $this->__properties['type'] = 'alpha';
        $this->__properties['title'] = 'string';
        $this->__properties['teaser'] = 'html';
        $this->__properties['description'] = 'html';
        $this->__properties['media'] = 'string';
        $this->__properties['format'] = 'string';
        $this->__properties['file_size'] = 'int';
        $this->__properties['creator'] = 'string';
        $this->__properties['image'] = 'string';
        $this->__properties['caption'] = 'string';
        $this->__properties['date'] = 'string';
        $this->__properties['parent'] = 'int';
        $this->__properties['language'] = 'string';
        $this->__properties['rights'] = 'int';
        $this->__properties['publisher'] = 'string';
        $this->__properties['tags'] = 'array';
        $this->__properties['online'] = 'int';
        $this->__properties['submission_time'] = 'int';
        $this->__properties['counter'] = 'int';
        $this->__properties['meta_title'] = 'string';
        $this->__properties['meta_description'] = 'string';
        $this->__properties['seo'] = 'string';
        $this->__properties['handler'] = 'alpha';
        $this->__properties['template'] = 'alnumunder';
        $this->__properties['module'] = 'string';
        $this->__properties['icon'] = 'html';

        /**
         * Set the permitted properties of this object.
         */
        foreach ($this->__properties as $key => $value) {
            $this->__data[$key] = '';
        }

        /**
         * Set default values of permitted properties.
         */
        $this->__data['type'] = get_class($this);
        $this->__data['template'] = '';
        $this->__data['handler'] = $this->__data['type'] . 'Handler';
        $this->__data['rights'] = 1;
        $this->__data['online'] = 1;
        $this->__data['counter'] = 0;
        $this->__data['tags'] = array();
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
    protected function makeHumanReadable(string $clean_property)
    {
        switch ($clean_property) {
            case "date": // Stored in format yyyy-mm-dd
                $date = new DateTime($this->__data[$clean_property]);
                
                return $date->format('j F Y');
                break;

            case "file_size": // Convert to human readable.
                $bytes = (int) $this->__data[$clean_property];
                $unit = $val = '';

                if ($bytes === 0 || $bytes < 1024) {
                    $unit = ' bytes';
                    $val = $bytes;
                } elseif ($bytes > 1023 && $bytes < 1048576) {
                    $unit = ' KB';
                    $val = ($bytes / 1024);
                } elseif ($bytes > 1048575 && $bytes < 1073741824) {
                    $unit = ' MB';
                    $val = ($bytes / 1048576);
                } else {
                    $unit = ' GB';
                    $val = ($bytes / 1073741824);
                }

                $val = round($val, 2);

                return $val . ' ' . $unit;
                break;

            case "format": // Output the file extension as user-friendly "mimetype".
                $mimetype_whitelist = TfishFileHandler::getPermittedUploadMimetypes();
                $mimetype = array_search($this->__data[$clean_property], $mimetype_whitelist);

                if (!empty($mimetype)) {
                    return $mimetype;
                }
                break;

            case "description":
            case "teaser":
                // Do a simple string replace to allow TFISH_URL to be used as a constant,
                // making the site portable.
                $tfish_url_enabled = str_replace('TFISH_LINK', TFISH_LINK,
                        $this->__data[$clean_property]);

                return $tfish_url_enabled; 
                break;

            case "rights":
                $rights = TfishContentHandler::getRights();

                return $rights[$this->__data[$clean_property]];
                break;

            case "submission_time":
                $date = date('j F Y', $this->__data[$clean_property]);

                return $date;
                break;

            case "tags":
                $tags = array();

                foreach ($this->__data[$clean_property] as $value) {
                    $tags[] = (int) $value;
                    unset($value);
                }

                return $tags;
                break;
                
            // No special handling required. Return unmodified value.
            default:
                return $this->__data[$clean_property];
                break;
        }
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
    public function escape(string $property, bool $escape_html = false)
    {
        $clean_property = TfishFilter::trimString($property);
        
        // If property is not set return null.
        if (!isset($this->__data[$clean_property])) {
            return null;
        }
        
        // Format all data for display and convert TFISH_LINK to URL.
        $human_readable_data = (string) $this->makeHumanReadable($clean_property);
        
        // Output for editor: HTML should be double escaped.
        if ($this->__properties[$clean_property] === 'html' && $escape_html === true) {
            
            return htmlspecialchars($human_readable_data, ENT_NOQUOTES, 'UTF-8',
                    true);
        }
                
        // Output HTML for display: Do not escape as it has been input filtered with HTMLPurifier.
        if ($this->__properties[$clean_property] === 'html' && $escape_html === false) {
            return $human_readable_data;
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
        $clean_width = TfishFilter::isInt($width, 1) ? (int) $width : 0;
        $clean_height = TfishFilter::isInt($height, 1) ? (int) $height : 0;
        
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
     * Generates a URL to access this object in single view mode.
     * 
     * URL can point relative to either the home page (index.php, or other custom content stream
     * page defined by modifying TFISH_PERMALINK_URL in config.php) or to an arbitrary page in the
     * web root. For example, you could rename index.php to 'blog.php' to free up the index page
     * for a landing page (this requires you to append the name of the new page to the 
     * TFISH_PERMALINK_URL constant).
     * 
     * You can set up an articles.php page to display only TfishArticle objects. The 
     * subclass-specific pages are found in the trust_path/extras folder. Just drop
     * them into your site root to use them.
     * 
     * @param string $custom_page Use an arbitrary target page or the home page (index.php).
     * @return string URL to view this object.
     */
    public function getURL(string $custom_page = '')
    {
        $url = empty($custom_page) ? TFISH_PERMALINK_URL : TFISH_URL;
        
        if ($custom_page) {
            $url .= TfishFilter::isAlnumUnderscore($custom_page)
                    ? TfishFilter::trimString($custom_page) . '.php' : '';
        }
        
        $url .= '?id=' . (int) $this->id;
        
        if ($this->seo) {
            $url .= '&amp;title=' . TfishFilter::encodeEscapeUrl($this->seo);
        }

        return $url;
    }

    /**
     * Populates the properties of the object from external (untrusted) data source.
     * 
     * Note that the supplied data is internally validated by __set().
     * 
     * @param array $dirty_input Usually raw form $_REQUEST data.
     * @param bool $live_urls Convert base url to TFISH_LINK (true) or TFISH_LINK to base url (false).
     */
    public function loadProperties(array $dirty_input, $live_urls = true)
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
            $this->__set('date', date(DATE_RSS, time()));
        }
        
        // Convert URLs back to TFISH_LINK for insertion or update, to aid portability.
        if (array_key_exists('teaser', $property_whitelist) && !empty($dirty_input['teaser'])) {
            
            if ($live_urls === true) {
                $teaser = str_replace(TFISH_LINK, 'TFISH_LINK', $dirty_input['teaser']);
            } else {
                $teaser = str_replace('TFISH_LINK', TFISH_LINK, $dirty_input['teaser']);
            }
            
            $this->__set('teaser', $teaser);
        }

        if (array_key_exists('description', $property_whitelist)
                && !empty($dirty_input['description'])) {
            
            if ($live_urls === true) {
                $description = str_replace(TFISH_LINK, 'TFISH_LINK', $dirty_input['description']);
            } else {
                $description = str_replace('TFISH_LINK', TFISH_LINK, $dirty_input['description']);
            }
            
            $this->__set('description', $description);
        }

        if (array_key_exists('image', $property_whitelist) && !empty($_FILES['image']['name'])) {
            $clean_image_filename = TfishFilter::trimString($_FILES['image']['name']);
            
            if ($clean_image_filename) {
                $this->__set('image', $clean_image_filename);
            }
        }
        
        if (array_key_exists('media', $property_whitelist) && !empty($_FILES['media']['name'])) {
            $clean_media_filename = TfishFilter::trimString($_FILES['media']['name']);
            
            if ($clean_media_filename) {
                $mimetype_whitelist = TfishFileHandler::getPermittedUploadMimetypes();
                $extension = mb_strtolower(pathinfo($clean_media_filename, PATHINFO_EXTENSION), 'UTF-8');
                
                $this->__set('media', $clean_media_filename);
                $this->__set('format', $mimetype_whitelist[$extension]);
                $this->__set('file_size', $_FILES['media']['size']);
            }
        }
    }

    /**
     * Set the value of a whitelisted property.
     * 
     * Intercepts direct calls to set the value of an object property. This method is overridden by
     * child classes to impose data type restrictions and range checks before allowing the property
     * to be set. Tuskfish objects are designed not to trust other components; each conducts its
     * own internal validation checks. 
     * 
     * @param string $property Name of property.
     * @param mixed $value Value of property.
     */
    public function __set(string $property, $value)
    {
        $clean_property = TfishFilter::trimString($property);
        
        if (!isset($this->__data[$clean_property])) {
            /**
             * If try to set an property that was explicitly unset by a subclass, do nothing.
             * This does happen when trying to pull rows directly into content subclass objects
             * using PDO (basically the constructor unsets unneeded fields, then PDO tries to set
             * them because each row has the full set of columns).
             *  
             * If try to set some other random property (which probably means a typo has been made)
             * throw an error to help catch bugs.
             */
            if (!in_array($property, $this->zeroedProperties())) {
                trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_WARNING);
            }
        }
            
        // Validate $value against expected data type and business rules
        $type = $this->__properties[$clean_property];

        switch ($type) {

            case "alpha":
                $value = TfishFilter::trimString($value);

                if (TfishFilter::isAlpha($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
                }
                break;

            case "alnum":
                $value = TfishFilter::trimString($value);

                if (TfishFilter::isAlnum($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_ALNUM, E_USER_ERROR);
                }
                break;

            case "alnumunder":
                $value = TfishFilter::trimString($value);

                if (TfishFilter::isAlnumUnderscore($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
                }
                break;

            // Only array field is tags, contents must all be integers.
            case "array":
                if (TfishFilter::isArray($value)) {
                    $clean_tags = array();

                    foreach ($value as $val) {
                        $clean_val = (int) $val;

                        if (TfishFilter::isInt($clean_val, 1)) {
                            $clean_tags[] = $clean_val;
                        } else {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }
                        unset($clean_val);
                    }

                    $this->__data[$clean_property] = $clean_tags;
                } else {
                    trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
                }
                break;

            case "bool":
                if (TfishFilter::isBool($value)) {
                    $this->__data[$clean_property] = (bool) $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_BOOL, E_USER_ERROR);
                }
                break;

            case "email":
                $value = TfishFilter::trimString($value);

                if (TfishFilter::isEmail($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_EMAIL, E_USER_ERROR);
                }
                break;

            case "digit":
                $value = TfishFilter::trimString($value);

                if (TfishFilter::isDigit($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_DIGIT, E_USER_ERROR);
                }
                break;

            case "float":
                $value = (float) $value;
                
                if (TfishFilter::isFloat($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_FLOAT, E_USER_ERROR);
                }
                break;

            case "html":
                $value = TfishFilter::trimString($value);
                // Enable input filtering with HTMLPurifier.
                $this->__data[$clean_property] = (string) TfishFilter::filterHtml($value);
                // Disable input filtering with HTMLPurifier (only do this if output filtering
                // is enabled in escape()).
                //$this->__data[$clean_property] = (string)TfishFilter::trimString($value);
                break;

            case "int":
                $value = (int) $value;

                switch ($clean_property) {
                    // 0 or 1.
                    case "online":
                        if (TfishFilter::isInt($value, 0, 1)) {
                            $this->__data[$clean_property] = $value;
                        } else {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }
                        break;

                    // Minimum value 0.
                    case "counter":
                    case "file_size":
                    case "id":
                        if (TfishFilter::isInt($value, 0)) {
                            $this->__data[$clean_property] = $value;
                        } else {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }
                        break;

                    // Parent ID must be different to content ID (cannot declare self as parent).
                    case "parent":
                        if (!TfishFilter::isInt($value, 0)) {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }

                        if ($value === $this->__data['id'] && $value > 0) {
                            trigger_error(TFISH_ERROR_CIRCULAR_PARENT_REFERENCE);
                        } else {
                            $this->__data[$clean_property] = $value;
                        }
                        break;

                    // Minimum value 1.
                    case "rights":
                    case "submission_time":
                        if (TfishFilter::isInt($value, 1)) {
                            $this->__data[$clean_property] = $value;
                        } else {
                            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                        }
                        break;
                }
                break;

            case "ip":
                $value = TfishFilter::trimString($value);
                if ($value === "" || TfishFilter::isIp($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_IP, E_USER_ERROR);
                }
                break;

            case "string":
                $value = TfishFilter::trimString($value);

                if ($clean_property === "date") { // Ensure format complies with DATE_RSS
                    $check_date = date_parse_from_format('Y-m-d', $value);

                    if (!$check_date || $check_date['warning_count'] > 0
                            || $check_date['error_count'] > 0) {
                        // Bad date supplied, default to today.
                        $value = date(DATE_RSS, time());
                        trigger_error(TFISH_ERROR_BAD_DATE_DEFAULTING_TO_TODAY, E_USER_WARNING);
                    }
                }

                // Check image/media paths for directory traversals and null byte injection.
                if ($clean_property === "image" || $clean_property === "media") {
                    if (TfishFilter::hasTraversalorNullByte($value)) {
                        trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
                    }
                }
                
                // Check image file is a permitted mimetype.
                if ($clean_property === "image") {
                    $mimetype_whitelist = TfishFileHandler::allowedImageMimetypes();
                    $extension = mb_strtolower(pathinfo($value, PATHINFO_EXTENSION), 'UTF-8');
                    if (!empty($extension) && !array_key_exists($extension, $mimetype_whitelist)) {
                        trigger_error(TFISH_ERROR_ILLEGAL_MIMETYPE, E_USER_ERROR);
                    }
                }
                
                // Check media file is a permitted mimetype.
                if ($clean_property === "media") {
                    $mimetype_whitelist = TfishFileHandler::getPermittedUploadMimetypes();
                    $extension = mb_strtolower(pathinfo($value, PATHINFO_EXTENSION), 'UTF-8');
                    
                    if (empty($extension) 
                            || (!empty($extension) && !array_key_exists($extension, $mimetype_whitelist))) {
                        $this->__data['media'] = '';
                        $this->__data['format'] = '';
                        $this->__data['file_size'] = '';
                    }
                }

                if ($clean_property === "format") {
                    $mimetype_whitelist = TfishFileHandler::getPermittedUploadMimetypes();
                    if (!empty($value) && !in_array($value, $mimetype_whitelist)) {
                        trigger_error(TFISH_ERROR_ILLEGAL_MIMETYPE, E_USER_ERROR);
                    }
                }

                if ($clean_property === "language") {
                    $language_whitelist = TfishContentHandler::getLanguages();

                    if (!array_key_exists($value, $language_whitelist)) {
                        trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                    }
                }

                // Replace spaces with dashes.
                if ($clean_property === "seo") {
                    if (TfishFilter::isUtf8($value)) {
                        $value = str_replace(' ', '-', $value);
                    } else {
                        trigger_error(TFISH_ERROR_NOT_UTF8, E_USER_ERROR);
                    }
                }

                $this->__data[$clean_property] = $value;
                break;

            case "url":
                $value = TfishFilter::trimString($value);

                if ($value === "" || TfishFilter::isUrl($value)) {
                    $this->__data[$clean_property] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_URL, E_USER_ERROR);
                }
                break;
        }
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
    public function validMedia()
    {
        if (!$this->__data['media']) {
            return false;
        }
        
        $allowed_mimetypes = array();

        switch($this->__data['type']) {
            case "TfishAudio":
                $allowed_mimetypes = TfishFileHandler::allowedAudioMimetypes();
                break;
            case "TfishImage":
                $allowed_mimetypes = TfishFileHandler::allowedImageMimetypes();
                break;
            case "TfishVideo":
                $allowed_mimetypes = TfishFileHandler::allowedVideoMimetypes();
                break;
            default:
                $allowed_mimetypes = TfishFileHandler::getPermittedUploadMimetypes();
        }

        if (in_array($this->__data['format'], $allowed_mimetypes)) {
            return true;
        }
        
        return false;
    }

    /**
     * Returns an array of base object properties that are not used by this subclass.
     * 
     * This list is also used in update calls to the database to ensure that unused columns are
     * cleared and reset with default values.
     * 
     * @return array
     */
    public function zeroedProperties()
    {
        return array();
    }
    
}
