<?php

/**
* Tuskfish page-level metadata class
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishMetadata
{	
	private $preference;
	protected $__data = array(
		'template' => '',
		'title' => '',
		'description' => '',
		'author' => '',
		'copyright' => '',
		'generator' => '',
		'seo' => '',
		'robots' => '',
		'pagination_elements' => '');
	
	/**
	 * Generic constructor
	 */
	function __construct($preference)
	{		
		$this->template = 'default.html';
		$this->title = $preference->site_name;
		$this->description = $preference->site_description;
		$this->author = $preference->site_author;
		$this->copyright = $preference->site_copyright;
		$this->generator = 'Tuskfish CMS';
		$this->seo = '';
		$this->robots = 'index,follow';
		$this->pagination_elements = $preference->pagination_elements;
	}
	
	/**
	 * Access an existing object property and escape it for output to browser.
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property)
	{
		if (isset($this->__data[$property])) {
			return htmlspecialchars($this->__data[$property], ENT_QUOTES, "UTF-8");
		} else {
			return false;
		}
	}
	
	/**
	 * Set an existing object property
	 * 
	 * @param mixed $property
	 * @param mixed $value
	 */
	public function __set($property, $value)
	{
		if (isset($this->__data[$property])) {
			$value = TfishFilter::trimString($value);
			$clean_value = TfishFilter::escape($value);
			$this->__data[$property] = $clean_value;
		} else {
			trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
		}
	}
	
	/**
	 * Intercept isset() calls to correctly read object properties
	 * 
	 * @param type $property
	 * @return type 
	 */
	public function __isset($property)
	{
		if (isset($this->__data[$property])) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Intercept unset() calls to correctly unset object properties
	 * 
	 * @param type $property
	 * @return type 
	 */
	public function __unset($property)
	{
		if (isset($this->__data[$property])) {
			unset($this->__data[$property]);
		} else {
			return false;
		}
	}
	
	/**
	 * Creates a pagination control designed for use with the Bootstrap framework.
	 * 
	 * If you want to create pagination controls for other presentation-side libraries add
	 * additional methods to this class.
	 * 
	 * @param int $count
	 * @param int $limit
	 * @param int $start
	 * @param string $url
	 * @return string
	 */
	public function getPaginationControl($count, $limit, $url, $start = 0, $tag = 0)
	{
		// Filter parameters.
		$clean_count = TfishFilter::isInt($count, 1) ? (int)$count : false;
		$clean_limit = TfishFilter::isInt($limit, 1) ? (int)$limit : false;
		$clean_start = TfishFilter::isInt($start, 0) ? (int)$start : 0;
		$clean_url = TfishFilter::isAlnumUnderscore($url) ? TfishFilter::escape($url) . '.php' : TFISH_URL;
		$clean_tag = TfishFilter::isInt($tag) ? (int)$tag : 0;
		
		// If the count is zero there is no need for a pagination control.
		if ($clean_count == 0) {
			return false;
		}
		// If any parameter fails a range check throw an error.
		if ($clean_limit === false || $clean_url === false) {
			trigger_error(TFISH_ERROR_PAGINATION_PARAMETER_ERROR, E_USER_ERROR);
		}
		
		$control = $this->_getPavigationControl($clean_count, $clean_limit, $clean_url,
				$clean_start, $clean_tag);

		return $control;
	}
	
	private function _getPavigationControl($count, $limit, $url, $start, $tag)
	{
		// Calculate number of pages, page number of start object and adjust for remainders.
		$page_count = (int)(($count / $limit));
		$current_page = (int)(($start / $limit) + 1);
		$remainder = $count % $limit;
		if ($remainder) {
			$page_count += 1;
		}

		// No need for pagination control if only one page.
		if ($page_count == 1) {
			return false;
		}
		
		// Handling pagination for multiple pages.	
		$page_slots = array();
		$page_slots[$current_page] = $current_page;
		for ($i = 1; $i < $this->pagination_elements; $i++) {
			$page_slots[$current_page - $i] = $current_page - $i;
			$page_slots[$current_page + $i] = $current_page + $i;
		}
		ksort($page_slots);
		$page_range = range(1, $page_count);
		$page_slots = array_intersect($page_slots, $page_range);
		$page_slots[1] = TFISH_PAGINATION_FIRST;
		if ($page_count > count($page_slots)) {
			array_pop($page_slots);
			$page_slots[$page_count] = TFISH_PAGINATION_LAST;
		}

		// Construct a HTML pagination control.
		$control = '<ul class="pagination">';
		
		// Prepare the query string.
		$query = $start_arg = $tag_arg = '';	
		foreach ($page_slots as $key => $slot) {
			$start = (int)(($key - 1) * $limit);
			$query = ($start || $tag) ? '?' : '';
			$start_arg = !empty($start) ? 'start=' . $start : '';
			$separator = (!empty($start) && !empty($tag)) ? '&amp;' : '';
			$tag_arg = !empty($tag) ? 'tag_id=' . $tag : '';
			if ($key == $current_page) {
				$control .= '<li class="active"><a href="' . $url . $query . $start_arg . $separator . $tag_arg . '">' . $slot . '</a></li>';
			} else {
				$control .= '<li><a href="' . $url . $query . $start_arg . $separator . $tag_arg . '">' . $slot . '</a></li>';
			}
			unset($query, $separator, $start_arg, $tag_arg, $key, $slot);
		}
		$control .= '</ul>';
		
		return $control;
	}
}