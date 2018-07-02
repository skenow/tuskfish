<?php

/**
 * TfishContentHandler class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Base class for content handler objects.
 * 
 * Provides base content handler methods that are inherited or overridden by subclass-specific
 * content handlers. You can use it as a generic handler when you want to retrieve mixed content
 * types. If you want to retrieve a specific content type it would be better to use the specific
 * content handler for that type, as it may contain additional functionality for processing or
 * displaying it.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */
class TfishContentHandler
{
    protected $tfish_database;
    
    function __construct(TfishDatabase $tfish_database)
    {
        $this->tfish_database = $tfish_database;
    }
    /**
     * Delete a single object from the content table.
     * 
     * @param int $id ID of content object to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id)
    {
        $clean_id = (int) $id;
        
        if (!TfishDataValidator::isInt($clean_id, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            return false;
        }

        // Delete associated files.
        $obj = $this->getObject($clean_id);
        
        if (!TfishDataValidator::isObject($obj)) {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
            return false;
        }
        
        if (!empty($obj->image)) {
            $this->_deleteImage($obj->image);
        }

        if (!empty($obj->media)) {
            $this->_deleteMedia($obj->media);
        }

        // Delete associated taglinks. If this object is a tag, delete taglinks referring to it.
        $taglink_handler = new TfishTaglinkHandler();
        $result = $taglink_handler->deleteTaglinks($obj);
        
        if (!$result) {
            return false;
        }

        // If object is a collection delete related parent references in child objects.
        if ($obj->type === 'TfishCollection') {
            $criteria = new TfishCriteria($this->tfish_database);
            $criteria->add(new TfishCriteriaItem('parent', $clean_id));
            $result = $this->tfish_database->updateAll('content', array('parent' => 0), $criteria);
            
            if (!$result) {
                return false;
            }
        }

        // Delete the object.
        $result = $this->tfish_database->delete('content', $clean_id);
        
        if (!$result) {
            return false;
        }

        return true;
    }
    
    /**
     * Removes references to a collection when it is deleted or changed to another type.
     * 
     * @param int $id ID of the parent collection.
     * @return boolean True on success, false on failure.
     */
    public function deleteParentalReferences(int $id)
    {
        $clean_id = TfishDataValidator::isInt($id, 1) ? (int) $id : null;
        
        if (!$clean_id) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        $criteria = new TfishCriteria($this->tfish_database);
        $criteria->add(new TfishCriteriaItem('parent', $clean_id));
        $result = $this->tfish_database->updateAll('content', array('parent' => 0), $criteria);

        if (!$result) {
            return false;
        }
        
        return true;
    }

    /**
     * Deletes an uploaded image file associated with a content object.
     * 
     * @param string $filename Name of file.
     * @return bool True on success, false on failure.
     */
    private function _deleteImage(string $filename)
    {
        if ($filename) {
            return TfishFileHandler::deleteFile('image/' . $filename);
        }
    }

    /**
     * Deletes an uploaded media file associated with a content object.
     * 
     * @param string $filename Name of file.
     * @return bool True on success, false on failure.
     */
    private function _deleteMedia(string $filename)
    {
        if ($filename) {
            return TfishFileHandler::deleteFile('media/' . $filename);
        }
    }

    /**
     * Inserts a content object into the database.
     * 
     * Note that content child content classes that have unset unused properties from the parent
     * should reset them to null before insertion or update. This is to guard against the case
     * where the admin reassigns the type of a content object - it makes sure that unused properties
     * are zeroed in the database. 
     * 
     * @param object $obj TfishContentObject subclass.
     * @return bool True on success, false on failure.
     */
    public function insert(TfishContentObject $obj)
    {   
        $key_values = $obj->toArray();
        $key_values['submission_time'] = time(); // Automatically set submission time.
        unset($key_values['id']); // ID is auto-incremented by the database on insert operations.
        unset($key_values['tags']);

        // Process image and media files before inserting the object, as related fields must be set.
        $property_whitelist = $obj->getPropertyWhitelist();
        
        if (array_key_exists('image', $property_whitelist) && !empty($_FILES['image']['name'])) {
            $filename = TfishDataValidator::trimString($_FILES['image']['name']);
            $clean_filename = TfishFileHandler::uploadFile($filename, 'image');
            
            if ($clean_filename) {
                $key_values['image'] = $clean_filename;
            }
        }

        if (array_key_exists('media', $property_whitelist) && !empty($_FILES['media']['name'])) {
            $filename = TfishDataValidator::trimString($_FILES['media']['name']);
            $clean_filename = TfishFileHandler::uploadFile($filename, 'media');
            
            if ($clean_filename) {
                $key_values['media'] = $clean_filename;
                $mimetype_whitelist = TfishFileHandler::getPermittedUploadMimetypes();
                $extension = pathinfo($clean_filename, PATHINFO_EXTENSION);
                $key_values['format'] = $mimetype_whitelist[$extension];
                $key_values['file_size'] = $_FILES['media']['size'];
            }
        }

        // Insert the object into the database.
        $result = $this->tfish_database->insert('content', $key_values);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
            return false;
        } else {
            $content_id = $this->tfish_database->lastInsertId();
        }
        
