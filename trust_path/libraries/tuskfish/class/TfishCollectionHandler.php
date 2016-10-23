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

class TfishCollectionHandler extends TfishContentHandler
{	
	function __construct()
	{
		// Must call parent constructor first.
		parent::__construct();
	}
	
	/**
	 * Returns an array of first children of a designed collection object.
	 * 
	 * This function is typically used to generate a list of the children of a collection object.
	 * 
	 * @param int $id
	 * @return array content objects
	 */
	public static function getFirstChild($id)
	{
		$clean_id = TfishFilter::isInt($id, 1) ? (int)$id : null;
		$first_children = array();
		
		if ($clean_id) {
			
			$criteria = new TfishCriteria();
			$criteria->add(new TfishCriteriaItem('parent', $clean_id));
			$criteria->add(new TfishCriteriaItem('online', true));
			$criteria->order = 'date';
			$criteria->ordertype = 'DESC';
			
			$first_children = self::getList($criteria);			
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
		
		$select_box = '<select id="parent" name="parent">';
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
