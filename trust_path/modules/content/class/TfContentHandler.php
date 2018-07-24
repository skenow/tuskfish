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
    protected $criteriaFactory;
    protected $itemFactory;
    protected $fileHandler;
    protected $taglinkHandler;
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfCriteriaItemFactory $criteriaItemFactory,
            TfFileHandler $fileHandler, TfTaglinkHandler $taglinkHandler)
    {
        $this->validator = $validator;
        $this->db = $db;
        $this->criteriaFactory = $criteriaFactory;
        $this->itemFactory = $criteriaItemFactory;
        $this->fileHandler = $fileHandler;
        $this->taglinkHandler = $taglinkHandler;
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
                $criteria = $this->criteriaFactory->getCriteria();
                $criteria->add($this->itemFactory->getItem('contentId', (int) $contentObject->id));
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
        $result = $this->taglinkHandler->deleteTaglinks($obj);
        
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
            return $this->fileHandler->deleteFile('image/' . $filename);
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
            return $this->fileHandler->deleteFile('media/' . $filename);
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
        
        $criteria = $this->criteriaFactory->getCriteria();
        $criteria->add($this->itemFactory->getItem('parent', $cleanId));
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
        $keyValues = $obj->convertObjectToArray();
        $keyValues['submissionTime'] = time(); // Automatically set submission time.
        unset($keyValues['id']); // ID is auto-incremented by the database on insert operations.
        unset($keyValues['tags']);
        unset($keyValues['validator']);

        // Process image and media files before inserting the object, as related fields must be set.
        $propertyWhitelist = $obj->getPropertyWhitelist();
        
        if (array_key_exists('image', $propertyWhitelist) && !empty($_FILES['image']['name'])) {
            $filename = $this->validator->trimString($_FILES['image']['name']);
            $cleanFilename = $this->fileHandler->uploadFile($filename, 'image');
            
            if ($cleanFilename) {
                $keyValues['image'] = $cleanFilename;
            }
        }

        if (array_key_exists('media', $propertyWhitelist) && !empty($_FILES['media']['name'])) {
            $filename = $this->validator->trimString($_FILES['media']['name']);
            $cleanFilename = $this->fileHandler->uploadFile($filename, 'media');
            
            if ($cleanFilename) {
                $keyValues['media'] = $cleanFilename;
                $mimetypeWhitelist = $obj->getListOfPermittedUploadMimetypes();
                $extension = pathinfo($cleanFilename, PATHINFO_EXTENSION);
                $keyValues['format'] = $mimetypeWhitelist[$extension];
                $keyValues['fileSize'] = $_FILES['media']['size'];
            }
        }

        // Insert the object into the database.
        $result = $this->db->insert('content', $keyValues);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
            return false;
        } else {
            $contentId = $this->db->lastInsertId();
        }
        
        unset($keyValues, $result);

        // Tags are stored separately in the taglinks table. Tags are assembled in one batch before
        // proceeding to insertion; so if one fails a range check all should fail.
        if (isset($obj->tags) and $this->validator->isArray($obj->tags)) {
            // If the lastInsertId could not be retrieved, then halt execution becuase this data
            // is necessary in order to correctly assign taglinks to content objects.
            if (!$contentId) {
                trigger_error(TFISH_ERROR_NO_LAST_INSERT_ID, E_USER_ERROR);
                exit;
            }

            $result = $this->taglinkHandler->insertTaglinks($contentId, $obj->type, $obj->tags);
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
        $sanctionedTypes = $this->getTypes();
        
        if (array_key_exists($type, $sanctionedTypes)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get a list of tags actually in use by other content objects, optionally filtered by type.
     * 
     * Used primarily to build select box controls. Use $onlineOnly to select only those tags that
     * are marked as online (true), or all tags (false).
     * 
     * @param string $type Type of content object.
     * @param bool $onlineOnly True if marked as online, false if marked as offline.
     * @return array|bool List of tags if available, false if empty.
     */
    public function getActiveTagList(string $type = null, bool $onlineOnly = true)
    {
        $tags = $distinctTags = array();

        $cleanOnlineOnly = $this->validator->isBool($onlineOnly) ? (bool) $onlineOnly : true;
        $tags = $this->getTagList($cleanOnlineOnly);
        
        if (empty($tags)) {
            return false;
        }

        // Restrict tag list to those actually in use.
        $cleanType = (isset($type) && $this->isSanctionedType($type))
                ? $this->validator->trimString($type) : null;

        $criteria = $this->criteriaFactory->getCriteria();

        // Filter tags by type.
        if (isset($cleanType)) {
            $criteria->add($this->itemFactory->getItem('contentType', $cleanType));
        }

        // Put a check for online status in here.
        $statement = $this->db->selectDistinct('taglink', $criteria, array('tagId'));
        
        if ($statement) {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                if (isset($tags[$row['tagId']])) {
                    $distinctTags[$row['tagId']] = $tags[$row['tagId']];
                }
            }
        }

        // Sort the tags into alphabetical order.
        asort($distinctTags);

        return $distinctTags;
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
            $criteria = $this->criteriaFactory->getCriteria();
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
        $contentList = array();
        $columns = array('id', 'title');

        if (!isset($criteria)) {
            $criteria = $this->criteriaFactory->getCriteria();
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
                $contentList[$row['id']] = $row['title'];
            }
            unset($statement);
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }

        return $contentList;
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
            $criteria = $this->criteriaFactory->getCriteria();
            $criteria->add($this->itemFactory->getItem('id', $cleanId));
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
            $criteria = $this->criteriaFactory->getCriteria();
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
            $objectIds = array_keys($objects);

            $criteria = $this->criteriaFactory->getCriteria();
            
            foreach ($objectIds as $id) {
                $criteria->add($this->itemFactory->getItem('contentId', (int) $id), "OR");
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
     * @param string $zeroOption The text to display in the zero option of the select box.
     * @return string HTML select box.
     */
    public function getOnlineSelectBox(int $selected = null,
            string $zeroOption = TFISH_ONLINE_STATUS)
    {
        $cleanSelected = (isset($selected) && $this->validator->isInt($selected, 0, 1)) 
                ? (int) $selected : null; // Offline (0) or online (1)
        $cleanZeroOption = $this->validator->escapeForXss($this->validator->trimString($zeroOption));
        $options = array(2 => TFISH_SELECT_STATUS, 1 => TFISH_ONLINE, 0 => TFISH_OFFLINE);
        $selectBox = '<select class="form-control custom-select" name="online" id="online" '
                . 'onchange="this.form.submit()">';
        
        if (isset($cleanSelected)) {
            foreach ($options as $key => $value) {
                $selectBox .= ($key === $cleanSelected) ? '<option value="' . $key . '" selected>' 
                        . $value . '</option>' : '<option value="' . $key . '">' . $value 
                        . '</option>';
            }
        } else { // Nothing selected
            $selectBox .= '<option value="2" selected>' . TFISH_SELECT_STATUS . '</option>';
            $selectBox .= '<option value="1">' . TFISH_ONLINE . '</option>';
            $selectBox .= '<option value="0">' . TFISH_OFFLINE . '</option>';
        }

        $selectBox .= '</select>';

        return $selectBox;
    }

    /**
     * Get an array of all tag objects in $id => $title format.
     * 
     * @param bool Get tags marked online only.
     * @return array Array of tag IDs and titles.
     */
    public function getTagList(bool $onlineOnly = true)
    {
        $tags = array();
        $statement = false;
        $cleanOnlineOnly = $this->validator->isBool($onlineOnly) ? (bool) $onlineOnly : true;
        $columns = array('id', 'title');
        $criteria = $this->criteriaFactory->getCriteria();
        $criteria->add($this->itemFactory->getItem('type', 'TfTag'));
        
        if ($cleanOnlineOnly) {
            $criteria->add($this->itemFactory->getItem('online', true));
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
        $criteria = $this->criteriaFactory->getCriteria();
        $criteria->add($this->itemFactory->getItem('type', 'TfTag'));
        $tags = $this->getObjects($criteria);
        
        return $tags;
    }
    
    /**
     * Search the filtering criteria ($criteria->items) to see if object type has been set and
     * return the key.
     * 
     * @param array $criteriaItems Array of TfCriteriaItem objects.
     * @return int|null Key of relevant TfCriteriaItem or null.
     */
    protected function getTypeIndex(array $criteriaItems)
    {
        foreach ($criteriaItems as $key => $item) {
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
     * @param string $zeroOption The default text to show at top of select box.
     * @return string HTML select box.
     */
    public function getTypeSelectBox(string $selected = '', string $zeroOption = null)
    {
        // The text to display in the zero option of the select box.
        if (isset($zeroOption)) {
            $cleanZeroOption = $this->validator->escapeForXss($this->validator->trimString($zeroOption));
        } else {
            $cleanZeroOption = TFISH_TYPE;
        }
        
        $cleanSelected = '';
        $typeList = $this->getTypes();

        if ($selected && $this->validator->isAlnumUnderscore($selected)) {
            if (array_key_exists($selected, $typeList)) {
                $cleanSelected = $this->validator->trimString($selected);
            }
        }

        $options = array(0 => TFISH_SELECT_TYPE) + $typeList;
        $selectBox = '<select class="form-control custom-select" name="type" id="type" '
                . 'onchange="this.form.submit()">';
        
        foreach ($options as $key => $value) {
            $selectBox .= ($key === $cleanSelected) ? '<option value="' . $this->validator->escapeForXss($key)
                    . '" selected>' . $this->validator->escapeForXss($value) . '</option>' : '<option value="'
                . $this->validator->escapeForXss($key) . '">' . $this->validator->escapeForXss($value) . '</option>';
        }
        
        $selectBox .= '</select>';

        return $selectBox;
    }

    /**
     * Converts an array of tagIds into an array of tag links with an arbitrary local target file.
     * 
     * Note that the filename may only consist of alphanumeric characters and underscores. Do not
     * include the file extension (eg. use 'article' instead of 'article.php'. The base URL of the
     * site will be prepended and .php plus the tagId will be appended.
     * 
     * @param array $tags Array of tag IDs.
     * @param string $targetFilename Name of file for tag links to point at.
     * @return array Array of HTML tag links.
     */
    public function makeTagLinks(array $tags, string $targetFilename = '')
    {
        if (empty($targetFilename)) {
            $cleanFilename = TFISH_URL . '?tagId=';
        } else {
            if (!$this->validator->isAlnumUnderscore($targetFilename)) {
                trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
            } else {
                $targetFilename = $this->validator->trimString($targetFilename);
                $cleanFilename = TFISH_URL . $this->validator->escapeForXss($targetFilename)
                        . '.php?tagId=';
            }
        }

        $tagList = $this->getTagList(false);
        $tagLinks = array();
        
        foreach ($tags as $tag) {
            if ($this->validator->isInt($tag, 1) && array_key_exists($tag, $tagList)) {
                $tagLinks[$tag] = '<a href="' . $this->validator->escapeForXss($cleanFilename . $tag) . '">'
                        . $this->validator->escapeForXss($tagList[$tag]) . '</a>';
            }
            
            unset($tag);
        }

        return $tagLinks;
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
        $cleanFilename = !empty($filename) ? $this->validator->trimString($filename) : '';
        
        if ($cleanId) {
            $result = $this->_streamDownloadToBrowser($cleanId, $cleanFilename);
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
        $criteria = $this->criteriaFactory->getCriteria();
        $criteria->add($this->itemFactory->getItem('id', $id));
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
                $fileExtension = pathinfo($filepath, PATHINFO_EXTENSION);
                $fileSize = filesize(TFISH_MEDIA_PATH . $content->media);
                $mimetypeList = $this->getListOfMimetypes();
                $mimetype = $mimetypeList[$fileExtension];

                // Must call session_write_close() first otherwise the script gets locked.
                session_write_close();
                
                // Output the header.
                $this->_outputHeader($filename, $fileExtension, $mimetype, $fileSize, $filepath);
                
            } else {
                return false;
            }
        } else {
            trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_WARNING);
            return false;
        }
    }
    
    private function _outputHeader($filename, $fileExtension, $mimetype, $fileSize, $filepath)
    {
        // Prevent caching
        header("Pragma: public");
        header("Expires: -1");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

        // Set file-specific headers.
        header('Content-Disposition: attachment; filename="' . $filename . '.'
                . $fileExtension . '"');
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
        $keyValues = $obj->convertObjectToArray();
        
        unset($keyValues['submissionTime']); // Submission time should not be overwritten.
        $zeroedProperties = $obj->getListOfZeroedProperties();

        foreach ($zeroedProperties as $property) {
            $keyValues[$property] = null;
        }

        $propertyWhitelist = $obj->getPropertyWhitelist();

        // Tags are stored in a separate table and must be handled in a separate query.
        unset($keyValues['tags']);
        
        // Validator is non-persistent and not stored in the database.
        unset($keyValues['validator']);

        // Load the saved object from the database. This will be used to make comparisons with the
        // current object state and facilitate clean up of redundant tags, parent references, and
        // image/media files.
        
        $savedObject = $this->getObject($cleanId);
        
        /**
         * Handle image / media files for existing objects.
         */
        if (!empty($savedObject)) {

            /**
             * Image property.
             */
            
            // 1. Check if there is an existing image file associated with this content object.
            $existingImage = $this->_checkImage($savedObject);

            // 2. Is this content type allowed to have an image property?
            if (!array_key_exists('image', $propertyWhitelist)) {
                $keyValues['image'] = '';
                if ($existingImage) {
                    $this->_deleteImage($existingImage);
                    $existingImage = '';
                }
            }
            
            // 3. Has an existing image file been flagged for deletion?
            if ($existingImage) {
                if (isset($_POST['deleteImage']) && !empty($_POST['deleteImage'])) {
                    $keyValues['image'] = '';
                    $this->_deleteImage($existingImage);
                    $existingImage = '';
                }
            }
            
            // 4. Check if a new image file has been uploaded by looking in $_FILES.
            if (array_key_exists('image', $propertyWhitelist)) {

                if (isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])) {
                    $filename = $this->validator->trimString($_FILES['image']['name']);
                    $cleanFilename = $this->fileHandler->uploadFile($filename, 'image');
                    
                    if ($cleanFilename) {
                        $keyValues['image'] = $cleanFilename;
                        
                        // Delete old image file, if any.
                        if ($existingImage) {
                            $this->_deleteImage($existingImage);
                        }
                    } else {
                        $keyValues['image'] = '';
                    }
                    
                } else { // No new image, use the existing file name.
                    $keyValues['image'] = $existingImage;
                }
            }

            // If the updated object has no image attached, or has been instructed to delete
            // attached image, delete any old image files.
            if ($existingImage &&
                    ((!isset($keyValues['image']) || empty($keyValues['image']))
                    || (isset($_POST['deleteImage']) && !empty($_POST['deleteImage'])))) {
                $this->_deleteImage($existingImage);
            }

            /**
             * Media property.
             */
            
            // 1. Check if there is an existing media file associated with this content object.
            $existingMedia = $this->_checkMedia($savedObject);
            
            // 2. Is this content type allowed to have a media property?
            if (!array_key_exists('media', $propertyWhitelist)) {
                $keyValues['media'] = '';
                $keyValues['format'] = '';
                $keyValues['fileSize'] = '';
                if ($existingMedia) {
                    $this->_deleteMedia($existingMedia);
                    $existingMedia = '';
                }
            }
            
            // 3. Has existing media been flagged for deletion?
            if ($existingMedia) {
                if (isset($_POST['deleteMedia']) && !empty($_POST['deleteMedia'])) {
                    $keyValues['media'] = '';
                    $keyValues['format'] = '';
                    $keyValues['fileSize'] = '';
                    $this->_deleteMedia($existingMedia);
                    $existingMedia = '';
                }
            }
            
            // 4. Process media file.
            if (array_key_exists('media', $propertyWhitelist)) {
                $cleanFilename = '';
                
                // Get a whitelist of permitted mimetypes.
                $mimetypeWhitelist = $obj->getListOfPermittedUploadMimetypes();
                
                // Get name of newly uploaded file (overwrites old one).
                if (isset($_FILES['media']['name']) && !empty($_FILES['media']['name'])) {
                    $filename = $this->validator->trimString($_FILES['media']['name']);
                    $cleanFilename = $this->fileHandler->uploadFile($filename, 'media'); 
                } else {
                    $cleanFilename = $existingMedia;
                }

                if ($cleanFilename) {
                    if (isset($_FILES['media']['name']) && !empty($_FILES['media']['name'])) {
                        $extension = mb_strtolower(pathinfo($cleanFilename, PATHINFO_EXTENSION), 'UTF-8');

                        // Set values of new media file.
                        $keyValues['media'] = $cleanFilename;
                        $keyValues['format'] = $mimetypeWhitelist[$extension];
                        $keyValues['fileSize'] = $_FILES['media']['size'];

                        // Delete any old media file.
                        if ($existingMedia) {
                            $this->_deleteMedia($existingMedia);
                            $existingMedia = '';
                        }
                    // No new media, use the existing file name. Still need to validate it as the
                    // content type may have changed.
                    } else {
                        if ($existingMedia) {
                            $keyValues['media'] = $existingMedia;
                            $keyValues['format'] = $obj->format;
                            $keyValues['fileSize'] = $obj->fileSize;
                        }
                    }           
                } else {
                    $keyValues['media'] = '';
                    $keyValues['format'] = '';
                    $keyValues['fileSize'] = '';

                    // Delete any old media file.
                    if ($existingMedia) {
                        $this->_deleteMedia($existingMedia);
                        $existingMedia = '';
                    }
                }
            }
        }

        // Update tags
        $result = $this->taglinkHandler->updateTaglinks($cleanId, $obj->type, $obj->tags);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_TAGLINK_UPDATE_FAILED, E_USER_NOTICE);
            return false;
        }
        
        // Check if this object used to be a collection. If it has been changed to something else
        // clean up any parental references to it.
        if ($keyValues['type'] !== 'TfCollection' && !empty($savedObject)) {
            $exCollection = $this->_checkExCollection($savedObject);
            
            if ($exCollection === true) {
                $result = $this->deleteParentalReferences($cleanId);
                
                if (!$result) {
                    trigger_error(TFISH_ERROR_PARENT_UPDATE_FAILED, E_USER_NOTICE);
                    return false;
                }
            }
        }

        // Update the content object.
        $result = $this->db->update('content', $cleanId, $keyValues);
        
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
