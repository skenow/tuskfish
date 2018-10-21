<?php
/**
 * TfSensorController class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
// Enable strict type declaration.
declare(strict_types=1);
if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");
/**
 * Controls basic machine sensor object operations (add, edit, delete and update). It encapsulates
 * the admin controller script functionality.
 * 
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 * @uses        TfSensorTypes Whitelist of sanctioned TfSensor subclasses.
 * @var         TfValidator $validator Instance of the Tuskfish data validator class.
 * @var         TfDatabase $db Instance of the Tuskfish database class.
 * @var         TfCriteriaFactory $criteriaFactory Instance of the Tuskfish criteria factory class.
 */
class TfSensorController
{
    
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $sensorHandler;
    protected $cache;
    protected $template;
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfSensorHandler $sensorHandler, TfCache $cache,
            TfTemplate $template)
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
        
        if (is_a($sensorHandler, 'TfSensorHandler')) {
            $this->sensorHandler = $sensorHandler;
        } else {
            trigger_error(TFISH_ERROR_NOT_SENSOR_HANDLER, E_USER_ERROR);
        }
        
        if (is_a($cache, 'TfCache')) {
            $this->cache = $cache;
        } else {
            trigger_error(TFISH_ERROR_NOT_CACHE, E_USER_ERROR);
        }
        
        if (is_a($template, 'TfTemplate')) {
            $this->template = $template;
        } else {
            trigger_error(TFISH_ERROR_NOT_TEMPLATE_OBJECT, E_USER_ERROR);
        }
    }
    
    public function getParentSelectOptions(TfMachineHandler $machineHandler)
    {
        if (!is_a($machineHandler, 'TfMachineHandler')) {
            trigger_error(TFISH_ERROR_NOT_MACHINE_HANDLER, E_USER_ERROR);
        }
        
        $criteria = $this->criteriaFactory->getCriteria();
        $criteria->setOrder('title');
        $criteria->setOrderType('ASC');
        $criteria->setSecondaryOrder('submissionTime');
        $criteria->setSecondaryOrderType('DESC');
        $machineList = $machineHandler->getListOfTitles($criteria);
        
        return array(0 => TFISH_ZERO_OPTION) + $machineList;
    }
    
    public function addSensor(TfMachineHandler $machineHandler)
    {
        $parentSelectOptions = $this->getParentSelectOptions($machineHandler);
        
        $this->template->sensorTypes = $this->sensorHandler->getSensorTypes();
        $this->template->protocols = $this->sensorHandler->getDataProtocols();
        $this->template->parentSelectOptions = $parentSelectOptions;
        $this->template->pageTitle = TFISH_SENSORS;
        $this->template->form = TFISH_MACHINES_MODULE_FORM_PATH . "sensorEntry.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function confirmDelete(int $id)
    {
        $cleanId = (int) $id;

        if ($this->validator->isInt($cleanId, 1)) {
            $this->template->pageTitle = TFISH_CONFIRM_DELETE;
            $this->template->sensor = $this->sensorHandler->getObject($cleanId);
            $this->template->form = TFISH_MACHINES_MODULE_FORM_PATH . "confirmSensorDelete.html";
            $this->template->tfMainContent = $this->template->render('form');
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function deleteSensor(int $id)
    {
        $cleanId = (int) $id;
        
        $result = $this->sensorHandler->delete($cleanId);

        if ($result) {
            $this->cache->flushCache();
            $this->template->pageTitle = TFISH_SUCCESS;
            $this->template->alertClass = 'alert-success';
            $this->template->message = TFISH_OBJECT_WAS_DELETED;
        } else {
            $this->template->pageTitle = TFISH_FAILED;
            $this->template->alertClass = 'alert-danger';
            $this->template->message = TFISH_OBJECT_DELETION_FAILED;
        }

        $this->template->backUrl = 'sensor.php';
        $this->template->form = TFISH_FORM_PATH . "response.html";
        $this->template->tfMainContent = $this->template->render('form');        
    }
    
    public function editSensor(int $id, TfMachineHandler $machineHandler)
    {        
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }

        $criteria = $this->criteriaFactory->getCriteria();
        $criteria->add($this->criteriaFactory->getItem('id', $cleanId));
        $statement = $this->db->select('sensor', $criteria);

        if (!$statement) {
            trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_NOTICE);
            header("Location: sensor.php");
        }
        
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $sensor = $this->sensorHandler->convertRowToObject($row, false);
        
        // Prepare parent select box.
        $parentSelectOptions = $this->getParentSelectOptions($machineHandler);

        // Assign to template.
        $this->template->pageTitle = TFISH_SENSOR_EDIT;
        $this->template->op = 'update'; // Critical to launch correct submission action.
        $this->template->action = TFISH_UPDATE;
        $this->template->sensor = $sensor;
        $this->template->sensorTypes = $this->sensorHandler->getsensorTypes();
        $this->template->protocols = $this->sensorHandler->getDataProtocols();
        $this->template->parentSelectOptions = $parentSelectOptions;
        $this->template->form = TFISH_MACHINES_MODULE_FORM_PATH . "sensorEdit.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function submitSensor(array $formData)
    {
        if (empty($formData['type'])) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }

        $cleanType = $this->validator->trimString($formData['type']);
        $typeWhitelist = $this->sensorHandler->getSensorTypes();

        if (!array_key_exists($cleanType, $typeWhitelist)) {
            trigger_error(TFISH_ERROR_NOT_SENSOR, E_USER_ERROR);
            exit;
        }

        $sensor = new $cleanType($this->validator);
        $sensor->loadPropertiesFromArray($formData, true);
        
        // Insert the object
        $result = $this->sensorHandler->insert($sensor);

        if ($result) {
            $this->cache->flushCache();
            $this->template->pageTitle = TFISH_SUCCESS;
            $this->template->alertClass = 'alert-success';
            $this->template->message = TFISH_OBJECT_WAS_INSERTED;
        } else {
            $this->template->title = TFISH_FAILED;
            $this->template->alertClass = 'alert-danger';
            $this->template->message = TFISH_OBJECT_INSERTION_FAILED;
        }

        $this->template->backUrl = 'sensor.php';
        $this->template->form = TFISH_FORM_PATH . "response.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function toggleOnlineStatus(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($id, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        $result = $this->sensorHandler->toggleOnlineStatus($cleanId);

        if ($result) {
            $this->cache->flushCache();
            $this->template->pageTitle = TFISH_SUCCESS;
            $this->template->alertClass = 'alert-success';
            $this->template->message = TFISH_OBJECT_WAS_UPDATED;
        } else {
            $this->template->pageTitle = TFISH_FAILED;
            $this->template->alertClass = 'alert-danger';
            $this->template->message = TFISH_OBJECT_UPDATE_FAILED;
        }

        $this->template->backUrl = 'sensor.php';
        $this->template->form = TFISH_FORM_PATH . "response.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function updateSensor(array $formData)
    {
        if (empty($formData['type'])) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }

        $type = $this->validator->trimString($formData['type']);
        $typeWhitelist = $this->sensorHandler->getSensorTypes();

        if (!array_key_exists($type, $typeWhitelist)) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }

        $sensor = new $type($this->validator);
        $sensor->loadPropertiesFromArray($formData, true);

        // As this object is being sent to storage, need to decode entities that got encoded for
        // display.
        if (isset($sensor->title)) {
            $sensor->setTitle(htmlspecialchars_decode($sensor->title, ENT_NOQUOTES));
        }
        
        // Properties that are used within attributes must have quotes encoded.        
        if (isset($sensor->metaTitle)) {
            $sensor->setMetaTitle(htmlspecialchars_decode($sensor->metaTitle, ENT_QUOTES));
        }
        
        if (isset($sensor->metaDescription)) {
            $sensor->setMetaDescription(htmlspecialchars_decode($sensor->metaDescription, ENT_QUOTES));
        }
        
        if (isset($sensor->seo)) {
            $sensor->setSeo(htmlspecialchars_decode($sensor->seo, ENT_QUOTES));
        }        

        // Update the database row and display a response.
        $result = $this->sensorHandler->update($sensor);

        if ($result) {
            $this->cache->flushCache();
            $this->template->pageTitle = TFISH_SUCCESS;
            $this->template->alertClass = 'alert-success';
            $this->template->message = TFISH_OBJECT_WAS_UPDATED;
            $this->template->id = $sensor->id;
        } else {
            $this->template->pageTitle = TFISH_FAILED;
            $this->template->alertClass = 'alert-danger';
            $this->template->message = TFISH_OBJECT_UPDATE_FAILED;
        }

        $this->template->backUrl = 'sensor.php';
        $this->template->form = TFISH_MACHINES_MODULE_FORM_PATH . "responseSensorEdit.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
}
