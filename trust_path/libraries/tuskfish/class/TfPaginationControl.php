<?php

/**
 * TfPaginationControl class file.
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
 * Generates pagination controls for display.
 * 
 * The number of pagination control slots is set in Tuskfish Preferences. Choose an odd number for
 * best results.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @property    object $preference TfPreference object containing the site preference information.
 */
class TfPaginationControl
{
    
    protected $validator;
    
    /** @var object $preference Instance of TfPreference class, holds site preference info. */
    protected $preference;
    
    protected $count;
    protected $limit;
    protected $url;
    protected $start;
    protected $tag;
    protected $extra_params;
    
    /** @param TfPreference $preference Instance of TfPreference, holding site preferences. */
    function __construct(TfValidator $tf_validator, TfPreference $tf_preference)
    {
        $this->validator = $tf_validator;
        $this->preference = $tf_preference;
        $this->count = 0;
        $this->limit = 0;
        $this->url = '';
        $this->start = 0;
        $this->tag = 0;
        $this->extra_params = array();
    }
    
    /**
     * Creates a pagination control designed for use with the Bootstrap framework.
     * 
     * $query is an array of arbitrary query string parameters. Note that these need to be passed
     * in as an array of key => value pairs, and you should build this yourself using known and
     * whitelisted values. Do not pass through random query strings someone gave you on the
     * internetz.
     * 
     * If you want to create pagination controls for other presentation-side libraries add
     * additional methods to this class.
     * 
     * @param int $count Number of content objects (pages) matching these parameters.
     * @param int $limit Number of content objects to retrieve in current view.
     * @param string $url Target base URL for pagination control links.
     * @param int $start Position in result set to retrieve content objects from.
     * @param int $tag ID of tag used to filter content.
     * @param array $extra_params Query string to be appended to the URLs (control script params).
     * @return string HTML pagination control.
     */
    public function getPaginationControl()
    {
        // If the count is zero there is no need for a pagination control.
        if ($this->count === 0) {
            return false;
        }
        
        // 1. Calculate number of pages, page number of start object and adjust for remainders.
        $page_slots = array();
        $page_count = (int) (($this->count / $this->limit));
        $remainder = $this->count % $this->limit;
        
        if ($remainder) {
            $page_count += 1;
        }
        
        $page_range = range(1, $page_count);

        // No need for pagination control if only one page.
        if ($page_count === 1) {
            return false;
        }

        // 2. Calculate current page.
        $current_page = (int) (($this->start / $this->limit) + 1);

        // 3. Calculate length of pagination control (number of slots).
        $elements = ((int) $this->preference->pagination_elements > $page_count)
                ? $page_count : (int) $this->preference->pagination_elements;

        // 4. Calculate the fore offset and initial (pre-adjustment) starting position.
        $offset_int = (int) (($elements - 1) / 2);
        $offset_float = ($elements - 1) / 2;
        $page_start = $current_page - $offset_int;

        // 5. Check if fore exceeds bounds. If so, set start = 1 and extract the range.
        $fore_boundcheck = $current_page - $offset_int;
         
        // 6. Check if aft exceeds bounds. If so set start = $page_count - length.
        $aft_boundcheck = ($current_page + $offset_float);

        // This is the tricky bit - slicing a variable region out of the range.
        if ($page_count === $elements) {
            $page_slots = $page_range;
        } elseif ($fore_boundcheck < 1) {
            $page_slots = array_slice($page_range, 0, $elements, true);
        } elseif ($aft_boundcheck >= $page_count) {
            $page_start = $page_count - $elements;
            $page_slots = array_slice($page_range, $page_start, $elements, true);
        } else {
            $page_slots = array_slice($page_range, ($page_start - 1), $elements, true);
        }

        // 7. Substitute in the 'first' and 'last' page elements and sort the array back into
        // numerical order.
        end($page_slots);
        unset($page_slots[key($page_slots)]);
        $page_slots[($page_count - 1)] = TFISH_PAGINATION_LAST;
        reset($page_slots);
        unset($page_slots[key($page_slots)]);
        $page_slots[0] = TFISH_PAGINATION_FIRST;
        ksort($page_slots);

        // Construct a HTML pagination control.
        $control = '<nav aria-label="Page navigation"><ul class="pagination">';

        // Prepare the query string.
        $query = $start_arg = $tag_arg = '';
        
        foreach ($page_slots as $key => $slot) {
            $this->start = (int) ($key * $this->limit);

            // Set the arguments.
            if ($this->start || $this->tag || $this->extra_params) {
                $arg_array = array();
                
                if (!empty($this->start)) {
                    $arg_array[] = 'start=' . $this->start;
                }
                
                if (!empty($this->tag)) {
                    $arg_array[] = 'tag_id=' . $this->tag;
                }
                
                if (!empty($this->extra_params)) {
                    $arg_array[] = $this->extra_params;
                }
                
                $query = '?' . implode('&amp;', $arg_array);
            }

            if (($key + 1) === $current_page) {
                $control .= '<li class="page-item active"><a class="page-link" href="' . $this->url 
                        . $query . '">' . $slot . '</a></li>';
            } else {
                $control .= '<li class="page-item"><a class="page-link" href="' . $this->url
                        . $query . '">' . $slot . '</a></li>';
            }
            
            unset($query, $key, $slot);
        }
        
        $control .= '</ul></nav>';

        return $control;
    }
    
    /**
     * Disallow direct setting of properties.
     * 
     * @param string $property Name of property.
     * @param mixed $value Value to assign to property.
     */
    
    public function __set(string $property, $value)
    {
        trigger_error(TFISH_ERROR_DIRECT_PROPERTY_SETTING_DISALLOWED);        
        exit;    
    }
    
    public function setCount($count)
    {
        $this->count = $this->validator->isInt($count, 1) ? (int) $count : 0;
    }
    
    public function setLimit(int $limit)
    {
        $this->limit = $this->validator->isInt($limit, 1) ? (int) $limit : 0;
    }
    
    public function setUrl(string $url)
    {
        $clean_url = $this->validator->trimString($url);
        $this->url = $this->validator->isAlnumUnderscore($clean_url) ? $clean_url . '.php'
                : TFISH_URL;
    }
    
    public function setStart(int $start)
    {
        $this->start = $this->validator->isInt($start, 0) ? (int) $start : 0;
    }
    
    public function setTag(int $tag)
    {
        $this->tag = $this->validator->isInt($tag, 0) ? (int) $tag : 0;
    }
    
    // $extra_params is a potential XSS attack vector.
    // The key => value pairs be i) rawurlencoded and ii) entity escaped. However, in order to
    // avoid messing up the query and avoid unecessary decoding it is possible to maintain
    // manual control over the operators. (Basically, input requiring encoding or escaping is
    // absolutely not wanted here, it is just being conducted to mitigate XSS attacks). If you
    // actually *want* to use such input (check your sanity), you will need to decode it prior to
    // use on the landing page.
    public function setExtraParams(array $extra_params)
    {
        $clean_extra_params = array();
        
        foreach ($extra_params as $key => $value) {
            if ($this->validator->hasTraversalorNullByte((string) $key)
                    || $this->validator->hasTraversalorNullByte((string) $value)) {
                trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
                return false;
            }
        
            $clean_extra_params[] = $this->validator->encodeEscapeUrl($key) . '='
                    . $this->validator->encodeEscapeUrl((string) $value);
            unset($key, $value);
        }
        
        if (empty($clean_extra_params)) {
            $this->extra_params = '';
        } else {
            $this->extra_params = $this->validator->escapeForXss(implode("&", $clean_extra_params));
        }   
    }
    
}