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
	function __construct($title)
	{
		parent::__construct();
		
		$this->__set('title', $title); // String: Title of the block (blank for no title).
		// $this->__set('type', $type): Alpha: Class name for this block type.
		// $this->__set('online', 1); // Int: Toggle object on (1) or offline (0).
		// $this->__set('handler', $handler); // String: Handler for this object.
		$this->__set('template', 'block_list'); // String: The template that should be used to display this block.

	}
	
	/**
	 * Generates HTML code to display the block.
	 * 
	 * @param object $criteria
	 * @return string
	 */
	public function build($criteria = false)
	{
		if ($criteria) {
			$clean_criteria = TfishDatabase::validateCriteriaObject($criteria);
		} else {
			$clean_criteria = new TfishCriteria();
		}
		
		// Set some sensible defaults.
		if (empty($clean_criteria->limit)) {
			$clean_criteria->limit = 5;
		}
		if (empty($clean_criteria->order)) {
			$clean_criteria->order = 'submission_time';
		}

		return $this->_build($clean_criteria);		
	}
	
	private function _build($criteria)
	{	
		$content_handler = new TfishContentHandler();
		$content_objects = $content_handler->getObjects($criteria);
		if (!empty($content_objects)) {
			$this->items = $content_objects;
			return true;
		} else {
			return false;
		}
	}
	
	public function render()
	{
		extract($this->__data);
		if (file_exists(TFISH_TEMPLATES_OBJECT_PATH . $this->template . '.html')) {
			ob_start();
			$title = $this->__data['title'];
			include TFISH_TEMPLATES_OBJECT_PATH . $this->template . '.html';
			return ob_get_clean();
		} else {
			trigger_error(TFISH_ERROR_TEMPLATE_DOES_NOT_EXIST, E_USER_ERROR);
		}
	}
}