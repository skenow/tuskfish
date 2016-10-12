<?php

/**
* Tuskfish tag handler object class.
* 
* Provides tag-specific handler methods.
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
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
	 * Generates a tag select box.
	 * 
	 * Set the action parameter to have the select box returned within its own form. Leave it empty
	 * to return a select box only, ie. if you want to embed it within a larger form.
	 * 
	 * @param int $selected
	 * @return boolean|string
	 */
	public static function getTagSelectBox($selected = null, $action = false, $type = null, $zero_option = TFISH_SELECT_TAGS)
	{
		$action = TfishFilter::trimString($action);
		$select_box = '';
		$tag_list = array();
		
		$clean_action = TfishFilter::isAlnumUnderscore($action) ? TfishFilter::escape(TfishFilter::trimString($action)) . '.php' : ''; // Name of script to load on submission. Could be user side or admin side.
		$clean_selected = (isset($selected) && TfishFilter::isInt($selected, 1)) ? (int)$selected : null; // ID of a previously selected tag, if any.
		$clean_zero_option = TfishFilter::escape(TfishFilter::trimString($zero_option)); // The text to display in the zero option of the select box.
		$clean_type = TfishContentHandler::isSanctionedType($type) ? TfishFilter::trimString($type) : null;  // Used to filter tags relevant to a specific content subclass, eg. TfishArticle.
		
		$tag_list = TfishContentHandler::getActiveTagList($clean_type);
		if (!empty($tag_list)) {
			asort($tag_list);
			$tag_list = array(0 => $clean_zero_option) + $tag_list;
			$select_box = !empty($clean_action) ? '<form name="tag_select_form" action="' . $clean_action . '" method="get">' : '';
			$select_box .= '<select name="tag_id" id="tag_id" onchange="this.form.submit()">';
			foreach($tag_list as $key => $value) {
				$select_box .= ($key == $selected) ? '<option value="' . $key . '" selected>' . $value . '</option>' : '<option value="' . $key . '">' . $value . '</option>';
			}
			$select_box .= '</select>';
			$select_box .= !empty($clean_action) ? '</form>' : '';
			return $select_box;
		} else {
			return false;
		}
	}
}