<?php

/**
 * TfMachineHandler class file.
 * 
 * @copyright   Simon Wilkinson 2018+(https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@tuskfish.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Manipulates remote machine objects (TfMachine), typically Internet of Things devices.
 *
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
class TfMachineHandler
{
    
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $fileHandler;
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfFileHandler $fileHandler)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator; 
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (is_a($db, 'TfDatabase')) {
            $this->db = $db; 
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (is_a($criteriaFactory, 'TfCriteriaFactory')) {
            $this->criteriaFactory = $criteriaFactory; 
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (is_a($fileHandler, 'TfFileHandler')) {
            $this->fileHandler = $fileHandler; 
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
    }
    
    /**
     * Convert a database content row to a corresponding machine object.
     * 
     * @param array $row Array of result set from database.
     * @return object|bool Machine object on success, false on failure.
     */
    public function convertRowToObject(array $row)
    {
        if (empty($row) || !$this->validator->isArray($row)) {
            return false;
        }
        
        $machine = new TfMachine($this->validator);
        
        if ($machine) {
            $machine->loadPropertiesFromArray($row, true);

            return $machine;
        }

        return false;
    }
    
    /**
     * Delete a single object from the machine table.
     * 
     * @param int $id ID of machine object to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            return false;
        }

        $result = $this->db->delete('machine', $cleanId);
        
        if (!$result) {
            return false;
        }

        return true;
    }
    
    /**
     * Count machine objects optionally matching conditions specified with a TfCriteria object.
     * 
     * @param TfCriteria $criteria Query composer object used to build conditional database query.
     * @return int $count Number of objects matching conditions.
     */
    public function getCount(TfCriteria $criteria = null)
    {
        if (isset($criteria) && !is_a($criteria, 'TfCriteria')) {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (!isset($criteria)) {
            $criteria = $this->criteriaFactory->getCriteria();
        }
        
        $count = $this->db->selectCount('machine', $criteria);

        return $count;
    }
    
    /**
     * Returns a list of machine object titles with ID as key.
     * 
     * @param TfCriteria $criteria Query composer object used to build conditional database query.
     * @return array Array as id => title of machine objects.
     */
    public function getListOfTitles(TfCriteria $criteria = null)
    {
        $machineList = array();
        $columns = array('id', 'title');

        if (isset($criteria) && !is_a($criteria, 'TfCriteria')) {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (!isset($criteria)) {
            $criteria = $this->criteriaFactory->getCriteria();
        }
        
        // Set default sorting order by submission time descending.
        if (!$criteria->order) {
            $criteria->setOrder('submissionTime');
            $criteria->setOrderType('DESC');
        }

        $statement = $this->db->select('machine', $criteria, $columns);
        
        if ($statement) {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $machineList[$row['id']] = $row['title'];
            }
            unset($statement);
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }

        return $machineList;
    }
    
    /**
     * Retrieves a single machine based on its ID.
     * 
     * @param int $id ID of machine object.
     * @return TfMachine|bool $object Machine object on success, false on failure.
     */
    public function getObject(int $id)
    {
        $cleanId = (int) $id;
        $row = $object = '';
        
        if ($this->validator->isInt($cleanId, 1)) {
            $criteria = $this->criteriaFactory->getCriteria();
            $criteria->add($this->criteriaFactory->getItem('id', $cleanId));
            $statement = $this->db->select('machine', $criteria);
            
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
     * Get machine objects, optionally matching conditions specified with a TfCriteria object.
     * 
     * @param TfCriteria $criteria Query composer object used to build conditional database query.
     * @return array Array of machine objects.
     */
    public function getObjects(TfCriteria $criteria = null)
    {
        $objects = array();
        
        if (isset($criteria) && !is_a($criteria, 'TfCriteria')) {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (!isset($criteria)) {
            $criteria = $this->criteriaFactory->getCriteria();
        }

        // Set default sorting order by submission time descending.        
        if (!$criteria->order) {
            $criteria->setOrder('submissionTime');
            $criteria->setOrderType('DESC');
        }

        $statement = $this->db->select('machine', $criteria);
        if ($statement) {

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $object->loadPropertiesFromArray($row, true);
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
     * Inserts a machine object into the database.
     * 
     * @param TfMachine $obj A machine object or subclass.
     * @return bool True on success, false on failure.
     */
    public function insert(TfMachine $obj)
    {
        if (!is_a($obj, 'TfMachine')) {
            trigger_error(TFISH_ERROR_NOT_MACHINE, E_USER_ERROR);
        }

        $keyValues = $obj->convertObjectToArray();
        unset($keyValues['validator']); // Injected dependency, not resident in database.
        unset($keyValues['id']); // ID is auto-incremented by the database on insert operations.
        $keyValues['submissionTime'] = time(); // Automatically set submission time.

        // Insert the object into the database.
        $result = $this->db->insert('machine', $keyValues);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
            return false;
        } else {
            $contentId = $this->db->lastInsertId();
        }
        
        unset($keyValues, $result);

        return true;
    }
    
    /**
     * Toggle the online status of a machine object.
     * 
     * @param int $id ID of machine object.
     * @return boolean True on success, false on failure.
     */
    public function toggleOnlineStatus(int $id)
    {
        $cleanId = (int) $id;
        return $this->db->toggleBoolean($cleanId, 'machine', 'online');
    }
    
    /**
     * Updates a machine object in the database.
     * 
     * @param TfMachine $obj A machine object or subclass.
     * @return bool True on success, false on failure.
     */
    public function update(TfMachine $obj)
    {
        if (!is_a($obj, 'TfMachine')) {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        $cleanId = $this->validator->isInt($obj->id, 1) ? (int) $obj->id : 0;
        
        $obj->updateLastUpdated();
        $keyValues = $obj->convertObjectToArray();
        unset($keyValues['submissionTime']); // Submission time should not be overwritten.

        $propertyWhitelist = $obj->getPropertyWhitelist();
        unset($keyValues['validator']);
        $savedObject = $this->getObject($cleanId);
        
        // Update the machine object.
        $result = $this->db->update('machine', $cleanId, $keyValues);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
        }
        
        unset($result);

        return true;
    }
    
}
