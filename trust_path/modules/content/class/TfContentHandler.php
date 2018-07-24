<?php

/**
 * TfContentHandler class file.
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
class TfContentHandler
{
    use TfContentTypes;
    
    protected $validator;
    protected $db;
    protected $criteria_factory;
    protected $item_factory;
    protected $file_handler;
    protected $taglink_handler;
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteria_factory, TfCriteriaItemFactory $criteria_item_factory,
            TfFileHandler $file_handler, TfTaglinkHandler $taglink_handler)
    {
        $this->validator = $validator;
        $this->db = $db;
        $this->criteria_factory = $criteria_factory;
        $this->item_factory = $criteria_item_factory;
        $this->file_handler = $file_handler;
        $this->taglink_handler = $taglink_handler;
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
    public function convertRowToObject(array $row)
    {
        if (empty($row) || !$this->validator->isArray($row)) {
            return false;
        }

        // Check the content type is whitelisted.
        $typeWhitelist = $this->getTypes();
        
        if (!empty($row['type']) && array_key_exists($row['type'], $typeWhitelist)) {
            $contentObject = new $row['type']($this->validator);
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
        }
        
        // Populate the object from the $row using whitelisted properties.
        if ($contentObject) {
            $contentObject->loadPropertiesFromArray($row, true);

            // Populate the tag property.
            if (isset($contentObject->tags) && !empty($contentObject->id)) {
                $tags = array();
                $criteria = $this->criteria_factory->getCriteria();
                $criteria->add($this->item_factory->getItem('contentId', (int) $contentObject->id));
                $statement = $this->db->select('taglink', $criteria, array('tagId'));
                
                if ($statement) {
                    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                        $tags[] = $row['tagId'];
                    }
                    $contentObject->setTags($tags);
                } else {
                    trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
                }
            }

            return $contentObject;
        }

        return false;
    }
    
    /**
     * Delete a single object from the content table.
     * 
     * @param int $id ID of content object to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            return false;
        }

        // Delete files associated with the image and media properties.
        $obj = $this->getObject($cleanId);
        
        if (!is_object($obj)) {
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
        $result = $this->taglink_handler->deleteTaglinks($obj);
        
        if (!$result) {
            return false;
        }

        // If object is a collection delete related parent references in child objects.
        if ($obj->type === 'TfCollection') {
            $this->deleteParentalReferences($cleanId);
        }

        // Finally, delete the object.
        $result = $this->db->delete('content', $cleanId);
        
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
            return $this->file_handler->deleteFile('image/' . $filename);
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
            return $this->file_handler->deleteFile('media/' . $filename);
        }
    }
    
    /**
     * Removes references to a collection when it is deleted or changed to another type.
     * 
     * @param int $id ID of the parent collection.
     * @return boolean True on success, false on failure.
     */
    public function deleteParentalReferences(int $id)
    {
        $cleanId = $this->validator->isInt($id, 1) ? (int) $id : null;
        
        if (!$cleanId) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        $criteria = $this->criteria_factory->getCriteria();
        $criteria->add($this->item_factory->getItem('parent', $cleanId));
        $result = $this->db->updateAll('content', array('parent' => 0), $criteria);

        if (!$result) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Inserts a content object into the database.
     * 
     * Note that content child content classes that have unset unused properties from the parent
     * should reset them to null before insertion or update. This is to guard against the case
     * where the admin reassigns the type of a content object - it makes sure that unused properties
     * are zeroed in the database. 
     * 
     * @param object $obj TfContentObject subclass.
     * @return bool True on success, false on failure.
     */
    public function insert(TfContentObject $obj)
    {
        $key_values = $obj->convertObjectToArray();
        $key_values['submissionTime'] = time(); // Automatically set submission time.
        unset($key_values['id']); // ID is auto-incremented by the database on insert operations.
        unset($key_values['tags']);
        unset($key_values['validator']);

        // Process image and media files before inserting the object, as related fields must be set.
        $property_whitelist = $obj->getPropertyWhitelist();
        
        if (array_key_exists('image', $property_whitelist) && !empty($_FILES['image']['name'])) {
            $filename = $this->validator->trimString($_FILES['image']['name']);
            $clean_filename = $this->file_handler->uploadFile($filename, 'image');
            
            if ($clean_filename) {
                $key_values['image'] = $clean_filename;
            }
        }

        if (array_key_exists('media', $property_whitelist) && !empty($_FILES['media']['name'])) {
            $filename = $this->validator->trimString($_FILES['media']['name']);
            $clean_filename = $this->file_handler->uploadFile($filename, 'media');
            
            if ($clean_filename) {
                $key_values['media'] = $clean_filename;
                $mimetypeWhitelist = $obj->getListOfPermittedUploadMimetypes();
                $extension = pathinfo($clean_filename, PATHINFO_EXTENSION);
                $key_values['format'] = $mimetypeWhitelist[$extension];
                $key_values['fileSize'] = $_FILES['media']['size'];
            }
        }

        // Insert the object into the database.
        $result = $this->db->insert('content', $key_values);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
            return false;
        } else {
            $contentId = $this->db->lastInsertId();
        }
        
        unset($key_values, $result);

        // Tags are stored separately in the taglinks table. Tags are assembled in one batch before
        // proceeding to insertion; so if one fails a range check all should fail.
        if (isset($obj->tags) and $this->validator->isArray($obj->tags)) {
            // If the lastInsertId could not be retrieved, then halt execution becuase this data
            // is necessary in order to correctly assign taglinks to content objects.
            if (!$contentId) {
                trigger_error(TFISH_ERROR_NO_LAST_INSERT_ID, E_USER_ERROR);
                exit;
            }

            $result = $this->taglink_handler->insertTaglinks($contentId, $obj->type, $obj->tags);
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if a class name is a sanctioned subclass of TfContentObject.
     * 
     * Basically this just checks if the class name is whitelisted.
     * 
     * @param string $type Type of content object.
     * @return bool True if sanctioned type otherwise false.
     */
    public function isSanctionedType(string $type)
    {
        $type = $this->validator->trimString($type);
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

        $cleanOnline_only = $this->validator->isBool($online_only) ? (bool) $online_only : true;
        $tags = $this->getTagList($cleanOnline_only);
        
        if (empty($tags)) {
            return false;
        }

        // Restrict tag list to those actually in use.
        $cleanType = (isset($type) && $this->isSanctionedType($type))
                ? $this->validator->trimString($type) : null;

        $criteria = $this->criteria_factory->getCriteria();

        // Filter tags by type.
        if (isset($cleanType)) {
            $criteria->add($this->item_factory->getItem('contentType', $cleanType));
        }

        // Put a check for online status in here.
        $statement = $this->db->selectDistinct('taglink', $criteria, array('tagId'));
        
        if ($statement) {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                if (isset($tags[$row['tagId']])) {
                    $distinct_tags[$row['tagId']] = $tags[$row['tagId']];
                }
            }
        }

        // Sort the tags into alphabetical order.
        asort($distinct_tags);

        return $distinct_tags;
    }

    /**
     * Count content objects optionally matching conditions specified with a TfCriteria object.
     * 
     * @param object $criteria TfCriteria object used to build conditional database query.
     * @return int $count Number of objects matching conditions.
     */
    public function getCount(TfCriteria $criteria = null)
    {
        if (!isset($criteria)) {
            $criteria = $this->criteria_factory->getCriteria();
        }
        
        if ($criteria && !empty($criteria->limit)) {
            $limit = $criteria->limit;
            $criteria->setLimit(0);
        }
        
        $count = $this->db->selectCount('content', $criteria);
        
        if (isset($limit)) {
            $criteria->setLimit((int) $limit);
        }

        return $count;
    }
    
    /**
     * Return a list of mimetypes.
     * 
     * This list is not exhaustive, but it does cover most things that a sane person would want.
     * Feel free to add more if you wish, but do NOT use this as a whitelist of permitted mimetypes,
     * it is just a reference.
     * 
     * @return array Array of mimetypes with extension as key.
     * @copyright	The ImpressCMS Project http://www.impresscms.org/
     * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
     * @author		marcan <marcan@impresscms.org>
     */
    public function getListOfMimetypes()
    {
        return array(
            "hqx" => "application/mac-binhex40",
            "doc" => "application/msword",
            "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "dot" => "application/msword",
            "bin" => "application/octet-stream",
            "lha" => "application/octet-stream",
            "lzh" => "application/octet-stream",
            "exe" => "application/octet-stream",
            "class" => "application/octet-stream",
            "so" => "application/octet-stream",
            "dll" => "application/octet-stream",
            "pdf" => "application/pdf",
            "ai" => "application/postscript",
            "eps" => "application/postscript",
            "ps" => "application/postscript",
            "smi" => "application/smil",
            "smil" => "application/smil",
            "wbxml" => "application/vnd.wap.wbxml",
            "wmlc" => "application/vnd.wap.wmlc",
            "wmlsc" => "application/vnd.wap.wmlscriptc",
            "odt" => "application/vnd.oasis.opendocument.text",
            "xla" => "application/vnd.ms-excel",
            "xls" => "application/vnd.ms-excel",
            "xlt" => "application/vnd.ms-excel",
            "ppt" => "application/vnd.ms-powerpoint",
            "csh" => "application/x-csh",
            "dcr" => "application/x-director",
            "dir" => "application/x-director",
            "dxr" => "application/x-director",
            "spl" => "application/x-futuresplash",
            "gtar" => "application/x-gtar",
            "php" => "application/x-httpd-php",
            "php3" => "application/x-httpd-php",
            "php4" => "application/x-httpd-php",
            "php5" => "application/x-httpd-php",
            "phtml" => "application/x-httpd-php",
            "js" => "application/x-javascript",
            "sh" => "application/x-sh",
            "swf" => "application/x-shockwave-flash",
            "sit" => "application/x-stuffit",
            "tar" => "application/x-tar",
            "tcl" => "application/x-tcl",
            "xhtml" => "application/xhtml+xml",
            "xht" => "application/xhtml+xml",
            "xhtml" => "application/xml",
            "ent" => "application/xml-external-parsed-entity",
            "dtd" => "application/xml-dtd",
            "mod" => "application/xml-dtd",
            "gz" => "application/x-gzip",
            "zip" => "application/zip",
            "au" => "audio/basic",
            "snd" => "audio/basic",
            "mid" => "audio/midi",
            "midi" => "audio/midi",
            "kar" => "audio/midi",
            "mp1" => "audio/mpeg",
            "mp2" => "audio/mpeg",
            "mp3" => "audio/mpeg",
            "aif" => "audio/x-aiff",
            "aiff" => "audio/x-aiff",
            "m3u" => "audio/x-mpegurl",
            "ram" => "audio/x-pn-realaudio",
            "rm" => "audio/x-pn-realaudio",
            "rpm" => "audio/x-pn-realaudio-plugin",
            "ra" => "audio/x-realaudio",
            "wav" => "audio/x-wav",
            "bmp" => "image/bmp",
            "gif" => "image/gif",
            "jpeg" => "image/jpeg",
            "jpg" => "image/jpeg",
            "jpe" => "image/jpeg",
            "png" => "image/png",
            "tiff" => "image/tiff",
            "tif" => "image/tif",
            "wbmp" => "image/vnd.wap.wbmp",
            "pnm" => "image/x-portable-anymap",
            "pbm" => "image/x-portable-bitmap",
            "pgm" => "image/x-portable-graymap",
            "ppm" => "image/x-portable-pixmap",
            "xbm" => "image/x-xbitmap",
            "xpm" => "image/x-xpixmap",
            "ics" => "text/calendar",
            "ifb" => "text/calendar",
            "css" => "text/css",
            "html" => "text/html",
            "htm" => "text/html",
            "asc" => "text/plain",
            "txt" => "text/plain",
            "rtf" => "text/rtf",
            "sgml" => "text/x-sgml",
            "sgm" => "text/x-sgml",
            "tsv" => "text/tab-seperated-values",
            "wml" => "text/vnd.wap.wml",
            "wmls" => "text/vnd.wap.wmlscript",
            "xsl" => "text/xml",
            "mpeg" => "video/mpeg",
            "mpg" => "video/mpeg",
            "mpe" => "video/mpeg",
            "mp4" => "video/mp4",
            "qt" => "video/quicktime",
            "mov" => "video/quicktime",
            "avi" => "video/x-msvideo",
        );
    }

    /**
     * Returns a list of content object titles with ID as key.
     * 
     * @param object $criteria TfCriteria object used to build conditional database query.
     * @return array Array as id => title of content objects.
     */
    public function getListOfObjectTitles(TfCriteria $criteria = null)
    {
        $content_list = array();
        $columns = array('id', 'title');

        if (!isset($criteria)) {
            $criteria = $this->criteria_factory->getCriteria();
        }
        
        // Set default sorting order by submission time descending.
        if (!$criteria->order) {
            $criteria->setOrder('date');
            $criteria->setSecondaryOrder('submissionTime');
            $criteria->setSecondaryOrderType('DESC');
        }

        $statement = $this->db->select('content', $criteria, $columns);
        
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
        $cleanId = (int) $id;
        $row = $object = '';
        
        if ($this->validator->isInt($cleanId, 1)) {
            $criteria = $this->criteria_factory->getCriteria();
            $criteria->add($this->item_factory->getItem('id', $cleanId));
            $statement = $this->db->select('content', $criteria);
            
            if ($statement) {
                $row = $statement->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($row) {
                $object = $this->convertRowToObject($row);
                return $object;
            }
        }
        
        return false;
    }

    /**
     * Get content objects, optionally matching conditions specified with a TfCriteria object.
     * 
     * @param object $criteria TfCriteria object used to build conditional database query.
     * @return array Array of content objects.
     */
    public function getObjects(TfCriteria $criteria = null)
    {
        $objects = array();
        
        if (!isset($criteria)) {
            $criteria = $this->criteria_factory->getCriteria();
        }

        // Set default sorting order by submission time descending.        
        if (!$criteria->order) {
            $criteria->setOrder('date');
            $criteria->setSecondaryOrder('submissionTime');
            $criteria->setSecondaryOrderType('DESC');
        }

        $statement = $this->db->select('content', $criteria);
        if ($statement) {

            // Fetch rows into the appropriate class type, as determined by the first column.
            // Note that you can't pass constructor arguments using FETCH_CLASSTYPE.
            
            /**$statement->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_CLASSTYPE | PDO::FETCH_PROPS_LATE);

            while ($object = $statement->fetch()) {
                $objects[$object->id] = $object;
            }*/

            // Alternative method - allows constructor arguments to be passed in.
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $object = new $row['type']($this->validator);
                $object->loadPropertiesFromArray($row, true);
                $objects[$object->id] = $object;
                unset($object);
            }            

            unset($statement);
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }

        // Get the tags for these objects.
        if (!empty($objects)) {
            $taglinks = array();
            $object_ids = array_keys($objects);

            $criteria = $this->criteria_factory->getCriteria();
            
            foreach ($object_ids as $id) {
                $criteria->add($this->item_factory->getItem('contentId', (int) $id), "OR");
                unset($id);
            }

            $statement = $this->db->select('taglink', $criteria);

            if ($statement) {
                // Sort tag into multi-dimensional array indexed by contentId.
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $taglinks[$row['contentId']][] = $row['tagId'];
                }

                // Assign the sorted tags to correct content objects.
                foreach ($taglinks as $contentId => $tags) {
                    $objects[$contentId]->setTags($tags);
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
        $clean_selected = (isset($selected) && $this->validator->isInt($selected, 0, 1)) 
                ? (int) $selected : null; // Offline (0) or online (1)
        $clean_zero_option = $this->validator->escapeForXss($this->validator->trimString($zero_option));
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
     * Get an array of all tag objects in $id => $title format.
     * 
     * @param bool Get tags marked online only.
     * @return array Array of tag IDs and titles.
     */
    public function getTagList(bool $online_only = true)
    {
        $tags = array();
        $statement = false;
        $cleanOnline_only = $this->validator->isBool($online_only) ? (bool) $online_only : true;
        $columns = array('id', 'title');
        $criteria = $this->criteria_factory->getCriteria();
        $criteria->add($this->item_factory->getItem('type', 'TfTag'));
        
        if ($cleanOnline_only) {
            $criteria->add($this->item_factory->getItem('online', true));
        }

        $statement = $this->db->select('content', $criteria, $columns);
        
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
     * @return array Array of TfTag objects.
     */
    public function getTags()
    {
        $tags = array();
        $criteria = $this->criteria_factory->getCriteria();
        $criteria->add($this->item_factory->getItem('type', 'TfTag'));
        $tags = $this->getObjects($criteria);
        
        return $tags;
    }
    
    /**
     * Search the filtering criteria ($criteria->items) to see if object type has been set and
     * return the key.
     * 
     * @param array $criteria_items Array of TfCriteriaItem objects.
     * @return int|null Key of relevant TfCriteriaItem or null.
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
            $clean_zero_option = $this->validator->escapeForXss($this->validator->trimString($zero_option));
        } else {
            $clean_zero_option = TFISH_TYPE;
        }
        
        $clean_selected = '';
        $type_list = $this->getTypes();

        if ($selected && $this->validator->isAlnumUnderscore($selected)) {
            if (array_key_exists($selected, $type_list)) {
                $clean_selected = $this->validator->trimString($selected);
            }
        }

        $options = array(0 => TFISH_SELECT_TYPE) + $type_list;
        $select_box = '<select class="form-control custom-select" name="type" id="type" '
                . 'onchange="this.form.submit()">';
        
        foreach ($options as $key => $value) {
            $select_box .= ($key === $clean_selected) ? '<option value="' . $this->validator->escapeForXss($key)
                    . '" selected>' . $this->validator->escapeForXss($value) . '</option>' : '<option value="'
                . $this->validator->escapeForXss($key) . '">' . $this->validator->escapeForXss($value) . '</option>';
        }
        
        $select_box .= '</select>';

        return $select_box;
    }

    /**
     * Converts an array of tagIds into an array of tag links with an arbitrary local target file.
     * 
     * Note that the filename may only consist of alphanumeric characters and underscores. Do not
     * include the file extension (eg. use 'article' instead of 'article.php'. The base URL of the
     * site will be prepended and .php plus the tagId will be appended.
     * 
     * @param array $tags Array of tag IDs.
     * @param string $target_filename Name of file for tag links to point at.
     * @return array Array of HTML tag links.
     */
    public function makeTagLinks(array $tags, string $target_filename = '')
    {
        if (empty($target_filename)) {
            $clean_filename = TFISH_URL . '?tagId=';
        } else {
            if (!$this->validator->isAlnumUnderscore($target_filename)) {
                trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
            } else {
                $target_filename = $this->validator->trimString($target_filename);
                $clean_filename = TFISH_URL . $this->validator->escapeForXss($target_filename)
                        . '.php?tagId=';
            }
        }

        $tag_list = $this->getTagList(false);
        $tag_links = array();
        
        foreach ($tags as $tag) {
            if ($this->validator->isInt($tag, 1) && array_key_exists($tag, $tag_list)) {
                $tag_links[$tag] = '<a href="' . $this->validator->escapeForXss($clean_filename . $tag) . '">'
                        . $this->validator->escapeForXss($tag_list[$tag]) . '</a>';
            }
            
            unset($tag);
        }

        return $tag_links;
    }
    
    /**
     * Initiate streaming of a downloadable media file associated with a content object.
     * 
     * DOES NOT WORK WITH COMPRESSION ENABLED IN OUTPUT BUFFER. This method acts as an intermediary
     * to provide access to uploaded file resources that reside outside of the web root, while
     * concealing the real file path and name. Use this method to provide safe user access to
     * uploaded files. If anything nasty gets uploaded nobody will be able to execute it directly
     * through the browser.
     * 
     * @param int $id ID of the associated content object.
     * @param string $filename An alternative name (rename) for the file you wish to transfer,
     * excluding extension.
     * @return bool True on success, false on failure. 
     */
    public function streamDownloadToBrowser(int $id, string $filename = '')
    {
        $cleanId = $this->validator->isInt($id, 1) ? (int) $id : false;
        $clean_filename = !empty($filename) ? $this->validator->trimString($filename) : '';
        
        if ($cleanId) {
            $result = $this->_streamDownloadToBrowser($cleanId, $clean_filename);
            if ($result === false) {
                return false;
            }
            return true;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_NOTICE);
        }
    }

    /** @internal */
    private function _streamDownloadToBrowser(int $id, string $filename)
    {
        $criteria = $this->criteria_factory->getCriteria();
        $criteria->add($this->item_factory->getItem('id', $id));
        $statement = $this->db->select('content', $criteria);
        
        if (!$statement) {
            trigger_error(TFISH_ERROR_NO_STATEMENT, E_USER_NOTICE);
            return false;
        }
        
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $content = $this->convertRowToObject($row);
        
        if ($content && $content->online) {
            $media = isset($content->media) ? $content->media : false;
            
            if ($media && is_readable(TFISH_MEDIA_PATH . $content->media)) {
                ob_start();
                $filepath = TFISH_MEDIA_PATH . $content->media;
                $filename = empty($filename) ? pathinfo($filepath, PATHINFO_FILENAME) : $filename;
                $file_extension = pathinfo($filepath, PATHINFO_EXTENSION);
                $fileSize = filesize(TFISH_MEDIA_PATH . $content->media);
                $mimetype_list = $this->getListOfMimetypes();
                $mimetype = $mimetype_list[$file_extension];

                // Must call session_write_close() first otherwise the script gets locked.
                session_write_close();
                
                // Output the header.
                $this->_outputHeader($filename, $file_extension, $mimetype, $fileSize, $filepath);
                
            } else {
                return false;
            }
        } else {
            trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_WARNING);
            return false;
        }
    }
    
    private function _outputHeader($filename, $file_extension, $mimetype, $fileSize, $filepath)
    {
        // Prevent caching
        header("Pragma: public");
        header("Expires: -1");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

        // Set file-specific headers.
        header('Content-Disposition: attachment; filename="' . $filename . '.'
                . $file_extension . '"');
        header("Content-Type: " . $mimetype);
        header("Content-Length: " . $fileSize);
        ob_clean();
        flush();
        readfile($filepath);
    }

    /**
     * Toggle the online status of a content object.
     * 
     * @param int $id ID of content object.
     * @return boolean True on success, false on failure.
     */
    public function toggleOnlineStatus(int $id)
    {
        $cleanId = (int) $id;
        return $this->db->toggleBoolean($cleanId, 'content', 'online');
    }

    /**
     * Updates a content object in the database.
     * 
     * @param object $obj TfContentObject subclass.
     * @return bool True on success, false on failure.
     */
    public function update(TfContentObject $obj)
    {
        $cleanId = $this->validator->isInt($obj->id, 1) ? (int) $obj->id : 0;
        $key_values = $obj->convertObjectToArray();
        
        unset($key_values['submissionTime']); // Submission time should not be overwritten.
        $zeroedProperties = $obj->getListOfZeroedProperties();

        foreach ($zeroedProperties as $property) {
            $key_values[$property] = null;
        }

        $property_whitelist = $obj->getPropertyWhitelist();

        // Tags are stored in a separate table and must be handled in a separate query.
        unset($key_values['tags']);
        
        // Validator is non-persistent and not stored in the database.
        unset($key_values['validator']);

        // Load the saved object from the database. This will be used to make comparisons with the
        // current object state and facilitate clean up of redundant tags, parent references, and
        // image/media files.
        
        $saved_object = $this->getObject($cleanId);
        
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
                    $filename = $this->validator->trimString($_FILES['image']['name']);
                    $clean_filename = $this->file_handler->uploadFile($filename, 'image');
                    
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
                $key_values['fileSize'] = '';
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
                    $key_values['fileSize'] = '';
                    $this->_deleteMedia($existing_media);
                    $existing_media = '';
                }
            }
            
            // 4. Process media file.
            if (array_key_exists('media', $property_whitelist)) {
                $clean_filename = '';
                
                // Get a whitelist of permitted mimetypes.
                $mimetypeWhitelist = $obj->getListOfPermittedUploadMimetypes();
                
                // Get name of newly uploaded file (overwrites old one).
                if (isset($_FILES['media']['name']) && !empty($_FILES['media']['name'])) {
                    $filename = $this->validator->trimString($_FILES['media']['name']);
                    $clean_filename = $this->file_handler->uploadFile($filename, 'media'); 
                } else {
                    $clean_filename = $existing_media;
                }

                if ($clean_filename) {
                    if (isset($_FILES['media']['name']) && !empty($_FILES['media']['name'])) {
                        $extension = mb_strtolower(pathinfo($clean_filename, PATHINFO_EXTENSION), 'UTF-8');

                        // Set values of new media file.
                        $key_values['media'] = $clean_filename;
                        $key_values['format'] = $mimetypeWhitelist[$extension];
                        $key_values['fileSize'] = $_FILES['media']['size'];

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
                            $key_values['fileSize'] = $obj->fileSize;
                        }
                    }           
                } else {
                    $key_values['media'] = '';
                    $key_values['format'] = '';
                    $key_values['fileSize'] = '';

                    // Delete any old media file.
                    if ($existing_media) {
                        $this->_deleteMedia($existing_media);
                        $existing_media = '';
                    }
                }
            }
        }

        // Update tags
        $result = $this->taglink_handler->updateTaglinks($cleanId, $obj->type, $obj->tags);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_TAGLINK_UPDATE_FAILED, E_USER_NOTICE);
            return false;
        }
        
        // Check if this object used to be a collection. If it has been changed to something else
        // clean up any parental references to it.
        if ($key_values['type'] !== 'TfCollection' && !empty($saved_object)) {
            $ex_collection = $this->_checkExCollection($saved_object);
            
            if ($ex_collection === true) {
                $result = $this->deleteParentalReferences($cleanId);
                
                if (!$result) {
                    trigger_error(TFISH_ERROR_PARENT_UPDATE_FAILED, E_USER_NOTICE);
                    return false;
                }
            }
        }

        // Update the content object.
        $result = $this->db->update('content', $cleanId, $key_values);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
        }
        
        unset($result);

        return true;
    }
    
    /**
     * Check if a content object is currently registered as a TfCollection in the database.
     * 
     * When updating an object, this method is used to check if it used to be a collection. If so,
     * other content objects referring to it as parent will need to be updated. Note that you must
     * pass in the SAVED copy of the object from the database, rather than the 'current' version, 
     * as the purpose of the method is to determine if the object *used to be* a collection.
     * 
     * @param object $obj The TfContentObject to be tested.
     * @return boolean True if content object is registered as a TfCollection in database,
     * otherwise false.
     */
    private function _checkExCollection(TfContentObject $obj)
    {      
        if (!empty($obj->type) && $obj->type === 'TfCollection') {
           return true; 
        }
        
        return false;
    }

    /**
     * Check if an existing object has an associated image file upload.
     * 
     * @param object $obj The TfContentObject to be tested.
     * @return string Filename of associated image property.
     */
    private function _checkImage(TfContentObject $obj)
    {        
        if (!empty($obj->image)) {
            return $obj->image;
        }

        return '';
    }

    /**
     * Check if an existing object has an associated media file upload.
     * 
     * @param object $obj TfContentObject to be tested.
     * @return string Filename of associated media property.
     */
    private function _checkMedia(TfContentObject $obj)
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
        $cleanId = (int) $id;
        return $this->db->updateCounter($cleanId, 'content', 'counter');
    }

}
