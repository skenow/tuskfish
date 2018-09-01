<?php

/**
 * TfMachineController class file.
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
 * Controls common operations on machine (TfMachine) objects.
 *
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
class TfMachineController
{
    
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $machineHandler;
    protected $cache;
    protected $template;
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfMachineHandler $machineHandler, TfCache $cache,
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
        
        if (is_a($machineHandler, 'TfMachineHandler')) {
            $this->machineHandler = $machineHandler;
        } else {
            trigger_error(TFISH_ERROR_NOT_MACHINE_HANDLER, E_USER_ERROR);
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
    
    public function addMachine()
    {
        $this->template->pageTitle = TFISH_MACHINES;
        $this->template->form = TFISH_MACHINES_MODULE_FORM_PATH . "machineEntry.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function confirmDelete(int $id)
    {
        $cleanId = (int) $id;

        if ($this->validator->isInt($cleanId, 1)) {
            $this->template->pageTitle = TFISH_CONFIRM_DELETE;
            $this->template->machine = $this->machineHandler->getObject($cleanId);
            $this->template->form = TFISH_MACHINES_MODULE_FORM_PATH . "confirmMachineDelete.html";
            $this->template->tfMainContent = $this->template->render('form');
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function deleteMachine(int $id)
    {
        $cleanId = (int) $id;
        
        $result = $this->machineHandler->delete($cleanId);

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

        $this->template->backUrl = 'machine.php';
        $this->template->form = TFISH_FORM_PATH . "response.html";
        $this->template->tfMainContent = $this->template->render('form');        
    }
    
    public function editMachine(int $id)
    {        
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }

        $criteria = $this->criteriaFactory->getCriteria();
        $criteria->add($this->criteriaFactory->getItem('id', $cleanId));
        $statement = $this->db->select('machine', $criteria);

        if (!$statement) {
            trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_NOTICE);
            header("Location: machine.php");
        }
        
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $machine = $this->machineHandler->convertRowToObject($row, false);

        // Assign to template.
        $this->template->pageTitle = TFISH_MACHINE_EDIT;
        $this->template->op = 'update'; // Critical to launch correct submission action.
        $this->template->action = TFISH_UPDATE;
        $this->template->machine = $machine;
        $this->template->form = TFISH_MACHINES_MODULE_FORM_PATH . "machineEdit.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function submitMachine(array $formData)
    {
        $machine = new TfMachine($this->validator);
        $machine->loadPropertiesFromArray($formData);
        
        // Insert the object
        $result = $this->machineHandler->insert($machine);

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

        $this->template->backUrl = 'machine.php';
        $this->template->form = TFISH_FORM_PATH . "response.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function toggleOnlineStatus(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($id, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        $result = $this->machineHandler->toggleOnlineStatus($cleanId);

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

        $this->template->backUrl = 'machine.php';
        $this->template->form = TFISH_FORM_PATH . "response.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function updateMachine(array $formData)
    {
        $machine = new TfMachine($this->validator);
        $machine->loadPropertiesFromArray($formData);

        // As this object is being sent to storage, need to decode entities that got encoded for
        // display.
        if (isset($machine->title)) {
            $machine->title = htmlspecialchars_decode($machine->title, ENT_NOQUOTES);
        }

        // Properties that are used within attributes must have quotes encoded.
        $fieldsToDecode = array('metaTitle', 'seo', 'metaDescription');

        foreach ($fieldsToDecode as $field) {
            if (isset($machine->field)) {
                $machine->$field = htmlspecialchars_decode($machine->field, ENT_QUOTES);
            }
        }

        // Update the database row and display a response.
        $result = $this->machineHandler->update($machine);

        if ($result) {
            $this->cache->flushCache();
            $this->template->pageTitle = TFISH_SUCCESS;
            $this->template->alertClass = 'alert-success';
            $this->template->message = TFISH_OBJECT_WAS_UPDATED;
            $this->template->id = $machine->id;
        } else {
            $this->template->pageTitle = TFISH_FAILED;
            $this->template->alertClass = 'alert-danger';
            $this->template->message = TFISH_OBJECT_UPDATE_FAILED;
        }

        $this->template->backUrl = 'machine.php';
        $this->template->form = TFISH_MACHINES_MODULE_FORM_PATH . "responseMachineEdit.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
}
