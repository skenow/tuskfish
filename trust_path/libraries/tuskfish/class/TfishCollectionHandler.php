<?php

/**
* Tuskfish collection object handler.
* 
* Provides collection-specific handler methods.
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");


class TfishCollectionHandler extends TfishContentHandler
{	
	function __construct()
	{
		// Must call parent constructor first.
		parent::__construct();
	}
	
	/**
	 * Get TfishCollection objects, optionally matching conditions specified with a TfishCriteria object.
	 * 
	 * Note that the collection type is automatically set, so when calling
	 * TfishCollectionHandler::getObjects($criteria) it is unecessary to set the object type.
	 * However, if you want to use TfishContentHandler::getObjects($criteria) then you do need to
	 * specify the object type, otherwise you will get all types of content returned. it is
	 * acceptable to use either handler, although probably good practice to use the object-
	 * specific one when you know you want a specific kind of object.
	 * 
	 * @param TfishCriteria $criteria query composer object
	 * @return array $objects TfishCollection objects
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
		$criteria->add(new TfishCriteriaItem('type', 'TfishCollection'));
		$objects = parent::getObjects($criteria);
		
		return $objects;
	}
	
	/**
	 * Count TfishCollection objects, optionally matching conditions specified with a TfishCriteria object.
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
		$criteria->add(new TfishCriteriaItem('type', 'TfishCollection'));
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
	 * Returns an array of first children of a designed collection object.
	 * 
	 * This function is typically used to generate a list of the children of a collection object.
	 * 
	 * @param int $id
	 * @return array content objects
	 */
	public static function getFirstChild($id, $offset = 0, $limit = 0)
	{
		$clean_id = TfishFilter::isInt($id, 1) ? (int)$id : null;
		$clean_offset = TfishFilter::isInt($offset) ? (int)$offset : 0;
		$clean_limit = TfishFilter::isInt($offset) ? (int)$offset : 0;
		$first_children = array();
		
		if ($clean_id) {
			
			$criteria = new TfishCriteria();
			$criteria->add(new TfishCriteriaItem('parent', $clean_id));
			$criteria->add(new TfishCriteriaItem('online', true));
			if ($offset) {
				$criteria->offset = $clean_offset;
			}
			if ($limit) {
				$criteria->limit = $clean_limit;
			}
			$criteria->order = 'date';
			$criteria->ordertype = 'DESC';
			
			$first_children = self::getObjects($criteria);			
		}
			
		return $first_children;
	}
	
	public static function getParentSelectBox($selected = 0)
	{
		$selected = (int)$selected;
		$clean_selected = TfishFilter::isInt($selected, 1) ? $selected : 0;
		$options = array(0 => TFISH_SELECT_PARENT);
		$select_box = '';
		
		$criteria = new TfishCriteria();
		$criteria->add(new TfishCriteriaItem('type', 'TfishCollection'));
		$criteria->order = 'title';
		$criteria->ordertype = 'ASC';
		$options = $options + self::getList($criteria);
		
		$select_box = '<select id="parent" name="parent" class="form-control">';
		if (!empty($options)) {
			foreach ($options as $key => $value) {
				if ($key == $clean_selected) {
					$select_box .= '<option value="' . $key . '" selected>' . $value . '</option>';
				} else {
					$select_box .= '<option value="' . $key . '">' . $value . '</option>';
				}
			}
		}
		$select_box .= '</select>';
		
		return $select_box;
	}
}
