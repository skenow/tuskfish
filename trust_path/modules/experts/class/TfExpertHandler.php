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

class tfExpertHandler
{
    
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
    
    public function getCountryList()
    {
        return array(
            0 => TFISH_ZERO_OPTION,
            1 => "Australia",
            2 => "Bangladesh",
            3 => "Cambodia",
            4 => "China",
            5 => "Hong Kong SAR (China)",
            6 => "India",
            7 => "Indonesia",
            8 => "I.R. Iran",
            9 => "Lao PDR",
            10 => "Malaysia",
            11 => "Maldives",
            12 => "Myanmar",
            13 => "Nepal",
            14 => "Pakistan",
            15 => "Philippines",
            16 => "Sri Lanka",
            17 => "Thailand",
            18 => "Vietnam"
        );
    }
    
    /**
     * Returns an array of known / permitted salutations.
     * 
     * @return array List of salutations as key => value pairs.
     */
    public function getSalutationList()
    {
        return array(
            0 => TFISH_ZERO_OPTION,
            1 => "Dr",
            2 => "Prof.",
            3 => "Mr",
            4 => "Mrs",
            5 => "Ms"
        );
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
