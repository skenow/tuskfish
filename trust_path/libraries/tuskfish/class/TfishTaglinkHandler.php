<?php

/**
* Tuskfish taglink handler class.
* 
* Provides taglink-specific handler methods.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishTaglinkHandler extends TfishContentHandler
{
	function __construct()
	{
		// Must call parent constructor first.
		parent::__construct();
	}
	
	/**
	 * 
	 * 
	 * @param int $content_id of content object
	 * @param string $type alphabetical characters only and whitelisted in TfishTaglinkHandler::getType()
	 * @param array $tags as integers
	 * @return boolean true on success false on failure
	 */
	public static function insertTaglinks($content_id, $type, $tags)
	{
		if (TfishFilter::isInt($content_id, 1)) {
			$clean_content_id = (int)$content_id;
		} else {
			trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
			exit;
		}
		
		$typeList = self::getTypes();
		if (TfishFilter::isAlpha($type) && array_key_exists($type, $typeList)) {
			$clean_type = TfishFilter::trimString($type);
		} else {
			trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
			exit;
		}

		$clean_tags = array();
		foreach ($tags as $tag_id) {
			$tag = array();
			if (TfishFilter::isInt($tag_id, 1)) {
				$tag['tag_id'] = (int)$tag_id;
			} else {
				trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
			}
			$tag['content_id'] = $clean_content_id;
			$tag['content_type'] = $clean_type;
			$clean_tags[] = $tag;
			unset($tag);
		}
		foreach ($clean_tags as $clean_tag) {
			$result = TfishDatabase::insert('taglink', $clean_tag);
			if (!$result) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Updates taglinks for a particular content object.
	 * 
	 * Old taglinks are deleted, newly designated set of taglinks are inserted.
	 * 
	 * @param int $id of content object
	 * @param string $type
	 * @param array $tags as integers
	 * 
	 * @return boolean true on success false on failure
	 */
	public static function updateTaglinks($id, $type, $tags)
	{
		// Validate ID.
		if (TfishFilter::isInt($id, 1)) {
			$clean_id = (int)$id;
		} else {
			trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
		}
		
		// Validate type.
		$typeList = self::getTypes();
		if (TfishFilter::isAlpha($type) && array_key_exists($type, $typeList)) {
			$clean_type = TfishFilter::trimString($type);
		} else {
			trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
			exit;
		}
		
		// Validate tags.
		$clean_tag_id = array();
		if (TfishFilter::isArray($tags)) {
			foreach ($tags as $tag) {
				if (TfishFilter::isInt($tag, 1)) {
					$clean_tag_id[] = (int)$tag;
				} else {
					trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
				}
				unset($tag);
			}
		}
		
		// Delete any existing tags.
		$criteria = new TfishCriteria();
		$criteria->add(new TfishCriteriaItem('content_id', $clean_id));
		$result = TfishDatabase::deleteAll('taglink', $criteria);
		if (!$result) {
			return false;
		}
		unset($result);
		
		// Insert new taglinks, if any.
		$clean_tags = array();
		foreach ($clean_tag_id as $tag_id) {
			$tag = array();
			if (TfishFilter::isInt($tag_id, 1)) {
				$tag['tag_id'] = (int)$tag_id;
			} else {
				trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
			}
			$tag['content_id'] = $clean_id;
			$tag['content_type'] = $type;
			$clean_tags[] = $tag;
			unset($tag);
		}

		// Insert the new taglinks.
		foreach ($clean_tags as $clean_tag) {
			$result = TfishDatabase::insert('taglink', $clean_tag);
			if (!$result) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Delete taglinks associated with a particular content object.
	 * 
	 * @param int $content_id
	 * @return boolean true for success false on failure
	 */
	public static function deleteTaglinks($content_id)
	{
		if (TfishFilter::isInt($content_id, 1)) {
			$clean_content_id = (int)$content_id;
		} else {
			trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
		}
		$criteria = new TfishCriteria();
		$criteria->add(new TfishCriteriaItem('content_id', $clean_content_id));
		$result = TfishDatabase::deleteAll('taglink', $criteria);
		if (!$result) {
			return false;
		}
		
		return true;
	}
}