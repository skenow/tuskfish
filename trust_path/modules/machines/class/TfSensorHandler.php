<?php

/**
 * TfSensorHandler class file.
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
 * Manipulates sensor objects (TfSensor).
 *
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
class TfSensorHandler
{
    
    use TfSensorTypes;
    use TfDataProtocols;
    
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $fileHandler;
    
    /**
     * Constructor.
     * 
     * @param TfValidator $validator Instance of the Tuskfish validator class.
     * @param TfDatabase $db Instance of the Tuskfish database class.
     * @param TfCriteriaFactory $criteriaFactory Instance of the Tuskfish criteria factory class.
     * @param TfFileHandler $fileHandler Instance of the Tuskfish file handler class.
     * @param TfTaglinkHandler $taglinkHandler Instance of the Tuskfish taglink handler class.
     */
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
     * Convert a database row to a corresponding sensor object.
     * 
     * @param array $row Array of result set from database.
     * @return object|bool Sensor object on success, false on failure.
     */
    public function convertRowToObject(array $row)
    {
        if (empty($row) || !$this->validator->isArray($row)) {
            return false;
        }

        $sensor = $this->create($row['type']);        
        
        if ($sensor) {
            $sensor->loadPropertiesFromArray($row, true);

            return $sensor;
        }

        return false;
    }
    
    /**
     * Create a new sensor object.
     * 
     * @return \TfSensor A new sensor object.
     */
    public function create(string $type)
    {
        $cleanType = $this->validator->trimString($type);
        $typeWhitelist = $this->getSensorTypes();
        
        if (empty($cleanType) || !array_key_exists($cleanType, $typeWhitelist)) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
        }
        
        return new $cleanType($this->validator);
    }
    
    /**
     * Delete a single object from the sensor table.
     * 
     * @param int $id ID of sensor object to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            return false;
        }

        $result = $this->db->delete('sensor', $cleanId);
        
        if (!$result) {
            return false;
        }

        return true;
    }
    
    /**
     * Count sensor objects optionally matching conditions specified with a TfCriteria object.
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
        
        $count = $this->db->selectCount('sensor', $criteria);

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
        $sensorList = array();
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

        $statement = $this->db->select('sensor', $criteria, $columns);
        
        if ($statement) {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $sensorList[$row['id']] = $row['title'];
            }
            unset($statement);
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }

        return $sensorList;
    }
    
    /**
     * Retrieves a single sensor based on its ID.
     * 
     * @param int $id ID of sensor object.
     * @return TfSensorObject|bool $object Sensor object on success, false on failure.
     */
    public function getObject(int $id)
    {
        $cleanId = (int) $id;
        $row = $object = '';
        
        if ($this->validator->isInt($cleanId, 1)) {
            $criteria = $this->criteriaFactory->getCriteria();
            $criteria->add($this->criteriaFactory->getItem('id', $cleanId));
            $statement = $this->db->select('sensor', $criteria);
            
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
     * Get sensor objects, optionally matching conditions specified with a TfCriteria object.
     * 
     * @param TfCriteria $criteria Query composer object used to build conditional database query.
     * @return array Array of sensor objects.
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

        $statement = $this->db->select('sensor', $criteria);
        
        if ($statement) {

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $object = $this->create('TfSensor');
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
     * Inserts a sensor object into the database.
     * 
     * @param TfSensor $obj A sensor object subclass.
     * @return bool True on success, false on failure.
     */
    public function insert(TfSensor $obj)
    {
        if (!is_a($obj, 'TfSensor')) {
            trigger_error(TFISH_ERROR_NOT_SENSOR, E_USER_ERROR);
        }
        
        $keyValues = $obj->convertObjectToArray();
        $keyValues['submissionTime'] = time(); // Automatically set submission time.
        $keyValues['lastUpdated'] = 0; // Initiate lastUpdated at 0.
        $keyValues['expiresOn'] = 0; // Initate expiresOn at 0;
        unset($keyValues['id']); // ID is auto-incremented by the database on insert operations.
        unset($keyValues['validator']); // Injected dependency, not resident in database.

        // Insert the object into the database.
        $result = $this->db->insert('sensor', $keyValues);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
            return false;
        } else {
            $sensorId = $this->db->lastInsertId();
        }
        
        unset($keyValues, $result);

        return true;
    }
    
    /**
     * Toggle the online status of a sensor object.
     * 
     * @param int $id ID of sensor object.
     * @return boolean True on success, false on failure.
     */
    public function toggleOnlineStatus(int $id)
    {
        $cleanId = (int) $id;
        return $this->db->toggleBoolean($cleanId, 'sensor', 'online');
    }
    
    /**
     * Updates a sensor object in the database.
     * 
     * @param TfSensor $obj A sensor object or subclass.
     * @return bool True on success, false on failure.
     */
    public function update(TfSensor $obj)
    {
        if (!is_a($obj, 'TfSensor')) {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        $cleanId = $this->validator->isInt($obj->id, 1) ? (int) $obj->id : 0;
        
        $obj->updateLastUpdated();
        $keyValues = $obj->convertObjectToArray();
        unset($keyValues['submissionTime']); // Submission time should not be overwritten.

        $propertyWhitelist = $obj->getPropertyWhitelist();
        unset($keyValues['validator']);
        $savedObject = $this->getObject($cleanId);
        
        // Update the sensor object.
        $result = $this->db->update('sensor', $cleanId, $keyValues);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
        }
        
        unset($result);

        return true;
    }
    
}
