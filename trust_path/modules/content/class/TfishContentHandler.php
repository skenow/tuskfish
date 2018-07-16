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
    use TfishContentTypes;
    
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

        // Delete files associated with the image and media properties.
        $obj = $this->getObject($clean_id);
        
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
        $taglink_handler = new TfishTaglinkHandler();
        $result = $taglink_handler->deleteTaglinks($obj);
        
        if (!$result) {
            return false;
        }

        // If object is a collection delete related parent references in child objects.
        if ($obj->type === 'TfishCollection') {
            $criteria = new TfishCriteria();
            $criteria->add(new TfishCriteriaItem('parent', $clean_id));
            $result = TfishDatabase::updateAll('content', array('parent' => 0), $criteria);
            
            if (!$result) {
                return false;
            }
        }

        // Finally, delete the object.
        $result = TfishDatabase::delete('content', $clean_id);
        
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
        
        $criteria = new TfishCriteria();
        $criteria->add(new TfishCriteriaItem('parent', $clean_id));
        $result = TfishDatabase::updateAll('content', array('parent' => 0), $criteria);

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
            global $tfish_file_handler;
            return $tfish_file_handler->deleteFile('image/' . $filename);
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
            global $tfish_file_handler;
            return $tfish_file_handler->deleteFile('media/' . $filename);
        }
    }
    
    public function insert(object $obj)
    {
        $key_values = $obj->convertObjectToArray();
        $key_values['submission_time'] = time(); // Automatically set submission time.
        unset($key_values['id']); // ID is auto-incremented by the database on insert operations.
        unset($key_values['tags']);

        // Process image and media files before inserting the object, as related fields must be set.
        $property_whitelist = $obj->getPropertyWhitelist();
        
        if (array_key_exists('image', $property_whitelist) && !empty($_FILES['image']['name'])) {
            $filename = TfishDataValidator::trimString($_FILES['image']['name']);
            global $tfish_file_handler;
            $clean_filename = $tfish_file_handler->uploadFile($filename, 'image');
            
            if ($clean_filename) {
                $key_values['image'] = $clean_filename;
            }
        }

        if (array_key_exists('media', $property_whitelist) && !empty($_FILES['media']['name'])) {
            $filename = TfishDataValidator::trimString($_FILES['media']['name']);
            global $tfish_file_handler;
            $clean_filename = $tfish_file_handler->uploadFile($filename, 'media');
            
            if ($clean_filename) {
                $key_values['media'] = $clean_filename;
                global $tfish_file_handler;
                $mimetype_whitelist = $tfish_file_handler->getListOfPermittedUploadMimetypes();
                $extension = pathinfo($clean_filename, PATHINFO_EXTENSION);
                $key_values['format'] = $mimetype_whitelist[$extension];
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

        $criteria = new TfishCriteria();

        // Filter tags by type.
        if (isset($clean_type)) {
            $criteria->add(new TfishCriteriaItem('content_type', $clean_type));
        }

        // Put a check for online status in here.
        $statement = TfishDatabase::selectDistinct('taglink', $criteria, array('tag_id'));
        
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
    public function getCount(object $criteria = null)
    {
        if (!isset($criteria)) {
            $criteria = new TfishCriteria();
        }
        
        if ($criteria && !empty($criteria->limit)) {
            $limit = $criteria->limit;
            $criteria->setLimit(0);
        }
        
        $count = TfishDatabase::selectCount('content', $criteria);
        
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
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return array Array as id => title of content objects.
     */
    public function getListOfObjectTitles(object $criteria = null)
    {
        $content_list = array();
        $columns = array('id', 'title');

        if (!isset($criteria)) {
            $criteria = new TfishCriteria();
        }
        
        // Set default sorting order by submission time descending.
        if (!$criteria->order) {
            $criteria->setOrder('date');
            $criteria->setSecondaryOrder('submission_time');
            $criteria->setSecondaryOrderType('DESC');
        }

        $statement = TfishDatabase::select('content', $criteria, $columns);
        
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
        
        if (TfishDataValidator::isInt($clean_id, 1)) {
            $criteria = new TfishCriteria();
            $criteria->add(new TfishCriteriaItem('id', $clean_id));
            $statement = TfishDatabase::select('content', $criteria);
            
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
     * Get content objects, optionally matching conditions specified with a TfishCriteria object.
     * 
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return array Array of content objects.
     */
    public function getObjects(object $criteria = null)
    {
        $objects = array();
        
        if (!isset($criteria)) {
            $criteria = new TfishCriteria();
        }

        // Set default sorting order by submission time descending.        
        if (!$criteria->order) {
            $criteria->setOrder('date');
            $criteria->setSecondaryOrder('submission_time');
            $criteria->setSecondaryOrderType('DESC');
        }

        $statement = TfishDatabase::select('content', $criteria);
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

            $criteria = new TfishCriteria();
            
            foreach ($object_ids as $id) {
                $criteria->add(new TfishCriteriaItem('content_id', (int) $id), "OR");
                unset($id);
            }

            $statement = TfishDatabase::select('taglink', $criteria);

            if ($statement) {
                // Sort tag into multi-dimensional array indexed by content_id.
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $taglinks[$row['content_id']][] = $row['tag_id'];
                }

                // Assign the sorted tags to correct content objects.
                foreach ($taglinks as $content_id => $tags) {
                    $objects[$content_id]->setTags($tags);
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
        $clean_zero_option = TfishDataValidator::escapeForXss(TfishDataValidator::trimString($zero_option));
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
    public function getListOfRights()
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
        $criteria = new TfishCriteria();
        $criteria->add(new TfishCriteriaItem('type', 'TfishTag'));
        
        if ($clean_online_only) {
            $criteria->add(new TfishCriteriaItem('online', true));
        }

        $statement = TfishDatabase::select('content', $criteria, $columns);
        
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
        $criteria = new TfishCriteria();
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
            $clean_zero_option = TfishDataValidator::escapeForXss(TfishDataValidator::trimString($zero_option));
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
            $select_box .= ($key === $clean_selected) ? '<option value="' . TfishDataValidator::escapeForXss($key)
                    . '" selected>' . TfishDataValidator::escapeForXss($value) . '</option>' : '<option value="'
                . TfishDataValidator::escapeForXss($key) . '">' . TfishDataValidator::escapeForXss($value) . '</option>';
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
                $clean_filename = TFISH_URL . TfishDataValidator::escapeForXss($target_filename)
                        . '.php?tag_id=';
            }
        }

        $tag_list = $this->getTagList(false);
        $tag_links = array();
        
        foreach ($tags as $tag) {
            if (TfishDataValidator::isInt($tag, 1) && array_key_exists($tag, $tag_list)) {
                $tag_links[$tag] = '<a href="' . TfishDataValidator::escapeForXss($clean_filename . $tag) . '">'
                        . TfishDataValidator::escapeForXss($tag_list[$tag]) . '</a>';
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
    public function searchContent(object $tfish_preference, string $search_terms,
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
    private function _searchContent(object $tfish_preference, array $search_terms,
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
        
        $sql .= "AND `online` = 1 AND `type` != 'TfishBlock' ";
        $sql .= "ORDER BY `date` DESC, `submission_time` DESC ";
        $sql_count .= $sql;
        
        // Bind the search term values and execute the statement.
        $statement = TfishDatabase::preparedStatement($sql_count);
        
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
        $statement = TfishDatabase::preparedStatement($sql_search);
        
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
        $clean_id = TfishDataValidator::isInt($id, 1) ? (int) $id : false;
        $clean_filename = !empty($filename) ? TfishDataValidator::trimString($filename) : '';
        
        if ($clean_id) {
            $result = $this->_streamDownloadToBrowser($clean_id, $clean_filename);
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
        $criteria = new TfishCriteria();
        $criteria->add(new TfishCriteriaItem('id', $id));
        $statement = TfishDatabase::select('content', $criteria);
        
        if (!$statement) {
            trigger_error(TFISH_ERROR_NO_STATEMENT, E_USER_NOTICE);
            return false;
        }
        
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $content_handler = new TfishContentHandler();
        $content = $content_handler->convertRowToObject($row);
        
        if ($content && $content->online) {
            $media = isset($content->media) ? $content->media : false;
            
            if ($media && is_readable(TFISH_MEDIA_PATH . $content->media)) {
                ob_start();
                $filepath = TFISH_MEDIA_PATH . $content->media;
                $filename = empty($filename) ? pathinfo($filepath, PATHINFO_FILENAME) : $filename;
                $file_extension = pathinfo($filepath, PATHINFO_EXTENSION);
                $file_size = filesize(TFISH_MEDIA_PATH . $content->media);
                $mimetype_list = $this->getListOfMimetypes();
                $mimetype = $mimetype_list[$file_extension];

                // Must call session_write_close() first otherwise the script gets locked.
                session_write_close();

                // Prevent caching
                header("Pragma: public");
                header("Expires: -1");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

                // Set file-specific headers.
                header('Content-Disposition: attachment; filename="' . $filename . '.'
                        . $file_extension . '"');
                //header('Content-Type: application/octet-stream');
                header("Content-Type: " . $mimetype);
                header("Content-Length: " . $file_size);
                ob_clean();
                flush();
                readfile($filepath);
            } else {
                return false;
            }
        } else {
            trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_WARNING);
            return false;
        }
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
        return TfishDatabase::toggleBoolean($clean_id, 'content', 'online');
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
            $content_object->loadPropertiesFromArray($row, true);

            // Populate the tag property.
            if (isset($content_object->tags) && !empty($content_object->id)) {
                $tags = array();
                $criteria = new TfishCriteria();
                $criteria->add(new TfishCriteriaItem('content_id', (int) $content_object->id));
                $statement = TfishDatabase::select('taglink', $criteria, array('tag_id'));
                
                if ($statement) {
                    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                        $tags[] = $row['tag_id'];
                    }
                    $content_object->setTags($tags);
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
    public function update(object $obj)
    {
        $clean_id = TfishDataValidator::isInt($obj->id, 1) ? (int) $obj->id : 0;
        $key_values = $obj->convertObjectToArray();
        
        unset($key_values['submission_time']); // Submission time should not be overwritten.
        $zeroed_properties = $obj->getListOfZeroedProperties();

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
                    global $tfish_file_handler;
                    $clean_filename = $tfish_file_handler->uploadFile($filename, 'image');
                    
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
                global $tfish_file_handler;
                $mimetype_whitelist = $tfish_file_handler->getListOfPermittedUploadMimetypes();
                
                // Get name of newly uploaded file (overwrites old one).
                if (isset($_FILES['media']['name']) && !empty($_FILES['media']['name'])) {
                    $filename = TfishDataValidator::trimString($_FILES['media']['name']);
                    global $tfish_file_handler;
                    $clean_filename = $tfish_file_handler->uploadFile($filename, 'media'); 
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
        $result = TfishDatabase::update('content', $clean_id, $key_values);
        
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
    private function _checkExCollection(object $obj)
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
    private function _checkImage(object $obj)
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
    private function _checkMedia(object $obj)
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
        return TfishDatabase::updateCounter($clean_id, 'content', 'counter');
    }

}