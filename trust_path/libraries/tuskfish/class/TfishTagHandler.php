<?php

/**
* Tuskfish tag handler object class
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishTagHandler extends TfishContentHandler
{
	function __construct()
	{
		// Must call parent constructor first.
		parent::__construct();
	}
	
	/**
	 * Generates a tag slect box, optionally with an item selected.
	 * 
	 * @param int $selected
	 * @return boolean|string
	 */
	public static function getTagSelectBox($selected = false)
	{
		$select_box = '';
		$tag_list = array();
		$clean_selected = (int)$selected;
		
		$tag_list = array(0 => TFISH_TAGS_SELECT_TAG) + TfishTagHandler::getTagList();
		if (!empty($tag_list)) {
			$select_box = '<select>';
			foreach($tag_list as $key => $value) {
				$select_box .= ($key == $selected) ? '<option value="' . $key . '" selected>' . $value . '</option>' : '<option value="' . $key . '">' . $value . '</option>';
			}
			$select_box .= '</select>';
			return $select_box;
		} else {
			return false;
		}
	}	
}