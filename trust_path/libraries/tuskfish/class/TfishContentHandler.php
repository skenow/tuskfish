<?php

/**
* Tuskfish parent content object handler class.
* 
* Provides base content handler methods that are inherited or overridden by subclass-specific
* content handlers. 
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishContentHandler
{
	function __construct() {}

	/**
	 * Check if an existing object has an associated image file upload.
	 * 
	 * @param int $id of content object
	 * @return string filename
	 */
	private static function _checkImage($id)
	{
		$clean_id = TfishFilter::isInt($id, 1) ? (int)$id : null;
		$filename = '';
		
		// Objects without an ID have not yet been inserted into the database.
		if (empty($clean_id)) {
			return false;
		}
		
		$criteria = new TfishCriteria;
		$criteria->add(new TfishCriteriaItem('id', $clean_id));
		
		$statement = TfishDatabase::select('content', $criteria, array('image'));
		if ($statement) {
			$row = $statement->fetch(PDO::FETCH_ASSOC);
			$filename = (isset($row['image']) && !empty($row['image'])) ? $row['image'] : false;
		} else {
			trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
		}
		
		return $filename;
	}
	
	/**
	 * Check if an existing object has an associated media file upload.
	 * 
	 * @param int $id of content object
	 * @return string filename
	 */
	private static function _checkMedia($id)
	{
		$clean_id = TfishFilter::isInt($id, 1) ? (int)$id : null;
		$filename = '';
		
		// Objects without an ID have not yet been inserted into the database.
		if (empty($clean_id)) {
			return false;
		}
		
		$criteria = new TfishCriteria;
		$criteria->add(new TfishCriteriaItem('id', $clean_id));

		$statement = TfishDatabase::select('content', $criteria, array('media'));
		if ($statement) {
			$row = $statement->fetch(PDO::FETCH_ASSOC);
			$filename = (isset($row['media']) && !empty($row['media'])) ? $row['media'] : false;
		} else {
			trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
		}
		
		return $filename;
	}
	
	/**
	 * Deletes an uploaded image file associated with a content object.
	 * 
	 * @param int $id
	 * @return boolean true on success, false on failure
	 */
	private static function _deleteImage($filename)
	{
		if ($filename) {
			return TfishFileHandler::deleteFile('image/' . $filename);
		}
	}
	
	/**
	 * Deletes an uploaded media file associated with a content object.
	 * 
	 * @param int $id of content object
	 * @return boolean true on success, false on failure
	 */
	private static function _deleteMedia($filename)
	{
		if ($filename) {
			return TfishFileHandler::deleteFile('media/' . $filename);
		}
	}
	
	
	/**
	 * Uploads an image file into the uploads/image directory.
	 * 
	 * @return void
	 */
	private static function _uploadImage()
	{
		if (array_key_exists('image', $property_whitelist) && !empty($_FILES['image']['name'])) {
			$filename = TfishFilter::trimString($_FILES['image']['name']);
			$clean_filename = TfishFileHandler::uploadFile($filename, 'image');
			if ($clean_filename) {
				$this->__set('image', $clean_filename);
			}
		}
	}
	
	/**
	 * Uploads a media file into the uploads/media directory.
	 * 
	 * @return void
	 */
	private static function _uploadMedia()
	{
		if (array_key_exists('media', $property_whitelist) && !empty($_FILES['media']['name'])) {
			$filename = TfishFilter::trimString($_FILES['media']['name']);
			$clean_filename = TfishFileHandler::uploadFile($filename, 'media');
			if ($clean_filename) {
				$this->__set('media', $clean_filename);
				$this->__set('format', pathinfo($clean_filename, PATHINFO_EXTENSION));
				$this->__set('file_size', $_FILES['media']['size']);
			}
		}
	}
	
	/**
	 * Retrieves a single content object based on its ID.
	 * 
	 * @param int $id of content object
	 * @return object|boolean $object on success, false on failure
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
	
	/**
	 * Returns a list of content object titles with ID as key.
	 * 
	 * @param TfishCriteria $criteria
	 * @return array as id => title of content objects
	 */
	public static function getList($criteria = false)
	{
		$content_list = array();
		$columns = array('id', 'title');
		
		// Set default sorting order by submission time descending.
		if (!$criteria) {
			$criteria = new TfishCriteria;
		}
		if (!$criteria->order) {
			$criteria->order = 'submission_time';
		}
		
		$statement = TfishDatabase::select('content', $criteria, $columns);
		if ($statement) {
			try {
				while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
					$content_list[$row['id']] = $row['title'];
				}
			} catch (PDOException $e) {
				TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
			}
			unset($statement);
		} else {
			trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
		}
		
		return $content_list;		
	}
	
	/**
	 * Get content objects, optionally matching conditions specified with a TfishCriteria object.
	 * 
	 * @param object $criteria TfishCriteria
	 * @return array of content objects
	 */
	public static function getObjects($criteria = false)
	{
		$objects = array();
		
		// Set default sorting order by submission time descending.
		if (!$criteria) {
			$criteria = new TfishCriteria;
		}
		if (!$criteria->order) {
			$criteria->order = 'submission_time';
		}
		
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
	
	/**
	 * Counts the number of content objects matching the criteria.
	 * 
	 * Main use is for constructing the pagination control. Note that the limit property will
	 * be ignored even if it is set.
	 * 
	 * @param object $criteria TfishCriteria
	 * @return int $count
	 */
	public static function getCount($criteria = false)
	{
		if ($criteria && !empty($criteria->limit)) {
			$limit = $criteria->limit;
			$criteria->limit = 0;
		}
		$count = TfishDatabase::selectCount('content', $criteria);
		if (isset($limit)) {
			$criteria->limit = (int)$limit;
		}

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
	 * @return array of language codes
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
	 * @return array of copyright licenses
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
	 * Returns a list of collection objects for the data entry/edit forms.
	 * 
	 * @todo move this function over to use TfishAngryTree for more robust tree handling
	 * @return array of collection objects
	 */
	public static function getParents()
	{
		$parents = array();
		$criteria = new TfishCriteria();
		$criteria->add(new TfishCriteriaItem('type', 'TfishCollection'));
		$parents = TfishContentHandler::getList($criteria);
		
		return $parents;
	}
	
	/**
	 * Returns a whitelist of permitted content object types.
	 * 
	 * Use this whitelist when dynamically instantiating content objects. If you create additional
	 * types of content object (which must be descendants of the TfishContentObject class) you
	 * must add them to the whitelist below. Otherwise their use will be denied in many parts of
	 * the Tuskfish system.
	 * 
	 * @return array whitelist of permitted content object types
	 */
	public static function getTypes()
	{
		return array(
			'TfishArticle' => TFISH_TYPE_ARTICLE,
			'TfishAudio' => TFISH_TYPE_AUDIO,
			'TfishCollection' => TFISH_TYPE_COLLECTION,
			'TfishDownload' => TFISH_TYPE_DOWNLOAD,
			'TfishImage' => TFISH_TYPE_IMAGE,
			'TfishStatic' => TFISH_TYPE_STATIC,
			'TfishTag' => TFISH_TYPE_TAG,
			'TfishVideo' => TFISH_TYPE_VIDEO,
		);
	}
	
	/**
	 * Checks if a class name is a sanctioned subclass of content object.
	 * 
	 * @param string $type
	 * @return boolean true if sanctioned otherwise false
	 */
	public static function isSanctionedType($type)
	{
		$type = TfishFilter::trimString($type);
		$sanctioned_types = self::getTypes();
		if (array_key_exists($type, $sanctioned_types)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Get a list of tags actually in use by other content objects, optionally filtered by content type.
	 * 
	 * Used primarily to build select box controls.
	 * 
	 * @param string $type
	 * 
	 * @return array|boolean list of tags if available, false if empty.
	 */
	public static function getActiveTagList($type = null)
	{
		$tags = $distinct_tags = array();
		
		$tags = self::getTagList();
		if (empty($tags)) {
			return false;
		}
		
		// Restrict tag list to those actually in use.
		$clean_type = (isset($type) && self::isSanctionedType($type)) ? TfishFilter::trimString($type) : null;
		
		if (isset($clean_type)) {
			$criteria = new TfishCriteria();
			$criteria->add(new TfishCriteriaItem('content_type', $clean_type));
		} else {
			$criteria = false;
		}
		
		$statement = TfishDatabase::selectDistinct('taglink', $criteria, array('tag_id'));
		if ($statement) {
			try {
				while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
					$distinct_tags[$row['tag_id']] = $tags[$row['tag_id']];
				}
			} catch (PDOException $e) {
				TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
			}
		}

		return $distinct_tags;
	}
	
	
	/**
	 * Generates an online/offline select box.
	 * 
	 * @param string $action destination page on submission of the form
	 * @param int $selected preselected option
	 * @return string select box
	 */
	public static function getOnlineSelectBox($selected = null, $action = false, $zero_option = TFISH_ONLINE_STATUS)
	{
		$clean_action = TfishFilter::isAlnumUnderscore($action) ? TfishFilter::escape(TfishFilter::trimString($action)) . '.php' : ''; // Name of script to load on submission. Could be user side or admin side.
		$clean_selected = (isset($selected) && TfishFilter::isInt($selected, 0, 1)) ? (int)$selected : null; // Offline (0) or online (1)
		$clean_zero_option = TfishFilter::escape(TfishFilter::trimString($zero_option)); // The text to display in the zero option of the select box.
		
		$options = array(2 => TFISH_SELECT_STATUS, 1 => TFISH_ONLINE, 0 => TFISH_OFFLINE);
		$select_box = !empty($clean_action) ? '<form name="online_select_form" action="' . $clean_action . '" method="get">' : '';
		$select_box .= '<select name="online" id="online" onchange="this.form.submit()">';
		if (isset($clean_selected)) {
			foreach($options as $key => $value) {
				$select_box .= ($key == $clean_selected) ? '<option value="' . $key . '" selected>' . $value . '</option>' : '<option value="' . $key . '">' . $value . '</option>';
			}
		} else { // Nothing selected
			$select_box .= '<option value="2" selected>' . TFISH_SELECT_STATUS . '</option>';
			$select_box .= '<option value="1">' . TFISH_ONLINE . '</option>';
			$select_box .= '<option value="0">' . TFISH_OFFLINE . '</option>';
		}
		
		$select_box .= '</select>';
		$select_box .= !empty($clean_action) ? '</form>' : '';

		return $select_box;
	}
	
	/**
	 * Get an array of all tag objects in $id => $title format.
	 * 
	 * @return array of tag objects
	 */
	public static function getTagList()
	{
		$tags = array();
		$statement = false;

		$columns = array('id', 'title');
		$criteria = new TfishCriteria();
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
			unset($statement);
		} else {
			trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
		}

		return $tags;
	}
	
	/**
	 * Get an array of all tag objects.
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
	
	/**
	 * Get a content type select box.
	 * 
	 * @param int $selected preselected option
	 * @param type $action destination page on form submission
	 * @param type $zero_option the default text to show at top of select box
	 * @return string select box
	 */
	public static function getTypeSelectBox($selected = null, $action = false, $zero_option = TFISH_TYPE)
	{
		$clean_action = TfishFilter::isAlnumUnderscore($action) ? TfishFilter::escape(TfishFilter::trimString($action)) . '.php' : ''; // Name of script to load on submission. Could be user side or admin side.
		$clean_zero_option = TfishFilter::escape(TfishFilter::trimString($zero_option)); // The text to display in the zero option of the select box.
		$clean_selected = '';
		$type_list = self::getTypes();
		
		if (isset($selected) && TfishFilter::isAlnumUnderscore($selected)) {
			if (array_key_exists($selected, $type_list)) {
				$clean_selected = TfishFilter::trimString($selected);
			}
		}
		
		$options = array(0 => TFISH_SELECT_TYPE) + $type_list;
		$select_box = !empty($clean_action) ? '<form name="type_select_form" action="' . $clean_action . '" method="get">' : '';
		$select_box .= '<select name="type" id="type" onchange="this.form.submit()">';
		foreach($options as $key => $value) {
			$select_box .= ($key == $selected) 
					? '<option value="' . TfishFilter::escape($key) . '" selected>' . TfishFilter::escape($value) . '</option>'
					: '<option value="' . TfishFilter::escape($key) . '">' . TfishFilter::escape($value) . '</option>';
		}
		$select_box .= '</select>';
		$select_box .= !empty($clean_action) ? '</form>' : '';
		
		return $select_box;
	}
	
	/**
	 * Toggle the online status of a content object.
	 * 
	 * @param int $id of content object
	 * @return boolean true on success, false on failure
	 */
	public static function toggleOnlineStatus($id)
	{
		$clean_id = (int)$id;
		return TfishDatabase::toggleBoolean($clean_id, 'content', 'online');
	}
	
	/**
	 * Increment a content object's counter field by one.
	 * 
	 * @param int $id of content object
	 */
	public static function updateCounter($id)
	{
		$clean_id = (int)$id;
		return TfishDatabase::updateCounter($clean_id, 'content', 'counter');
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
		
		// Process image and media files before inserting the object, as related fields must be set.
		$property_whitelist = $obj->getPropertyWhitelist();
		if (array_key_exists('image', $property_whitelist) && !empty($_FILES['image']['name'])) {
			$filename = TfishFilter::trimString($_FILES['image']['name']);
			$clean_filename = TfishFileHandler::uploadFile($filename, 'image');
			if ($clean_filename) {
				$key_values['image'] = $clean_filename;
			}
		}
	
		if (array_key_exists('media', $property_whitelist) && !empty($_FILES['media']['name'])) {
			$filename = TfishFilter::trimString($_FILES['media']['name']);
			$clean_filename = TfishFileHandler::uploadFile($filename, 'media');
			if ($clean_filename) {
				$key_values['media'] = $clean_filename;
				$key_values['format'] = pathinfo($clean_filename, PATHINFO_EXTENSION);
				$key_values['file_size'] = $_FILES['media']['size'];
			}
		}
		
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
	 * Converts an array of tag_ids into an array of tag links with an arbitrary local target file.
	 * 
	 * Note that the filename may only consist of alphanumeric characters and underscores. Do not
	 * include the file extension (eg. use 'article' instead of 'article.php'. The base URL of the
	 * site will be prepended and .php plus the tag_id will be appended.
	 * 
	 * @param arrray $tags
	 * @param string $target_url
	 * 
	 * @return array of tag links
	 */
	public static function makeTagLinks($tags, $target_filename = false)
	{
		if (!TfishFilter::isArray($tags)) {
			trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
		}
		
		if (empty($target_filename)) {
			$clean_filename = TFISH_URL . '?tag_id=';
		} else {
			if (!TfishFilter::isAlnumUnderscore($target_filename)) {
				trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
			} else {
				$target_filename = TfishFilter::trimString($target_filename);
				$clean_filename = TFISH_URL . TfishFilter::escape($target_filename) . '.php?tag_id=';
			}
		}
		
		$tag_list = self::getTagList();
		$tag_links = array();
		foreach ($tags as $tag) {
			if (TfishFilter::isInt($tag, 1) && array_key_exists($tag, $tag_list)) {
				$tag_links[$tag] = '<a href="' . TfishFilter::escape($clean_filename . $tag) . '">' . TfishFilter::escape($tag_list[$tag]) . '</a>';
			}
			unset($tag);
		}			

		return $tag_links;
	}
	
	/**
	 * Provides global search functionality for content objects.
	 * 
	 * Search terms are passed through to the database query without modification. Escaping is
	 * handled through use of a PDO prepared statement with named placeholders; search terms are
	 * inserted indirectly by binding them to the placeholders. Search terms must NEVER be inserted
	 * into a query directly, otherwise you may as well do us all a favour and go shoot yourself now.
	 *
	 * @param array $queryarray search terms
	 * @param string $andor operator to chain search terms
	 * @param int $limit maximum number of results to retrieve (pagination)
	 * @param int $offset starting point for retrieving results (pagination)
	 * @return array|boolean array of content objects on success, false failure
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
		if (!empty($clean_search_terms)) {
			$results = self::_searchContent($clean_search_terms, $clean_andor, $clean_limit, $clean_offset);
		} else {
			$results = false;
		}
		
		return $results;
	}
	
	private static function _searchContent($search_terms, $andor, $limit, $offset)
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
		$sql .= " AND `online` = 1 ORDER BY `date` DESC ";
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
	 * Only use this function to convert single objects, as it does a seperate query to look up
	 * the associated taglinks. Running it through a loop will therefore consume a lot of resources.
	 * To convert multiple objects, load them directly into the relevant class files using
	 * PDO::FETCH_CLASS, prepare a buffer of tags using getTags() and loop through the objects
	 * referring to the buffer rather than hitting the database every time.
	 * 
	 * @param array $row
	 * @return object|boolean content object on success, false on failure
	 */
	public static function toObject($row)
	{
		if (empty($row) || !TfishFilter::isArray($row)) {
			return false;
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
			$content_object->loadProperties($row, true);
			
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
	 * @return boolean true on success, false on failure
	 */
	public static function update($obj)
	{
		$clean_id = TfishFilter::isInt($obj->id, 1) ? (int)$obj->id : 0;
		$key_values = $obj->toArray();
		unset($key_values['submission_time']); // Submission time should not be overwritten.
		$zeroed_properties = $obj->zeroedProperties();
		foreach ($zeroed_properties as $property) {
			$key_values[$property] = null;
		}
		$property_whitelist = $obj->getPropertyWhitelist();
		
		// Tags are stored in a separate table and must be handled in a separate query.
		unset($key_values['tags']);

		/**
		 * Handle image / media files for existing objects.
		 */
		if (!empty($clean_id)) {

			/**
			 * Image property.
			 */
			
			// Check if there is an existing image associated with this object.
			$existing_image = self::_checkImage($clean_id);
			
			// Is this object allowed to have an image property?
			if (array_key_exists('image', $property_whitelist)) {
				
				// Check if a new image file has been uploaded by looking in $_FILES.
				if (!empty($_FILES['image']['name']))  {
					$filename = TfishFilter::trimString($_FILES['image']['name']);
					$clean_filename = TfishFileHandler::uploadFile($filename, 'image');
					if ($clean_filename) {
						$key_values['image'] = $clean_filename;
					}					
				} else { // No new image, use the existing file name.
					$key_values['image'] = $existing_image;
				}
			} else {
				$key_values['image'] = '';
			}
			
			// If the updated object has no image attached, or has been instructed to delete attached image, delete any old image files.
			if ((!isset($key_values['image']) || empty($key_values['image']))
					|| (isset($_POST['deleteImage']) && !empty($_POST['deleteImage']))
					&& $existing_image) {
				$key_values['image'] = '';
				self::_deleteImage($existing_image);
			}
			
			/**
			 * Media property.
			 */
			
			// Check if there is an existing media file associated with this object.
			$existing_media = self::_checkMedia($clean_id);
			
			// Is this object allowed to have an media property?
			if (array_key_exists('media', $property_whitelist)) {
				
				// Check if a new media file has been uploaded by looking in $_FILES.
				if (!empty($_FILES['media']['name']))  {
					$filename = TfishFilter::trimString($_FILES['media']['name']);
					$clean_filename = TfishFileHandler::uploadFile($filename, 'media');
					if ($clean_filename) {
						$key_values['media'] = $clean_filename;
						$key_values['format'] = pathinfo($clean_filename, PATHINFO_EXTENSION);
						$key_values['file_size'] = $_FILES['media']['size'];
					}
				} else { // No new media, use the existing file name.
					$key_values['media'] = $existing_media;
				}
			} else {
				$key_values['media'] = '';
				$key_values['format'] = '';
				$key_values['file_size'] = '';
			}
			
			// If the updated object has no media attached, delete any old media files.
			if ((!isset($key_values['media']) || empty($key_values['media']))
					|| (isset($_POST['deleteMedia']) && !empty($_POST['deleteMedia']))
					&& $existing_media) {
				$key_values['media'] = '';
				$key_values['format'] = '';
				$key_values['file_size'] = '';
				self::_deleteMedia($existing_media);
			}
		}
		
		// Update tags.
		$result = TfishTaglinkHandler::updateTaglinks($clean_id, $obj->type, $obj->tags);
		if (!$result) {
			trigger_error(TFISH_ERROR_TAGLINK_UPDATE_FAILED, E_USER_NOTICE);
			return false;
		}
		
		// Update the content object.
		$result = TfishDatabase::update('content', $clean_id, $key_values);
		if (!$result) {
			trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
		}
		unset($result);
		
		return true;		
	}
	
	/**
	 * Delete a single object from the content table.
	 * 
	 * @param int $id of content object to delete
	 * @return boolean true on success, false on failure
	 */
	public static function delete($id)
	{
		$clean_id = (int)$id;
		if (!TfishFilter::isInt($clean_id, 1)) {
			trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
			return false;
		}
		
		// Delete associated files.
		$obj = self::getObject($id);
		if (TfishFilter::isObject($obj)) {
			if ($obj->image) {
				self::_deleteImage($obj->image);
			}
			if ($obj->media) {
				self::_deleteMedia($obj->media);
			}
		}
		
		// Delete associated taglinks.
		$result = TfishTaglinkHandler::deleteTaglinks($clean_id);
		if (!$result) {
			return false;
		}
		
		// Delete the object.
		$result = TfishDatabase::delete('content', $clean_id);
		if (!$result) {
			return false;
		}
		
		return true;		
	}
}