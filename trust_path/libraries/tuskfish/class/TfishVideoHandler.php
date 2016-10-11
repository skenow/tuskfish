<?php

/**
* Tuskfish video content object handler.
* 
* Provides video-specific handler methods.
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishVideoHandler extends TfishContentHandler
{	
	function __construct()
	{
		// Must call parent constructor first.
		parent::__construct();
	}
	
	/**
	 * Retrieves an array of TfishVideo objects, optionally matching conditions specified with a TfishCriteria object.
	 * 
	 * @param TfishCriteria $criteria for filtering the database select query
	 * @return array of video objects
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
		$criteria->add(new TfishCriteriaItem('type', 'TfishVideo'));
		$objects = parent::getObjects($criteria);
		
		return $objects;
	}
	
	/**
	 * Counts TfishVideo objects optionally matching conditions specified with a TfishCriteria object.
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
		$criteria->add(new TfishCriteriaItem('type', 'TfishVideo'));
		$count = parent::getcount($criteria);

		return $count;
	}
	
	/**
	 * Search the filtering criteria ($criteria->items) to see if object type has been set and
	 * return the key.
	 * 
	 * @param array $criteria_items
	 * @return mixed
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
}
