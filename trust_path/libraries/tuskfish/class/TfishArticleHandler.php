<?php

/**
* Tuskfish article content object handler
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishArticleHandler extends TfishContentHandler
{	
	function __construct()
	{
		// Must call parent constructor first.
		parent::__construct();
	}
	
	/**
	 * Get TfishArticle objects.
	 * 
	 * Note that the article type is automatically set, so when calling
	 * TfishArticleHandler::getObjects($criteria) it is unecessary to set the object type.
	 * However, if you want to use TfishContentHandler::getObjects($criteria) then you do need to
	 * specify the object type, otherwise you will get all types of content returned. it is
	 * acceptable to use either handler, although probably good practice to use the object-
	 * specific one when you know you want a specific kind of object.
	 * 
	 * @param TfishCriteria $criteria
	 * @return array $objects
	 */
	public static function getObjects($criteria = false)
	{
		if (!$criteria) {
			$criteria = new TfishCriteria();
		}
		$criteria->add(new TfishCriteriaItem('type', 'TfishArticle'));
		$objects = parent::getObjects($criteria);
		
		return $objects;
	}
	
	/**
	 * Count TfishArticle objects.
	 * 
	 * @param TfishCriteria $criteria
	 * @return int $count
	 */
	public static function getCount($criteria = false)
	{
		if (!$criteria) {
			$criteria = new TfishCriteria();
		}
		$criteria->add(new TfishCriteriaItem('type', 'TfishArticle'));
		$count = parent::getcount($criteria);
		
		return $count;
	}
}
