<?php

/**
 * Expert handler class file.
 * 
 * @copyright   Simon Wilkinson 2018+(https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     experts
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Manipulates expert (TfExpert) objects.
 *
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     experts
 */

class TfExpertHandler
{
    
    use TfExpertTrait;
    
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $fileHandler;
    protected $taglinkHandler;
    
    public function __construct(TfValidator $validator, TfDatabase $db, TfCriteriaFactory
            $criteriaFactory, TfFileHandler $fileHandler, TfTaglinkHandler $taglinkHandler)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator; 
        } else {
            trigger_error(TFISH_ERROR_NOT_VALIDATOR, E_USER_ERROR);
        }
        
        if (is_a($db, 'TfDatabase')) {
            $this->db = $db; 
        } else {
            trigger_error(TFISH_ERROR_NOT_DATABASE, E_USER_ERROR);
        }
        
        if (is_a($criteriaFactory, 'TfCriteriaFactory')) {
            $this->criteriaFactory = $criteriaFactory; 
        } else {
            trigger_error(TFISH_ERROR_NOT_CRITERIA_FACTORY, E_USER_ERROR);
        }
        
        if (is_a($fileHandler, 'TfFileHandler')) {
            $this->fileHandler = $fileHandler; 
        } else {
            trigger_error(TFISH_ERROR_NOT_FILE_HANDLER, E_USER_ERROR);
        }
        
        if (is_a($taglinkHandler, 'TfTaglinkHandler')) {
            $this->taglinkHandler = $taglinkHandler; 
        } else {
            trigger_error(TFISH_ERROR_NOT_TAGLINK_HANDLER, E_USER_ERROR);
        }
    }
    
    /**
     * Convert a database content row to a corresponding content object.
     * 
     * Only use this function to convert single objects, as it does a separate query to look up
     * the associated taglinks. Running it through a loop will therefore consume a lot of resources.
     * To convert multiple objects, load them directly into the relevant class files, prepare a
     * buffer of tags using getTags() and loop through the objects referring to the buffer rather
     * than hitting the database every time.
     * 
     * @param array $row Array of result set from database.
     * @return object|bool Content object on success, false on failure.
     */
    public function convertRowToObject(array $row)
    {
        if (empty($row) || !$this->validator->isArray($row)) {
            return false;
        }
        
        if (isset($row['type']) && $row['type'] === 'TfExpert') {
            $expertObject = new $row['type']($this->validator);
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
        }
        
        // Populate the object from the $row using whitelisted properties.
        if ($expertObject) {
            $expertObject->loadPropertiesFromArray($row, false);

            // Populate the tag property.
            if (isset($expertObject->tags) && !empty($expertObject->id)) {
                $expertObject->setTags($this->loadTagsForObject($expertObject->id));
            }

            return $expertObject;
        }

        return false;
    }
    
    /**
     * Delete a single object from the expert table.
     * 
     * @param int $id ID of expert object to delete.
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

        // Delete associated taglinks. If this object is a tag, delete taglinks referring to it.
        $result = $this->taglinkHandler->deleteTaglinks($obj);
        
        if (!$result) {
            return false;
        }

        // Finally, delete the object.
        $result = $this->db->delete('expert', $cleanId);
        
        if (!$result) {
            return false;
        }

        return true;
    }
    
    /**
     * Generates a country select box.
     * 
     * @param int $selected The currently option.
     * @param string $zeroOption The text to display in the zero option of the select box.
     * @return string HTML select box.
     */
    public function getCountrySelectBox(int $selected = 0, string $zeroOption = TFISH_EXPERTS_SELECT_STATE)
    {
        $cleanSelected = (int) $selected;
        $cleanZeroOption = $this->validator->trimString($zeroOption);
        $options = $this->getCountryList();
        
        if (!$this->validator->isInt($cleanSelected, 0)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        if ($cleanZeroOption) {
            $options[0] = $cleanZeroOption;
        }
        
        $selectBox = '<select class="form-control custom-select" name="state" id="state" '
                . 'onchange="this.form.submit()">';
        
        foreach ($options as $key => $value) {
            if ($key === $cleanSelected) {
                $selectBox .= '<option value="' . $key . '" selected>' . $value . '</option>';
            } else {
                $selectBox .= '<option value="' . $key . '">' . $value . '</option>';
            }
        }

        $selectBox .= '</select>';

        return $selectBox;
    }
    
    /**
     * Returns an array of tag IDs for a given expert object.
     * 
     * @param int $id ID of expert object.
     * @return array Array of tag IDs.
     */
    protected function loadTagsForObject(int $id)
    {
        $cleanId = (int) $id;      
        $tags = array();
        
        $criteria = $this->criteriaFactory->getCriteria();
        $criteria->add($this->criteriaFactory->getItem('contentId', $cleanId));
        $statement = $this->db->select('taglink', $criteria, array('tagId'));

        if ($statement) {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $tags[] = $row['tagId'];
            }
            
            return $tags;
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }
    }
    
    /**
     * Retrieves a single object based on its ID.
     * 
     * @param int $id ID of expert object.
     * @return TfExpert|bool $object Expert object on success, false on failure.
     */
    public function getObject(int $id)
    {
        $cleanId = (int) $id;
        $row = $object = '';
        
        if ($this->validator->isInt($cleanId, 1)) {
            $criteria = $this->criteriaFactory->getCriteria();
            $criteria->add($this->criteriaFactory->getItem('id', $cleanId));
            $statement = $this->db->select('expert', $criteria);
            
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
     * Get expert objects, optionally matching conditions specified with a TfCriteria object.
     * 
     * @param TfCriteria $criteria Query composer object used to build conditional database query.
     * @return array Array of expert objects.
     */
    public function getObjects(TfCriteria $criteria = null)
    {
        $objects = array();
        
        if (isset($criteria) && !is_a($criteria, 'TfCriteria')) {
            trigger_error(TFISH_ERROR_NOT_CRITERIA_OBJECT, E_USER_ERROR);
        }
        
        if (!isset($criteria)) {
            $criteria = $this->criteriaFactory->getCriteria();
        }

        // Set default sorting order by submission time descending.        
        if (!$criteria->order) {
            $criteria->setOrder('lastName');
            $criteria->setOrderType('ASC');
        }

        $statement = $this->db->select('expert', $criteria);
        
        if ($statement) {

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $object = new $row['type']($this->validator);
                $object->loadPropertiesFromArray($row, false);
                $objects[$object->id] = $object;
                unset($object);
            }            

            unset($statement);
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }

        return $objects;
    }
    
    /**
     * Converts an array of tagIds into an array of tag links with an arbitrary local target file.
     * 
     * Note that the filename may only consist of alphanumeric characters and underscores. Do not
     * include the file extension (eg. use 'experts' instead of 'experts.php'. The base URL of the
     * site will be prepended and .php plus the tagId will be appended.
     * 
     * @param array $tags Array of tag IDs.
     * @param string $targetFilename Name of file for tag links to point at.
     * @return array Array of HTML tag links.
     */
    public function makeTagLinks(array $tags, string $targetFilename = '')
    {
        if (!$this->validator->isArray($tags)) {
            trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
        }
        
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
        $criteria->add($this->criteriaFactory->getItem('type', 'TfTag'));
        
        if ($cleanOnlineOnly) {
            $criteria->add($this->criteriaFactory->getItem('online', true));
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
     * Inserts an expert object into the database.
     * 
     * @param TfExpert $obj An expert object or subclass.
     * @return bool True on success, false on failure.
     */
    public function insert(TfExpert $obj)
    {
        if (!is_a($obj, 'TfExpert')) {
            trigger_error(TFISH_ERROR_NOT_EXPERT, E_USER_ERROR);
        }

        $keyValues = $obj->convertObjectToArray();
        unset($keyValues['validator']); // Injected dependency, not resident in database.
        unset($keyValues['id']); // ID is auto-incremented by the database on insert operations.
        unset($keyValues['tags']);
        $keyValues['submissionTime'] = time(); // Automatically set submission time.
        $keyValues['lastUpdated'] = time();
        $keyValues['expiresOn'] = 0; // Not in use.
        
        // Process associated image.
        $propertyWhitelist = $obj->getPropertyWhitelist();
        $keyValues['image'] = $this->uploadImage($propertyWhitelist);

        // Insert the object into the database.
        $result = $this->db->insert('expert', $keyValues);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
            return false;
        } else {
            $expertId = $this->db->lastInsertId();
        }
        
        unset($keyValues, $result);
        
        // Insert the tags associated with this object.
        $this->insertTagsForObject($expertId, $obj);

        return true;
    }
    /**
     * Moves an uploaded image to the /uploads/image directory and returns the filename.
     * 
     * This is a helper function for insert().
     * 
     * @param array $propertyWhitelist List of permitted object properties.
     * @return string Filename.
     */
    private function uploadImage(array $propertyWhitelist)
    {
        if (array_key_exists('image', $propertyWhitelist) && !empty($_FILES['image']['name'])) {
            $filename = $this->validator->trimString($_FILES['image']['name']);
            $cleanFilename = $this->fileHandler->uploadFile($filename, 'image');
            
            if ($cleanFilename) {
                return $cleanFilename;
            }
        }
        
        return '';
    }
    
    /**
     * Insert the tags associated with an expert object.
     * 
     * This is a helper function for insert().
     * 
     * Tags are stored separately in the taglinks table. Tags are assembled in one batch before
     * proceeding to insertion; so if one fails a range check all should fail. If the
     * lastInsertId could not be retrieved, then halt execution because this data
     * is necessary in order to correctly assign taglinks to content objects.
     * 
     * @return boolean
     */
    private function insertTagsForObject(int $expertId, TfExpert $obj)
    {
        if (isset($obj->tags) and $this->validator->isArray($obj->tags)) {
            if (!$expertId) {
                trigger_error(TFISH_ERROR_NO_LAST_INSERT_ID, E_USER_ERROR);
                exit;
            }

            $result = $this->taglinkHandler->insertTaglinks($expertId, $obj->type, $obj->module,
                    $obj->tags);
            if (!$result) {
                return false;
            }
        }
    }
    
    /**
     * Updates an expert object in the database.
     * 
     * @param TfExpert $obj An expert object or subclass.
     * @return bool True on success, false on failure.
     */
    public function update(TfExpert $obj)
    {
        if (!is_a($obj, 'TfExpert')) {
            trigger_error(TFISH_ERROR_NOT_CONTENT_OBJECT, E_USER_ERROR);
        }
        
        $cleanId = $this->validator->isInt($obj->id, 1) ? (int) $obj->id : 0;

        $obj->updateLastUpdated();
        $keyValues = $obj->convertObjectToArray();
        unset($keyValues['submissionTime']); // Submission time should not be overwritten.

        $propertyWhitelist = $obj->getPropertyWhitelist();

        // Unset properties that are not resident in the expert table.
        unset($keyValues['tags']);
        unset($keyValues['validator']);

        // Load the saved object from the database. This will be used to make comparisons with the
        // current object state and facilitate clean up of redundant tags, and image files.
        $savedObject = $this->getObject($cleanId);
        
        // Update image file for existing objects.
        if (!empty($savedObject)) {
            $keyValues = $this->updateImage($propertyWhitelist, $keyValues, $savedObject);
        }

        // Update tags.
        $result = $this->taglinkHandler->updateTaglinks($cleanId, $obj->type, $obj->module,
                $obj->tags);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_TAGLINK_UPDATE_FAILED, E_USER_NOTICE);
            return false;
        }

        // Update the expert object.
        $result = $this->db->update('expert', $cleanId, $keyValues);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
        }
        
        unset($result);

        return true;
    }
    
    /**
     * Update the image property for an existing expert object.
     * 
     * This is a helper method for update().
     * 
     * @param array $propertyWhitelist Whitelist of permitted object properties.
     * @param array $keyValues Updated values of object properties from form data.
     * @param TfExpert $savedObject The existing (not updated) object as presently saved in
     * the database.
     * @return array $keyValues Object properties with updated image property.
     */
    private function updateImage(array $propertyWhitelist, array $keyValues,
            TfExpert $savedObject)
    {
        // 1. Check if there is an existing image file associated with this expert object.
        $existingImage = $this->checkImage($savedObject);

        // 2. Is this expert type allowed to have an image property?
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
        
        return $keyValues;
    }

    /**
     * Check if an existing object has an associated image file upload.
     * 
     * @param TfExpert $obj The expert object to be tested.
     * @return string Filename of associated image property.
     */
    private function checkImage(TfExpert $obj)
    {        
        if (!empty($obj->image)) {
            return $obj->image;
        }

        return '';
    }
    
    /**
     * Deletes an uploaded image file associated with an object.
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
     * Toggle the online status of an expert object.
     * 
     * @param int $id ID of expert object.
     * @return boolean True on success, false on failure.
     */
    public function toggleOnlineStatus(int $id)
    {
        $cleanId = (int) $id;
        return $this->db->toggleBoolean($cleanId, 'expert', 'online');
    }

    /**
     * Increment a given expert object counter field by one.
     * 
     * @param int $id ID of expert object.
     */
    public function updateCounter(int $id)
    {
        $cleanId = (int) $id;
        return $this->db->updateCounter($cleanId, 'expert', 'counter');
    }
    
}
