<?php

/**
 * TfishPaginationControl class file.
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
 * @property    object $preference TfishPreference object containing the site preference information.
 */
class TfishPaginationControl
{
    
    protected $validator;
    
    /** @var object $preference Instance of TfishPreference class, holds site preference info. */
    protected $preference;
    
    /** @param TfishPreference $preference Instance of TfishPreference, holding site preferences. */
    function __construct(object $tfish_validator, object $tfish_preference)
    {
        if (is_object($tfish_validator)) {
            $this->validator = $tfish_validator;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (is_object($tfish_preference)) {
            $this->preference = $tfish_preference;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }        
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
    public function getPaginationControl(int $count, int $limit, string $url, int $start = 0,
            int $tag = 0, array $extra_params = array())
    {
        // Filter parameters.
        $clean_count = $this->validator->isInt($count, 1) ? (int) $count : 0;
        $clean_limit = $this->validator->isInt($limit, 1) ? (int) $limit : 0;
        $clean_start = $this->validator->isInt($start, 0) ? (int) $start : 0;
        $clean_url = $this->validator->isAlnumUnderscore($url) ? $this->validator->trimString($url)
                . '.php' : TFISH_URL;
        $clean_tag = $this->validator->isInt($tag) ? (int) $tag : 0;

        // $extra_params is a potential XSS attack vector.
        // The key => value pairs be i) rawurlencoded and ii) entity escaped. However, in order to
        // avoid messing up the query and avoid unecessary decoding it is possible to maintain
        // manual control over the operators. (Basically, input requiring encoding or escaping is
        // absolutely not wanted here, it is just being conducted to mitigate XSS attacks). If you
        // actually *want* to use such input you will need to decode it prior to use on the
        // landing page.
        $clean_extra_params = array();
        
        foreach ($extra_params as $key => $value) {
            
            // Check for directory traversals and null byte injection.
            if ($this->validator->hasTraversalorNullByte($key)
                    || $this->validator->hasTraversalorNullByte((string) $value)) {
                trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
                return false;
            }
        
            $clean_extra_params[] = $this->validator->encodeEscapeUrl($key) . '='
                    . $this->validator->encodeEscapeUrl((string) $value);
            unset($key, $value);
        }
        
        $clean_extra_params = !empty($clean_extra_params)
                ? $this->validator->escapeForXss(implode("&", $clean_extra_params)) : '';

        // If the count is zero there is no need for a pagination control.
        if ($clean_count === 0) {
            return false;
        }
        
        // If any parameter fails a range check throw an error.
        if ($clean_limit === false || $clean_url === false) {
            trigger_error(TFISH_ERROR_PAGINATION_PARAMETER_ERROR, E_USER_ERROR);
        }
        
        $control = $this->_getPavigationControl($clean_count, $clean_limit, $clean_url,
                $clean_start, $clean_tag, $clean_extra_params);

        return $control;
    }

    /** @internal */
    private function _getPavigationControl(int $count, int $limit, string $url, int $start,
            int $tag, string $extra_params)
    {
        // 1. Calculate number of pages, page number of start object and adjust for remainders.
        $page_slots = array();
        $page_count = (int) (($count / $limit));
        $remainder = $count % $limit;
        
        if ($remainder) {
            $page_count += 1;
        }
        
        $page_range = range(1, $page_count);

        // No need for pagination control if only one page.
        if ($page_count === 1) {
            return false;
        }

        // 2. Calculate current page.
        $current_page = (int) (($start / $limit) + 1);

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
            $start = (int) ($key * $limit);

            // Set the arguments.
            if ($start || $tag || $extra_params) {
                $arg_array = array();
                
                if (!empty($start)) {
                    $arg_array[] = 'start=' . $start;
                }
                
                if (!empty($tag)) {
                    $arg_array[] = 'tag_id=' . $tag;
                }
                
                if (!empty($extra_params)) {
                    $arg_array[] = $extra_params;
                }
                
                $query = '?' . implode('&amp;', $arg_array);
            }

            if (($key + 1) === $current_page) {
                $control .= '<li class="page-item active"><a class="page-link" href="' . $url 
                        . $query . '">' . $slot . '</a></li>';
            } else {
                $control .= '<li class="page-item"><a class="page-link" href="' . $url . $query 
                        . '">' . $slot . '</a></li>';
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
    
}