        unset($key_values, $result);

        // Tags are stored separately in the taglinks table. Tags are assembled in one batch before
        // proceeding to insertion; so if one fails a range check all should fail.
        if (isset($obj->tags) and TfishDataValidator::isArray($obj->tags)) {
            // If the lastInsertId could not be retrieved, then halt execution becuase this data
            // is necessary in order to correctly assign taglinks to content objects.
            if (!$content_id) {
                trigger_error(TFISH_ERROR_NO_LAST_INSERT_ID, E_USER_ERROR);
                exit;
            }

            $taglink_handler = new TfishTaglinkHandler();
            $result = $taglink_handler->insertTaglinks($content_id, $obj->type, $obj->tags);
            
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if a class name is a sanctioned subclass of TfishContentObject.
     * 
     * Basically this just checks if the class name is whitelisted.
     * 
     * @param string $type Type of content object.
     * @return bool True if sanctioned type otherwise false.
     */
    public function isSanctionedType(string $type)
    {
        $type = TfishDataValidator::trimString($type);
        $sanctioned_types = $this->getTypes();
        
        if (array_key_exists($type, $sanctioned_types)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get a list of tags actually in use by other content objects, optionally filtered by type.
     * 
     * Used primarily to build select box controls. Use $online_only to select only those tags that
     * are marked as online (true), or all tags (false).
     * 
     * @param string $type Type of content object.
     * @param bool $online_only True if marked as online, false if marked as offline.
     * @return array|bool List of tags if available, false if empty.
     */
    public function getActiveTagList(string $type = null, bool $online_only = true)
    {
        $tags = $distinct_tags = array();

        $clean_online_only = TfishDataValidator::isBool($online_only) ? (bool) $online_only : true;
        $tags = $this->getTagList($clean_online_only);
        
        if (empty($tags)) {
            return false;
        }

        // Restrict tag list to those actually in use.
        $clean_type = (isset($type) && $this->isSanctionedType($type))
                ? TfishDataValidator::trimString($type) : null;

        $criteria = new TfishCriteria($this->tfish_database);

        // Filter tags by type.
        if (isset($clean_type)) {
            $criteria->add(new TfishCriteriaItem('content_type', $clean_type));
        }

        // Put a check for online status in here.
        $statement = $this->tfish_database->selectDistinct('taglink', $criteria, array('tag_id'));
        
        if ($statement) {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                if (isset($tags[$row['tag_id']])) {
                    $distinct_tags[$row['tag_id']] = $tags[$row['tag_id']];
                }
            }
        }

        // Sort the tags into alphabetical order.
        asort($distinct_tags);

        return $distinct_tags;
    }

    /**
     * Count content objects optionally matching conditions specified with a TfishCriteria object.
     * 
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return int $count Number of objects matching conditions.
     */
    public function getCount(TfishCriteria $criteria = null)
    {
        if (!isset($criteria)) {
            $criteria = new TfishCriteria($this->tfish_database);
        }
        
        if ($criteria && !empty($criteria->limit)) {
            $limit = $criteria->limit;
            $criteria->limit = 0;
        }
        
        $count = $this->tfish_database->selectCount('content', $criteria);
        
        if (isset($limit)) {
            $criteria->limit = (int) $limit;
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
     * @return array Array of languages in ISO 639-1 code => name format.
     */
    public function getLanguages()
    {
        return array(
            "en" => "English",
            "th" => "Thai",
        );
    }

    /**
     * Returns a list of content object titles with ID as key.
     * 
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return array Array as id => title of content objects.
     */
    public function getList(TfishCriteria $criteria = null)
    {
        $content_list = array();
        $columns = array('id', 'title');

        if (!isset($criteria)) {
            $criteria = new TfishCriteria($this->tfish_database);
        }
        
        // Set default sorting order by submission time descending.
        if (!$criteria->order) {
            $criteria->order = 'date';
        }

        $statement = $this->tfish_database->select('content', $criteria, $columns);
        
        if ($statement) {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $content_list[$row['id']] = $row['title'];
            }
            unset($statement);
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }

        return $content_list;
    }

    /**
     * Retrieves a single content object based on its ID.
     * 
     * @param int $id ID of content object.
     * @return object|bool $object Object on success, false on failure.
     */
    public function getObject(int $id)
    {
        $clean_id = (int) $id;
        $row = $object = '';
        
        if (TfishDataValidator::isInt($id, 1)) {
            $criteria = new TfishCriteria($this->tfish_database);
            $criteria->add(new TfishCriteriaItem('id', $clean_id));
            $statement = $this->tfish_database->select('content', $criteria);
            
            if ($statement) {
                $row = $statement->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($row) {
                $object = $this->toObject($row);
                return $object;
            }
        }
        
        return false;
    }

    /**
     * Get content objects, optionally matching conditions specified with a TfishCriteria object.
     * 
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return array Array of content objects.
     */
    public function getObjects(TfishCriteria $criteria = null)
    {
        $objects = array();
        
        if (!isset($criteria)) {
            $criteria = new TfishCriteria($this->tfish_database);
        }

        // Set default sorting order by submission time descending.        
        if (!$criteria->order) {
            $criteria->order = 'date';
        }

        $statement = $this->tfish_database->select('content', $criteria);
        
        if ($statement) {
            // Fetch rows into the appropriate class type, as determined by the first column.
            $statement->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_CLASSTYPE | PDO::FETCH_PROPS_LATE);
            
            while ($object = $statement->fetch()) {
                $objects[$object->id] = $object;
            }
            
            unset($statement);
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }

        // Get the tags for these objects.
        if (!empty($objects)) {
            $taglinks = array();
            $object_ids = array_keys($objects);

            $criteria = new TfishCriteria($this->tfish_database);
            
            foreach ($object_ids as $id) {
                $criteria->add(new TfishCriteriaItem('content_id', (int) $id), "OR");
                unset($id);
            }

            $statement = $this->tfish_database->select('taglink', $criteria);
            
            if ($statement) {
                // Sort tag into multi-dimensional array indexed by content_id.
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $taglinks[$row['content_id']][] = $row['tag_id'];
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
     * Generates an online/offline select box.
     * 
     * @param int $selected The currently option.
     * @param string $zero_option The text to display in the zero option of the select box.
     * @return string HTML select box.
     */
    public function getOnlineSelectBox(int $selected = null,
            string $zero_option = TFISH_ONLINE_STATUS)
    {
        $clean_selected = (isset($selected) && TfishDataValidator::isInt($selected, 0, 1)) 
                ? (int) $selected : null; // Offline (0) or online (1)
        $clean_zero_option = TfishDataValidator::escape(TfishDataValidator::trimString($zero_option));
        $options = array(2 => TFISH_SELECT_STATUS, 1 => TFISH_ONLINE, 0 => TFISH_OFFLINE);
        $select_box = '<select class="form-control custom-select" name="online" id="online" '
                . 'onchange="this.form.submit()">';
        
        if (isset($clean_selected)) {
            foreach ($options as $key => $value) {
                $select_box .= ($key === $clean_selected) ? '<option value="' . $key . '" selected>' 
                        . $value . '</option>' : '<option value="' . $key . '">' . $value 
                        . '</option>';
            }
        } else { // Nothing selected
            $select_box .= '<option value="2" selected>' . TFISH_SELECT_STATUS . '</option>';
            $select_box .= '<option value="1">' . TFISH_ONLINE . '</option>';
            $select_box .= '<option value="0">' . TFISH_OFFLINE . '</option>';
        }

        $select_box .= '</select>';

        return $select_box;
    }

    /**
     * Returns a list of intellectual property rights licenses for the content submission form.
     * 
     * In the interests of brevity and sanity, a comprehensive list is not provided. Add entries
     * that you want to use to the array below. Be aware that deleting entries that are in use by
     * your content objects will cause errors.
     * 
     * @return array Array of copyright licenses.
     */
    public function getRights()
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
     * Get an array of all tag objects in $id => $title format.
     * 
     * @param bool Get tags marked online only.
     * @return array Array of tag IDs and titles.
     */
    public function getTagList(bool $online_only = true)
    {
        $tags = array();
        $statement = false;
        $clean_online_only = TfishDataValidator::isBool($online_only) ? (bool) $online_only : true;
        $columns = array('id', 'title');
        $criteria = new TfishCriteria($this->tfish_database);
        $criteria->add(new TfishCriteriaItem('type', 'TfishTag'));
        
        if ($clean_online_only) {
            $criteria->add(new TfishCriteriaItem('online', true));
        }

        $statement = $this->tfish_database->select('content', $criteria, $columns);
        
        if ($statement) {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $tags[$row['id']] = $row['title'];
            }
            unset($statement);
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }
        
        asort($tags);

        return $tags;
    }

    /**
     * Get an array of all tag objects.
     * 
     * Use this function when you need to build a buffer of tags to reduce database queries, for
     * example when looping through a result set.
     * 
     * @return array Array of TfishTag objects.
     */
    public function getTags()
    {
        $tags = array();
        $criteria = new TfishCriteria($this->tfish_database);
        $criteria->add(new TfishCriteriaItem('type', 'TfishTag'));
        $tags = $this->getObjects($criteria);
        
        return $tags;
    }
    
    /**
     * Search the filtering criteria ($criteria->items) to see if object type has been set and
     * return the key.
     * 
     * @param array $criteria_items Array of TfishCriteriaItem objects.
     * @return int|null Key of relevant TfishCriteriaItem or null.
     */
    protected function getTypeIndex(array $criteria_items)
    {
        foreach ($criteria_items as $key => $item) {
            if ($item->column === 'type') {
                return $key;
            }
        }
        
        return null;
    }

    /**
     * Returns a whitelist of permitted content object types, ie. descendants of TfishContentObject.
     * 
     * Use this whitelist when dynamically instantiating content objects. If you create additional
     * types of content object (which must be descendants of the TfishContentObject class) you
     * must add them to the whitelist below. Otherwise their use will be denied in many parts of
     * the Tuskfish system.
     * 
     * @return array Array of whitelisted (permitted) content object types.
     */
    public function getTypes()
    {
        return array(
            'TfishArticle' => TFISH_TYPE_ARTICLE,
            'TfishAudio' => TFISH_TYPE_AUDIO,
            'TfishBlock' => TFISH_TYPE_BLOCK,
            'TfishCollection' => TFISH_TYPE_COLLECTION,
            'TfishDownload' => TFISH_TYPE_DOWNLOAD,
            'TfishImage' => TFISH_TYPE_IMAGE,
            'TfishStatic' => TFISH_TYPE_STATIC,
            'TfishTag' => TFISH_TYPE_TAG,
            'TfishVideo' => TFISH_TYPE_VIDEO,
        );
    }

    /**
     * Get a content type select box.
     * 
     * @param string $selected Currently selected option.
     * @param string $zero_option The default text to show at top of select box.
     * @return string HTML select box.
     */
    public function getTypeSelectBox(string $selected = '', string $zero_option = null)
    {
        // The text to display in the zero option of the select box.
        if (isset($zero_option)) {
            $clean_zero_option = TfishDataValidator::escape(TfishDataValidator::trimString($zero_option));
        } else {
            $clean_zero_option = TFISH_TYPE;
        }
        
        $clean_selected = '';
        $type_list = $this->getTypes();

        if ($selected && TfishDataValidator::isAlnumUnderscore($selected)) {
            if (array_key_exists($selected, $type_list)) {
                $clean_selected = TfishDataValidator::trimString($selected);
            }
        }

        $options = array(0 => TFISH_SELECT_TYPE) + $type_list;
        $select_box = '<select class="form-control custom-select" name="type" id="type" '
                . 'onchange="this.form.submit()">';
        
        foreach ($options as $key => $value) {
            $select_box .= ($key === $clean_selected) ? '<option value="' . TfishDataValidator::escape($key)
                    . '" selected>' . TfishDataValidator::escape($value) . '</option>' : '<option value="'
                . TfishDataValidator::escape($key) . '">' . TfishDataValidator::escape($value) . '</option>';
        }
        
        $select_box .= '</select>';

        return $select_box;
    }

    /**
     * Converts an array of tag_ids into an array of tag links with an arbitrary local target file.
     * 
     * Note that the filename may only consist of alphanumeric characters and underscores. Do not
     * include the file extension (eg. use 'article' instead of 'article.php'. The base URL of the
     * site will be prepended and .php plus the tag_id will be appended.
     * 
     * @param array $tags Array of tag IDs.
     * @param string $target_filename Name of file for tag links to point at.
     * @return array Array of HTML tag links.
     */
    public function makeTagLinks(array $tags, string $target_filename = '')
    {
        if (empty($target_filename)) {
            $clean_filename = TFISH_URL . '?tag_id=';
        } else {
            if (!TfishDataValidator::isAlnumUnderscore($target_filename)) {
                trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
            } else {
                $target_filename = TfishDataValidator::trimString($target_filename);
                $clean_filename = TFISH_URL . TfishDataValidator::escape($target_filename)
                        . '.php?tag_id=';
            }
        }

        $tag_list = $this->getTagList(false);
        $tag_links = array();
        
        foreach ($tags as $tag) {
            if (TfishDataValidator::isInt($tag, 1) && array_key_exists($tag, $tag_list)) {
                $tag_links[$tag] = '<a href="' . TfishDataValidator::escape($clean_filename . $tag) . '">'
                        . TfishDataValidator::escape($tag_list[$tag]) . '</a>';
            }
            
            unset($tag);
        }

        return $tag_links;
    }

    /**
     * Provides global search functionality for content objects.
     * 
     * Escaping of search terms is handled through use of a PDO prepared statement with named 
     * placeholders; search terms are inserted indirectly by binding them to the placeholders.
     * Search terms must NEVER be inserted into a query directly (creates an SQL injection
     * vulnerability), otherwise do us all a favour and go shoot yourself now.
     * 
     * Search terms have entity encoding (htmlspecialchars) applied on the teaser and description
     * fields (only) to ensure consistency with the entity encoding treatment that these HTML fields
     * have been subjected to, otherwise searches involving entities will not return results.
     *
     * @param object $tfish_preference TfishPreference object, to make site preferences available.
     * @param string $search_terms Search terms.
     * @param string $andor Operator to chain search terms (AND or OR).
     * @param int $limit Maximum number of results to retrieve (pagination constraint).
     * @param int $offset Starting point for retrieving results (pagination constraint).
     * @return array|bool Array of content objects on success, false failure.
     */
    public function searchContent(TfishPreference $tfish_preference, string $search_terms,
            string $andor, int $limit = 0, int $offset = 0)
    {
        
        $clean_search_terms = $escaped_search_terms = $clean_escaped_search_terms = array();
        $clean_andor = in_array($andor, array('AND', 'OR', 'exact'))
                ? TfishDataValidator::trimString($andor) : 'AND';
        $clean_limit = (int) $limit;
        $clean_offset = (int) $offset;
        
        // Create an escaped copy of the search terms that will be used to search the HTML teaser
        // and description fields.
        $escaped_search_terms = htmlspecialchars($search_terms, ENT_NOQUOTES, "UTF-8");

        if ($clean_andor === 'AND' || $clean_andor === 'OR') {
            $search_terms = explode(" ", $search_terms);
            $escaped_search_terms = explode(" ", $escaped_search_terms);
        } else {
            $search_terms = array($search_terms);
            $escaped_search_terms = array($escaped_search_terms);
        }
        
        // Trim search terms and discard any that are less than the minimum search length characters.
        foreach ($search_terms as $term) {
            $term = TfishDataValidator::TrimString($term);
            
            if (!empty($term) && mb_strlen($term, 'UTF-8') >= $tfish_preference->min_search_length) {
                $clean_search_terms[] = (string) $term;
            }
        }
        
        foreach ($escaped_search_terms as $escaped_term) {
            $escaped_term = TfishDataValidator::TrimString($escaped_term);
            
            if (!empty($escaped_term) && mb_strlen($escaped_term, 'UTF-8')
                    >= $tfish_preference->min_search_length) {
                $clean_escaped_search_terms[] = (string) $escaped_term;
            }
        }
        
        if (!empty($clean_search_terms)) {
            $results = $this->_searchContent($tfish_preference, $clean_search_terms,
                    $clean_escaped_search_terms, $clean_andor, $clean_limit, $clean_offset);
        } else {
            $results = false;
        }

        return $results;
    }

    /** @internal */
    private function _searchContent(TfishPreference $tfish_preference, array $search_terms,
            array $escaped_terms, string $andor, int $limit, int $offset)
    {
        $sql = $count = '';
        $search_term_placeholders = $escaped_term_placeholders = $results = array();
        
        $sql_count = "SELECT count(*) ";
        $sql_search = "SELECT * ";
        $result = array();

        $sql = "FROM `content` ";
        $count = count($search_terms);
        
        if ($count) {
            $sql .= "WHERE ";
            
            for ($i = 0; $i < $count; $i++) {
                $search_term_placeholders[$i] = ':search_term' . (string) $i;
                $escaped_term_placeholders[$i] = ':escaped_search_term' . (string) $i;
                $sql .= "(";
                $sql .= "`title` LIKE " . $search_term_placeholders[$i] . " OR ";
                $sql .= "`teaser` LIKE " . $escaped_term_placeholders[$i] . " OR ";
                $sql .= "`description` LIKE " . $escaped_term_placeholders[$i] . " OR ";
                $sql .= "`caption` LIKE " . $search_term_placeholders[$i] . " OR ";
                $sql .= "`creator` LIKE " . $search_term_placeholders[$i] . " OR ";
                $sql .= "`publisher` LIKE " . $search_term_placeholders[$i];
                $sql .= ")";
                
                if ($i != ($count - 1)) {
                    $sql .= " " . $andor . " ";
                }
            }
        }
        
        $sql .= " AND `online` = 1 AND `type` != 'TfishBlock' ORDER BY `date` DESC ";
        $sql_count .= $sql;
        
        // Bind the search term values and execute the statement.
        $statement = $this->tfish_database->preparedStatement($sql_count);
        
        if ($statement) {
            for ($i = 0; $i < $count; $i++) {
                $statement->bindValue($search_term_placeholders[$i], "%" . $search_terms[$i] . "%",
                        PDO::PARAM_STR);
                $statement->bindValue($escaped_term_placeholders[$i], "%" . $escaped_terms[$i] . "%",
                        PDO::PARAM_STR);
            }
        } else {
            return false;
        }

        // Execute the statement.
        $statement->execute();

        $row = $statement->fetch(PDO::FETCH_NUM);
        $result[0] = reset($row);
        unset($statement);

        // Retrieve the subset of objects actually required.
        if (!$limit) {
            $limit = $tfish_preference->search_pagination;
        }
        
        $sql .= "LIMIT :limit ";
        
        if ($offset) {
            $sql .= "OFFSET :offset ";
        }

        $sql_search .= $sql;
        $statement = $this->tfish_database->preparedStatement($sql_search);
        
        if ($statement) {
            for ($i = 0; $i < $count; $i++) {
                $statement->bindValue($search_term_placeholders[$i], "%" . $search_terms[$i] . "%",
                        PDO::PARAM_STR);
                $statement->bindValue($escaped_term_placeholders[$i], "%" . $escaped_terms[$i]
                        . "%", PDO::PARAM_STR);
                $statement->bindValue(":limit", (int) $limit, PDO::PARAM_INT);
                
                if ($offset) {
                    $statement->bindValue(":offset", (int) $offset, PDO::PARAM_INT);
                }
            }
        } else {
            return false;
        }

        // Execute the statement, fetch rows into the appropriate class type as determined by the
        // first column of the table.
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_CLASSTYPE | PDO::FETCH_PROPS_LATE);
        
        while ($object = $statement->fetch()) {
            $result[$object->id] = $object;
        }
        
        return $result;
    }

    /**
     * Toggle the online status of a content object.
     * 
     * @param int $id ID of content object.
     * @return boolean True on success, false on failure.
     */
    public function toggleOnlineStatus(int $id)
    {
        $clean_id = (int) $id;
        return $this->tfish_database->toggleBoolean($clean_id, 'content', 'online');
    }

    /**
     * Convert a database content row to a corresponding content object.
     * 
     * Only use this function to convert single objects, as it does a separate query to look up
     * the associated taglinks. Running it through a loop will therefore consume a lot of resources.
     * To convert multiple objects, load them directly into the relevant class files using
     * PDO::FETCH_CLASS, prepare a buffer of tags using getTags() and loop through the objects
     * referring to the buffer rather than hitting the database every time.
     * 
     * @param array $row Array of result set from database.
     * @return object|bool Content object on success, false on failure.
     */
    public function toObject(array $row)
    {
        if (empty($row) || !TfishDataValidator::isArray($row)) {
            return false;
        }

        // Check the content type is whitelisted.
        $type_whitelist = $this->getTypes();
        
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
                $criteria = new TfishCriteria($this->tfish_database);
                $criteria->add(new TfishCriteriaItem('content_id', (int) $content_object->id));
                $statement = $this->tfish_database->select('taglink', $criteria, array('tag_id'));
                
                if ($statement) {
                    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                        $tags[] = $row['tag_id'];
                    }
                    $content_object->tags = $tags;
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
     * @param object $obj TfishContentObject subclass.
     * @return bool True on success, false on failure.
     */
    public function update(TfishContentObject $obj)
    {
        $clean_id = TfishDataValidator::isInt($obj->id, 1) ? (int) $obj->id : 0;
        $key_values = $obj->toArray();
        unset($key_values['submission_time']); // Submission time should not be overwritten.
        $zeroed_properties = $obj->zeroedProperties();
        
        foreach ($zeroed_properties as $property) {
            $key_values[$property] = null;
        }
        
        $property_whitelist = $obj->getPropertyWhitelist();

        // Tags are stored in a separate table and must be handled in a separate query.
        unset($key_values['tags']);

        // Load the saved object from the database. This will be used to make comparisons with the
        // current object state and facilitate clean up of redundant tags, parent references, and
        // image/media files.
        $saved_object = $this->getObject($clean_id);
        
        /**
         * Handle image / media files for existing objects.
         */
        if (!empty($saved_object)) {

            /**
             * Image property.
             */
            
            // 1. Check if there is an existing image file associated with this content object.
            $existing_image = $this->_checkImage($saved_object);

            // 2. Is this content type allowed to have an image property?
            if (!array_key_exists('image', $property_whitelist)) {
                $key_values['image'] = '';
                if ($existing_image) {
                    $this->_deleteImage($existing_image);
                    $existing_image = '';
                }
            }
            
            // 3. Has an existing image file been flagged for deletion?
            if ($existing_image) {
                if (isset($_POST['deleteImage']) && !empty($_POST['deleteImage'])) {
                    $key_values['image'] = '';
                    $this->_deleteImage($existing_image);
                    $existing_image = '';
                }
            }
            
            // 4. Check if a new image file has been uploaded by looking in $_FILES.
            if (array_key_exists('image', $property_whitelist)) {

                if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
                    $filename = TfishDataValidator::trimString($_FILES['image']['name']);
                    $clean_filename = TfishFileHandler::uploadFile($filename, 'image');
                    
                    if ($clean_filename) {
                        $key_values['image'] = $clean_filename;
                        
                        // Delete old image file, if any.
                        if ($existing_image) {
                            $this->_deleteImage($existing_image);
                        }
                    } else {
                        $key_values['image'] = '';
                    }
                    
                } else { // No new image, use the existing file name.
                    $key_values['image'] = $existing_image;
                }
            }

            // If the updated object has no image attached, or has been instructed to delete
            // attached image, delete any old image files.
            if ($existing_image &&
                    ((!isset($key_values['image']) || empty($key_values['image']))
                    || (isset($_POST['deleteImage']) && !empty($_POST['deleteImage'])))) {
                $this->_deleteImage($existing_image);
            }

            /**
             * Media property.
             */
            
            // 1. Check if there is an existing media file associated with this content object.
            $existing_media = $this->_checkMedia($saved_object);
            
            // 2. Is this content type allowed to have a media property?
            if (!array_key_exists('media', $property_whitelist)) {
                $key_values['media'] = '';
                $key_values['format'] = '';
                $key_values['file_size'] = '';
                if ($existing_media) {
                    $this->_deleteMedia($existing_media);
                    $existing_media = '';
                }
            }
            
            // 3. Has existing media been flagged for deletion?
            if ($existing_media) {
                if (isset($_POST['deleteMedia']) && !empty($_POST['deleteMedia'])) {
                    $key_values['media'] = '';
                    $key_values['format'] = '';
                    $key_values['file_size'] = '';
                    $this->_deleteMedia($existing_media);
                    $existing_media = '';
                }
            }
            
            // 4. Process media file.
            if (array_key_exists('media', $property_whitelist)) {
                $clean_filename = '';
                
                // Get a whitelist of permitted mimetypes.
                $mimetype_whitelist = TfishFileHandler::getPermittedUploadMimetypes();
                
                // Get name of newly uploaded file (overwrites old one).
                if (isset($_FILES['media']['name']) && !empty($_FILES['media']['name'])) {
                    $filename = TfishDataValidator::trimString($_FILES['media']['name']);
                    $clean_filename = TfishFileHandler::uploadFile($filename, 'media'); 
                } else {
                    $clean_filename = $existing_media;
                }

                if ($clean_filename) {
                    if (isset($_FILES['media']['name']) && !empty($_FILES['media']['name'])) {
                        $extension = mb_strtolower(pathinfo($clean_filename, PATHINFO_EXTENSION), 'UTF-8');

                        // Set values of new media file.
                        $key_values['media'] = $clean_filename;
                        $key_values['format'] = $mimetype_whitelist[$extension];
                        $key_values['file_size'] = $_FILES['media']['size'];

                        // Delete any old media file.
                        if ($existing_media) {
                            $this->_deleteMedia($existing_media);
                            $existing_media = '';
                        }
                    // No new media, use the existing file name. Still need to validate it as the
                    // content type may have changed.
                    } else {
                        if ($existing_media) {
                            $key_values['media'] = $existing_media;
                            $key_values['format'] = $obj->format;
                            $key_values['file_size'] = $obj->file_size;
                        }
                    }           
                } else {
                    $key_values['media'] = '';
                    $key_values['format'] = '';
                    $key_values['file_size'] = '';

                    // Delete any old media file.
                    if ($existing_media) {
                        $this->_deleteMedia($existing_media);
                        $existing_media = '';
                    }
                }
            }
        }

        // Update tags
        $taglink_handler = new TfishTaglinkHandler();
        $result = $taglink_handler->updateTaglinks($clean_id, $obj->type, $obj->tags);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_TAGLINK_UPDATE_FAILED, E_USER_NOTICE);
            return false;
        }
        
        // Check if this object used to be a collection. If it has been changed to something else
        // clean up any parental references to it.
        if ($key_values['type'] !== 'TfishCollection' && !empty($saved_object)) {
            $ex_collection = $this->_checkExCollection($saved_object);
            
            if ($ex_collection === true) {
                $result = $this->deleteParentalReferences($clean_id);
                
                if (!$result) {
                    trigger_error(TFISH_ERROR_PARENT_UPDATE_FAILED, E_USER_NOTICE);
                    return false;
                }
            }
        }

        // Update the content object.
        $result = $this->tfish_database->update('content', $clean_id, $key_values);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
        }
        
        unset($result);

        return true;
    }
    
    /**
     * Check if a content object is currently registered as a TfishCollection in the database.
     * 
     * When updating an object, this method is used to check if it used to be a collection. If so,
     * other content objects referring to it as parent will need to be updated. Note that you must
     * pass in the SAVED copy of the object from the database, rather than the 'current' version, 
     * as the purpose of the method is to determine if the object *used to be* a collection.
     * 
     * @param object $obj The TfishContentObject to be tested.
     * @return boolean True if content object is registered as a TfishCollection in database,
     * otherwise false.
     */
    private function _checkExCollection(TfishContentObject $obj)
    {      
        if (!empty($obj->type) && $obj->type === 'TfishCollection') {
           return true; 
        }
        
        return false;
    }

    /**
     * Check if an existing object has an associated image file upload.
     * 
     * @param object $obj The TfishContentObject to be tested.
     * @return string Filename of associated image property.
     */
    private function _checkImage(TfishContentObject $obj)
    {        
        if (!empty($obj->image)) {
            return $obj->image;
        }

        return '';
    }

    /**
     * Check if an existing object has an associated media file upload.
     * 
     * @param object $obj TfishContentObject to be tested.
     * @return string Filename of associated media property.
     */
    private function _checkMedia(TfishContentObject $obj)
    {
        if (!empty($obj->media)) {
            return $obj->media;
        }
        
        return '';
    }

    /**
     * Increment a given content object counter field by one.
     * 
     * @param int $id ID of content object.
     */
    public function updateCounter(int $id)
    {
        $clean_id = (int) $id;
        return $this->tfish_database->updateCounter($clean_id, 'content', 'counter');
    }

}
