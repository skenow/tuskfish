<?php

/**
 * Expert handler class file.
 * 
 * @copyright   Simon Wilkinson 2018+(https://isengard.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Your name <simon@isengard.biz>
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
 * @copyright   Simon Wilkinson 2018+ (https://isengard.biz)
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
            $expertObject->loadPropertiesFromArray($row, true);

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

        // Insert the object into the database.
        $result = $this->db->insert('expert', $keyValues);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
            return false;
        } else {
            $expertId = $this->db->lastInsertId();
        }
        
        // Process associated image.
        $propertyWhitelist = $obj->getPropertyWhitelist();
        $keyValues['image'] = $this->uploadImage($propertyWhitelist);
        
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
    
}
