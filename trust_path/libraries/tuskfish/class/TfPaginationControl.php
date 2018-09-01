<?php

/**
 * TfPaginationControl class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1
 * @package     core
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Generates pagination controls for paging through content.
 * 
 * The number of pagination control slots is set in Tuskfish Preferences. Choose an odd number for
 * best results.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1
 * @package     core
 * @var         TfValidator $validator Instance of the Tuskfish data valiator class.
 * @var         TfPreference $preference Instance of the Tuskfish site preference class.
 * @var         int $count Number of objects (pages) matching these parameters.
 * @var         int $limit Number of objects to retrieve in current view.
 * @var         string $url Target base URL for pagination control links.
 * @var         int $start Position in result set to retrieve objects from.
 * @var         int $tag ID of tag used to filter content.
 * @var         array $extraParams Query string to be appended to the URLs (control script params).
 */
class TfPaginationControl
{
    
    protected $validator;
    protected $preference;
    protected $count;
    protected $limit;
    protected $url;
    protected $start;
    protected $tag;
    protected $extraParams;
    
    /**
     * Constructor.
     * 
     * @param TfValidator $validator An instance of the Tuskfish data validator class.
     * @param TfPreference $preference An instance of the Tuskfish site preferences class.
     */
    function __construct(TfValidator $validator, TfPreference $preference)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator; 
        } else {
            trigger_error(TFISH_ERROR_NOT_VALIDATOR, E_USER_ERROR);
        }
        
        if (is_a($preference, 'TfPreference')) {
            $this->preference = $preference;
        }  else {
            trigger_error(TFISH_ERROR_NOT_PREFERENCE, E_USER_ERROR);
        }
        
        $this->count = 0;
        $this->limit = 0;
        $this->url = TFISH_URL;
        $this->start = 0;
        $this->tag = 0;
        $this->extraParams = array();
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
     * @return string HTML pagination control.
     */
    public function renderPaginationControl()
    {
        // If the count is zero there is no need for a pagination control.
        if ($this->count === 0) {
            return false;
        }
        
        // 1. Calculate number of pages, page number of start object and adjust for remainders.
        $pageSlots = array();
        $pageCount = (int) (($this->count / $this->limit));
        $remainder = $this->count % $this->limit;
        
        if ($remainder) {
            $pageCount += 1;
        }
        
        $pageRange = range(1, $pageCount);

        // No need for pagination control if only one page.
        if ($pageCount === 1) {
            return false;
        }

        // 2. Calculate current page.
        $currentPage = (int) (($this->start / $this->limit) + 1);

        // 3. Calculate length of pagination control (number of slots).
        $elements = ((int) $this->preference->paginationElements > $pageCount)
                ? $pageCount : (int) $this->preference->paginationElements;

        // 4. Calculate the fore offset and initial (pre-adjustment) starting position.
        $offsetInt = (int) (($elements - 1) / 2);
        $offsetFloat = ($elements - 1) / 2;
        $pageStart = $currentPage - $offsetInt;

        // 5. Check if fore exceeds bounds. If so, set start = 1 and extract the range.
        $foreBoundcheck = $currentPage - $offsetInt;
         
        // 6. Check if aft exceeds bounds. If so set start = $pageCount - length.
        $aftBoundcheck = ($currentPage + $offsetFloat);

        // This is the tricky bit - slicing a variable region out of the range.
        if ($pageCount === $elements) {
            $pageSlots = $pageRange;
        } elseif ($foreBoundcheck < 1) {
            $pageSlots = array_slice($pageRange, 0, $elements, true);
        } elseif ($aftBoundcheck >= $pageCount) {
            $pageStart = $pageCount - $elements;
            $pageSlots = array_slice($pageRange, $pageStart, $elements, true);
        } else {
            $pageSlots = array_slice($pageRange, ($pageStart - 1), $elements, true);
        }

        // 7. Substitute in the 'first' and 'last' page elements and sort the array back into
        // numerical order.
        end($pageSlots);
        unset($pageSlots[key($pageSlots)]);
        $pageSlots[($pageCount - 1)] = TFISH_PAGINATION_LAST;
        reset($pageSlots);
        unset($pageSlots[key($pageSlots)]);
        $pageSlots[0] = TFISH_PAGINATION_FIRST;
        ksort($pageSlots);
        
        return $this->_renderPaginationControl($pageSlots, $currentPage);
    }
    
    /** @internal */
    private function _renderPaginationControl(array $pageSlots, int $currentPage)
    {
        $control = '<nav aria-label="Page navigation"><ul class="pagination">';

        // Prepare the query string.
        $query = '';
        
        foreach ($pageSlots as $key => $slot) {
            $this->start = (int) ($key * $this->limit);

            // Set the arguments.
            if ($this->start || $this->tag || $this->extraParams) {
                $args = array();
                
                if (!empty($this->start)) {
                    $args[] = 'start=' . $this->start;
                }
                
                if (!empty($this->tag)) {
                    $args[] = 'tagId=' . $this->tag;
                }
                
                if (!empty($this->extraParams)) {
                    $args[] = $this->extraParams;
                }
                
                $query = '?' . implode('&amp;', $args);
            }

            if (($key + 1) === $currentPage) {
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
    
    /**
     * Set the count property, which represents the number of objects matching the page parameters.
     * 
     * @param int $count
     */
    public function setCount($count)
    {
        $cleanCount = (int) $count;
        $this->count = $this->validator->isInt($cleanCount, 0) ? $cleanCount : 0;
    }
    
    /**
     * Sets the limit property, which controls the number of objects to be retrieved in a single
     * page view.
     * 
     * @param int $limit Number of content objects to retrieve in current view.
     */
    public function setLimit(int $limit)
    {
        $cleanLimit = (int) $limit;
        $this->limit = $this->validator->isInt($cleanLimit, 0) ? $cleanLimit : 0;
    }
    
    /**
     * Set the base URL for pagination control links.
     * 
     * @param string $url Base file name for constructing URLs, without the extension.
     */
    public function setUrl(string $url)
    {
        $cleanUrl = $this->validator->trimString($url);
        $this->url = $this->validator->isAlnumUnderscore($cleanUrl) ? $cleanUrl . '.php'
                : TFISH_URL;
    }

    /**
     * Set the starting position in the set of available object.
     * 
     * @param int $start ID of first object to view in the set of available records.
     */
    public function setStart(int $start)
    {
        $cleanStart = (int) $start;
        $this->start = $this->validator->isInt($cleanStart, 0) ? $cleanStart : 0;
    }
    
    /**
     * Set the ID of a tag used to filter content.
     * 
     * @param int $tag ID of tag used to filter content.
     */
    public function setTag(int $tag)
    {
        $cleanTag = (int) $tag;
        $this->tag = $this->validator->isInt($cleanTag, 0) ? $cleanTag : 0;
    }
    
    /**
     * Set extra parameters to be included in pagination control links.
     * 
     * $extraParams is a potential XSS attack vector.
     * The key => value pairs be i) rawurlencoded and ii) entity escaped. However, in order to
     * avoid messing up the query and avoid unnecessary decoding it is possible to maintain
     * manual control over the operators. (Basically, input requiring encoding or escaping is
     * absolutely not wanted here, it is just being conducted to mitigate XSS attacks). If you
     * actually *want* to use such input (check your sanity), you will need to decode it prior to
     * use on the landing page.
     * 
     * @param array $extraParams Query string to be appended to the URLs (control script params)
     * @return boolean Returns false on failure.
     */
    public function setExtraParams(array $extraParams)
    {
        $clean_extraParams = array();
        
        foreach ($extraParams as $key => $value) {
            if ($this->validator->hasTraversalorNullByte((string) $key)
                    || $this->validator->hasTraversalorNullByte((string) $value)) {
                trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
                return false;
            }
        
            $clean_extraParams[] = $this->validator->encodeEscapeUrl($key) . '='
                    . $this->validator->encodeEscapeUrl((string) $value);
            unset($key, $value);
        }
        
        if (empty($clean_extraParams)) {
            $this->extraParams = '';
        } else {
            $this->extraParams = $this->validator->escapeForXss(implode("&", $clean_extraParams));
        }   
    }
    
}