<?php

/**
* Tuskfish ancestral object handler class
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishContentHandler
{
	function __construct()
	{
	}
	
	/**
	 * Retrieves a single content object based on its ID.
	 * 
	 * @param int $id
	 * @return mixed
	 */
	public static function getObject($id)
	{
		$clean_id = (int)$id;
		if (TfishFilter::isInt($id, 1)) {
			$criteria = new TfishCriteria();
			$criteria->add(new TfishCriteriaItem('id', $clean_id));
			$statement = TfishDatabase::select('content', $criteria);
			if ($statement) {
				$row = $statement->fetch(PDO::FETCH_ASSOC);
				$object = self::toObject($row);
				return $object;
			}
		}
		return false;
	}
	
	// Arguments for injecting the object handler as a dependency:
	// 1. Can directly access subclass-specific handler methods without knowing the class (good).
	// 
	// Arguments against:
	// 1. Have to manually inspect the 'type' and build the handler / object names + instantiate a handler just to make an object (bad).
	// 2. If 'type' and 'handler' values were class names it would be easier to instantiate objects.
	
	public static function getObjects($criteria = false)
	{
		$objects = array();
		$statement = TfishDatabase::select('content', $criteria);
		if ($statement) {
			try {
				// Fetch rows into the appropriate class type, as determined by the first column.
				$statement->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_CLASSTYPE|PDO::FETCH_PROPS_LATE);
				while ($object = $statement->fetch()) {
					$objects[$object->id] = $object;
				}
				unset($statement);
			} catch (PDOException $e) {
				TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
			}
		} else {
			trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
		}
		
		// Get the tags for these objects.
		if (!empty($objects)) {
			$taglinks = array();
			$object_ids = array_keys($objects);
			
			$criteria = new TfishCriteria();
			foreach ($object_ids as $id) {
				$criteria->add(new TfishCriteriaItem('content_id', (int)$id), "OR");
				unset($id);
			}
			
			$statement = TfishDatabase::select('taglink', $criteria);
			if ($statement) {
				try {
					// Sort tag into multi-dimensional array indexed by content_id.
					while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
						$taglinks[$row['content_id']][] = $row['tag_id'];
					}
				} catch (PDOException $e) {
					TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
				}
				
				// Assign the sorted tags to correct content objects.
				foreach ($taglinks as $content_id => $tags) {
					$objects[$content_id]->tags = $tags;
					unset($tags);
				}
			} else {
				trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
			}
		}
		
		return $objects;
	}
	
	public static function getList($criteria = false)
	{
	}
	
	/**
	 * Counts the number of content objects matching the criteria (without the limit).
	 * 
	 * Main use is for constructing the pagination control.
	 * 
	 * @param obj $criteria
	 * @return int
	 */
	public static function getCount($criteria = false)
	{
		if ($criteria && !empty($criteria->limit)) {
			unset($criteria->limit);
		}
		$count = TfishDatabase::selectCount('content', $criteria);
		
		return $count;
	}
	
	/**
	 * Returns a list of languages for the content object submission form.
	 * 
	 * In the interests of brevity and sanity a full list is not provided. Add entries that you
	 * want to use to the array using ISO 639-1 two-letter language codes, which you can find at:
	 * https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes. Be aware that deleting entries that
	 * are in use by your content objects will cause errors.
	 * 
	 * @return array
	 */
	public static function getLanguages() {
		return array(
			"en" => "English",
			"th" => "Thai",
		);
	}
	
	/**
	 * Returns a list of intellectual property rights licenses for the content submission form.
	 * 
	 * In the interests of brevity and sanity, a comprehensive list is not provided. Add entries
	 * that you want to use to the array below. Be aware that deleting entries that are in use by
	 * your content objects will cause errors.
	 * 
	 * @return array
	 */
	public static function getRights()
	{
		return array(
			'1' => TFISH_RIGHTS_COPYRIGHT,
			'2' => TFISH_RIGHTS_ATTRIBUTION,
			'3' => TFISH_RIGHTS_ATTRIBUTION_SHARE_ALIKE,
			'4' => TFISH_RIGHTS_ATTRIBUTION_NO_DERIVS,
			'5' => TFISH_RIGHTS_ATTRIBUTION_NON_COMMERCIAL,
			'6' => TFISH_RIGHTS_ATTRIBUTION_NON_COMMERCIAL_SHARE_ALIKE,
			'7' => TFISH_RIGHTS_ATTRIBUTION_NON_COMMERCIAL_NO_DERIVS,
			'8' => TFISH_RIGHTS_GPL2,
			'9' => TFISH_RIGHTS_GPL3,
			'10' => TFISH_RIGHTS_PUBLIC_DOMAIN,
		);
	}
	
	/**
	 * Returns a whitelist of permitted content object types.
	 * 
	 * Use this whitelist when dynamically instantiating content objects.
	 * 
	 * @return array
	 */
	public static function getTypes()
	{
		return array(
			'TfishArticle' => TFISH_TYPE_ARTICLE,
			'TfishAudio' => TFISH_TYPE_AUDIO,
			'TfishCollection' => TFISH_TYPE_COLLECTION,
			'TfishDownload' => TFISH_TYPE_DOWNLOAD,
			'TfishImage' => TFISH_TYPE_IMAGE,
			'TfishPodcast' => TFISH_TYPE_PODCAST,
			'TfishStatic' => TFISH_TYPE_STATIC,
			'TfishTag' => TFISH_TYPE_TAG,
			'TfishVideo' => TFISH_TYPE_VIDEO,
		);
	}
	
	
	public static function getTagList()
	{
		$tags = array();
		$criteria = new TfishCriteria();
		$columns = array('id', 'title');
		$statement = false;
		
		$criteria->add(new TfishCriteriaItem('type', 'TfishTag'));
		$statement = TfishDatabase::select('content', $criteria, $columns);
		if ($statement) {
			try {
				while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
					$tags[$row['id']] = $row['title'];
				}
			} catch (PDOException $e) {
				TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
			}
		} else {
			trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
		}
		
		return $tags;
	}
	
	/**
	 * Returns a list of tag objects.
	 * 
	 * @return array tag objects
	 */
	public static function getTags()
	{
		$tags = array();
		$criteria = new TfishCriteria();
		$criteria->add(new TfishCriteriaItem('type', 'TfishTag'));
		$tags = self::getObjects($criteria);
		return $tags;
	}
	
	public static function updateCounter()
	{
	}
	
	/**
	 * Inserts a content object into the database.
	 * 
	 * Note that content child content classes that have unset unused properties from the parent
	 * should reset them to null before insertion or update. This is to guard against the case
	 * where the admin reassigns the type of a content object - it makes sure that unused properties
	 * are zeroed in the database. 
	 * 
	 * @param object $obj
	 * @return boolean
	 */
	public static function insert($obj)
	{
		$key_values = $obj->toArray();
		$key_values['submission_time'] = time(); // Automatically set submission time.
		unset($key_values['id']); // ID is auto-incremented by the database on insert operations.
		unset($key_values['tags']);
		
		// Insert the object into the database.
		$result = TfishDatabase::insert('content', $key_values);
		if (!$result) {
			trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
			return false;
		} else {
			$content_id = TfishDatabase::lastInsertId();
		}
		unset($key_values, $result);
		
		// Tags are stored separately in the taglinks table. Tags are assembled in one batch before
		// proceeding to insertion; so if one fails a range check all should fail.
		if (isset($obj->tags) and TfishFilter::isArray($obj->tags)) {

			// If the lastInsertId could not be retrieved, then halt execution becuase this data
			// is necessary in order to correctly assign taglinks to content objects.
			if (!$content_id) {
				trigger_error(TFISH_ERROR_NO_LAST_INSERT_ID, E_USER_ERROR);
				exit;
			}
			
			$result = TfishTaglinkHandler::insertTaglinks($content_id, $obj->type, $obj->tags);
			if (!$result) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Provides global search functionality for content objects.
	 *
	 * @param array $queryarray
	 * @param string $andor
	 * @param int $limit
	 * @param int $offset
	 * @param int $userid
	 * @return array 
	 */
	public static function searchContent($search_terms, $andor, $limit, $offset = 0)
	{
		global $tfish_preference;
		$clean_search_terms = array();
		$clean_andor = in_array($andor, array('AND', 'OR', 'exact')) ? TfishFilter::trimString($andor) : 'AND';
		$clean_limit = (int)$limit;
		$clean_offset = (int)$offset;
		
		if ($clean_andor == 'AND' || $clean_andor == 'OR') {
			$search_terms = explode(" ", $search_terms);
		} else {
			$search_terms = array($search_terms);
		}
		
		// Trim search terms and discard any that are less than the minimum search length characters.
		foreach($search_terms as $term) {
			$term = TfishFilter::TrimString($term);
			if (!empty($term) && mb_strlen($term, 'UTF-8') >= $tfish_preference->min_search_length) {
				$clean_search_terms[] = (string)$term; 
			}
		}
		$results = self::_searchContent($clean_search_terms, $clean_andor, $clean_limit, $clean_offset);
		
		return $results;
	}
	
	private function _searchContent($search_terms, $andor, $limit, $offset)
	{
		$sql = $count = '';
		$search_term_placeholders = $results = array();
		$sql_count = "SELECT count(*) ";
		$sql_search = "SELECT * ";
		$result = array();
		
		$sql = "FROM `content` ";		
		$count = count($search_terms);
		if ($count) {
			$sql .= "WHERE ";
			for ($i = 0; $i < $count; $i++) {
				$search_term_placeholders[$i] = ':search_term' . (string)$i;
				$sql .= "(";
				$sql .= "`title` LIKE " . $search_term_placeholders[$i] . " OR ";
				$sql .= "`teaser` LIKE " . $search_term_placeholders[$i] . " OR ";
				$sql .= "`description` LIKE " . $search_term_placeholders[$i] . " OR ";
				$sql .= "`caption` LIKE " . $search_term_placeholders[$i] . " OR ";
				$sql .= "`creator` LIKE " . $search_term_placeholders[$i] . " OR ";
				$sql .= "`publisher` LIKE " . $search_term_placeholders[$i];
				$sql .= ")";
				if ($i != ($count - 1)) {
					$sql .= " " . $andor . " ";
				}
			}
		}
		$sql .= " AND `online` = '1' ORDER BY `date` DESC ";
		$sql_count .= $sql;
		
		// Bind the search term values and execute the statement.
		try {
			$statement = TfishDatabase::preparedStatement($sql_count);
			if ($statement) {
				for ($i = 0; $i < $count; $i++) {
					$statement->bindValue($search_term_placeholders[$i], "%" . $search_terms[$i] . "%", PDO::PARAM_STR);
				}
			} else {
				return false;
			}
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}
		
		// Execute the statement.
		try {
			$statement->execute();
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}
		
		$row = $statement->fetch(PDO::FETCH_NUM);
		$result[0] = reset($row);
		unset($statement);
		
		// Retrieve the subset of objects actually required.
		if (!$limit) {
			global $tfish_preference;
			$limit = $tfish_preference->search_pagination;
		}
		$sql .= "LIMIT :limit ";
		if ($offset) {
			$sql .= "OFFSET :offset ";
		}
		$sql_search .= $sql;
		try {
			$statement = TfishDatabase::preparedStatement($sql_search);
			if ($statement) {
				for ($i = 0; $i < $count; $i++) {
					$statement->bindValue($search_term_placeholders[$i], "%" . $search_terms[$i] . "%", PDO::PARAM_STR);
					$statement->bindValue(":limit", (int)$limit, PDO::PARAM_INT);
					if ($offset) {
						$statement->bindValue(":offset", (int)$offset, PDO::PARAM_INT);
					}
				}
			} else {
				return false;
			}
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}

		// Execute the statement, fetch rows into the appropriate class type as determined by the
		// first column of the table.
		try {
			$statement->execute();
			$statement->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_CLASSTYPE|PDO::FETCH_PROPS_LATE);
			while ($object = $statement->fetch()) {
				$result[$object->id] = $object;
			}
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}
		return $result;
	}
	
	/**
	 * Convert a database content row to a corresponding content object.
	 * 
	 * @param type $row
	 * @return boolean
	 */
	public static function toObject($row)
	{
		if (empty($row) || !TfishFilter::isArray($row)) {
			trigger_error(TFISH_ERROR_NOT_ARRAY_OR_EMPTY);
		}
		
		// Check the content type is whitelisted.
		$type_whitelist = self::getTypes();
		if (!empty($row['type']) && array_key_exists($row['type'], $type_whitelist)) {
			$content_object = new $row['type'];
		} else {
			trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
		}
		
		// Populate the object from the $row using whitelisted properties.
		if ($content_object) {
			$content_object->loadProperties($row);
			
			// Populate the tag property.
			if (isset($content_object->tags) && !empty($content_object->id)) {
				$tags = array();
				$criteria = new TfishCriteria();
				$criteria->add(new TfishCriteriaItem('content_id', (int)$content_object->id ));
				$statement = TfishDatabase::select('taglink', $criteria, array('tag_id'));
				if ($statement) {
					try {
						while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
							$tags[] = $row['tag_id'];
						}
						$content_object->tags = $tags;
					} catch (PDOException $e) {
						TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
					}
				} else {
					trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
				}
			}
			
			return $content_object;
		}
		
		return false;
	}
	
	/**
	 * Updates a content object in the database.
	 * 
	 * @param object $obj
	 * @return boolean
	 */
	public static function update($obj)
	{
		if (TfishFilter::isInt($obj->id, 1)) {
			$clean_id = (int)$obj->id;
		}
		$key_values = $obj->toArray();
		unset($key_values['submission_time']); // Submission time should not be overwritten.
		$zeroed_properties = $obj->zeroedProperties();
		foreach ($zeroed_properties as $property) {
			$key_values[$property] = null;
		}
		// Tags are stored in a separate table and must be handled in a separate query.
		unset($key_values['tags']);

		// Update the content object.
		$result = TfishDatabase::update('content', $clean_id, $key_values);
		if (!$result) {
			trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
		}
		unset($result);
		
		// Update tags.
		$result = TfishTaglinkHandler::updateTaglinks($clean_id, $obj->type, $obj->tags);
		if (!$result) {
			trigger_error(TFISH_ERROR_TAGLINK_UPDATE_FAILED, E_USER_NOTICE);
			return false;
		}
		
		return true;		
	}
	
	/**
	 * Delete a single object from the content table.
	 * 
	 * @param int $id
	 * @return boolean
	 */
	public static function delete($id)
	{
		$clean_id = (int)$id;
		if (TfishFilter::isInt($clean_id, 1)) {
			$result = TfishDatabase::delete('content', $clean_id);
			if (!$result) {
				return false;
			}
		} else {
			trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
			return false;
		}
		
		// Delete associated taglinks.
		$result = TfishTaglinkHandler::deleteTaglinks($clean_id);
		if (!$result) {
			return false;
		}
		
		return true;		
	}
}