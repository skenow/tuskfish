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

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishTagHandler extends TfishContentHandler
{
	function __construct()
	{
		// Must call parent constructor first.
		parent::__construct();
	}
	
	/**
	 * Get TfishTag objects, optionally matching conditions specified with a TfishCriteria object.
	 * 
	 * Note that the article type is automatically set, so when calling
	 * TfishTagHandler::getObjects($criteria) it is unecessary to set the object type.
	 * However, if you want to use TfishContentHandler::getObjects($criteria) then you do need to
	 * specify the object type, otherwise you will get all types of content returned. it is
	 * acceptable to use either handler, although probably good practice to use the object-
	 * specific one when you know you want a specific kind of object.
	 * 
	 * @param TfishCriteria $criteria query composer object
	 * @return array $objects TfishArticle objects
	 */
	public static function getObjects($criteria = false)
	{
		if (!$criteria) {
			$criteria = new TfishCriteria();
		}
		
		// Unset any pre-existing object type criteria.
		$type_key = self::getTypeIndex($criteria->item);
		if (isset($type_key)) {
			$criteria->killType($type_key);
		}
		
		// Set new type criteria specific to this object.
		$criteria->add(new TfishCriteriaItem('type', 'TfishTag'));
		$objects = parent::getObjects($criteria);
		
		return $objects;
	}
	
	/**
	 * Count TfishArticle objects, optionally matching conditions specified with a TfishCriteria object.
	 * 
	 * @param TfishCriteria $criteria
	 * @return int $count
	 */
	public static function getCount($criteria = false)
	{
		if (!$criteria) {
			$criteria = new TfishCriteria();
		}
		
		// Unset any pre-existing object type criteria.
		$type_key = self::getTypeIndex($criteria->item);
		if (isset($type_key)) {
			$criteria->killType($type_key);
		}
		
		// Set new type criteria specific to this object.
		$criteria->add(new TfishCriteriaItem('type', 'TfishTag'));
		$count = parent::getcount($criteria);

		return $count;
	}
	
	/**
	 * Search the filtering criteria ($criteria->items) to see if object type has been set and
	 * return the key.
	 * 
	 * @param array $criteria_items
	 * @return int|null
	 */
	private static function getTypeIndex($criteria_items)
	{
		foreach ($criteria_items as $key => $item) {
			if ($item->column == 'type') {
				return $key;
			}
		}
		return null;
	}
	
	/**
	 * Generates a tag select box control.
	 * 
	 * @param int $selected
	 * @return boolean|string
	 */
	public static function getTagSelectBox($selected = null, $type = null, $zero_option = TFISH_SELECT_TAGS)
	{
		$select_box = '';
		$tag_list = array();
		
		$clean_selected = (isset($selected) && TfishFilter::isInt($selected, 1)) ? (int)$selected : null; // ID of a previously selected tag, if any.
		$clean_zero_option = TfishFilter::escape(TfishFilter::trimString($zero_option)); // The text to display in the zero option of the select box.
		$clean_type = TfishContentHandler::isSanctionedType($type) ? TfishFilter::trimString($type) : null;  // Used to filter tags relevant to a specific content subclass, eg. TfishArticle.
		
		$tag_list = TfishContentHandler::getActiveTagList($clean_type);
		if (!empty($tag_list)) {
			asort($tag_list);
			$tag_list = array(0 => $clean_zero_option) + $tag_list;
			$select_box = '<select class="form-control" name="tag_id" id="tag_id" onchange="this.form.submit()">';
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