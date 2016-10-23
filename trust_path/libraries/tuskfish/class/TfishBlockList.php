<?php

/**
* Tuskfish block list class.
* 
* Generates a traditional 'headline list' style of block, consisting of a block heading and a list
* of content titles/links. 
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishBlockList extends TfishBlock
{	
	function __construct($title)
	{
		parent::__construct();
		
		$this->__set('title', $title); // String: Title of the block (blank for no title).
		$this->__set('online', 1); // Int: Toggle object on (1) or offline (0).
		$this->__set('template', 'block_list'); // String: The template that should be used to display this block.
	}
	
	/**
	 * Generates HTML code to display the block.
	 * 
	 * @param object $criteria TfishCriteria object
	 * @return string HTML output of block
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
	
	/**
	 * Renders a block into HTML code.
	 * 
	 * Extracts all properties assigned to the template object as variables and includes the
	 * designated template file. The extracted variables are used to populate the dynamic sections
	 * of the template. Templates can be nested by assigning a rendered child template as a property
	 * of a parent template object.
	 * 
	 * @return string HTML output of template
	 */
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