<?php

/**
* Tuskfish block object class
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishBlockList extends TfishBlock
{	
	/**
	 * Generic constructor
	 */
	function __construct($title, $limit)
	{
		parent::__construct();
		
		/**
		 * Set default values of permitted properties.
		 */
		$this->__set('title', $title); // String: Title of the block (blank for no title).
		$this->__set('limit', $limit); // Int: Number of objects to be displayed.
		// $this->__set('type', $type): Alpha: Class name for this block type.
		// $this->__set('online', 1); // Int: Toggle object on (1) or offline (0).
		// $this->__set('handler', $handler); // String: Handler for this object.
		$this->__set('template', 'blocklist'); // String: The template that should be used to display this block.

	}
	
	/**
	 * Generates HTML code to display the block.
	 * 
	 * @param object $criteria
	 * @return string
	 */
	public function render($criteria = false)
	{
		if ($criteria) {
			$clean_criteria = TfishDatabase::validateCriteriaObject($criteria);
		} else {
			$clean_criteria = new TfishCriteria();
		}

		return $this->_render($clean_criteria);		
	}
	
	private function _render($criteria)
	{	
		$content_handler = new TfishContentHandler();
		$content_objects = $content_handler->getObjects($criteria);
		if (!empty($content_objects)) {
			$block = array('title' => TfishFilter::escape($this->title));
			foreach ($content_objects as $object) {
				$block['content'][TfishFilter::escape($object->id)] =  TfishFilter::escape($object->title);
			}
		} else {
			return false;
		}
		
		// Template should be handled here...somehow.
		$output = '<h3>' . $block['title'] . '</h3>';
		$output .= '<ul>';
		foreach ($block['content'] as $id => $title) {
			$output .= '<li><a href="' . TFISH_URL . '?id=' . $id . '">' . $title . '</a></li>';
		}
		$output .= '</ul>';
			
		return $output;
	}
}