<?php

/**
 * Admin controller script for sensors in the Machines module.
 * 
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     machines
 */
// Enable strict type declaration.
declare(strict_types=1);

// Boot! Set file paths, preferences and connect to database.
require_once "../mainfile.php";
require_once TFISH_ADMIN_PATH . "tfAdminHeader.php";
require_once TFISH_MODULE_PATH . "machines/tfMachinesHeader.php";

// Validate input parameters.
$cleanId = (int) ($_REQUEST['id'] ?? 0);
$cleanStart = (int) ($_GET['start'] ?? 0);
$cleanOnline = isset($_GET['online']) ? (int) $_GET['online'] : null;
$cleanType = isset($_GET['type']) && !empty($_GET['type']) ? $tfValidator->trimString($_GET['type']) : '';
$cleanToken = isset($_POST['token']) ? $tfValidator->trimString($_POST['token']) : '';
$op = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : false;

// Specify the admin theme and the template to be used to preview content (user side template).
if ($op === 'view') {
    $tfTemplate->setTheme('default');
} else {
    $tfTemplate->setTheme('admin');
}

// Permitted options.
$optionsWhitelist = array('', 'add', 'confirmDelete', 'delete', 'edit', 'submit', 'toggle',
    'update', 'view');

if (!in_array($op, $optionsWhitelist)) {
    exit;
}

// Cross-site request forgery check.
/*if (!in_array($op, $optionsWhitelist, true)) {
    TfSession::validateToken($cleanToken);
}*/

$sensorHandler = new TfSensorHandler($tfValidator, $tfDatabase, $tfCriteriaFactory, $tfFileHandler);

switch ($op) {
    case "add":
        $tfTemplate->sensorTypes = $sensorHandler->getSensorTypes();
        $tfTemplate->protocols = $sensorHandler->getDataProtocols();
        $tfTemplate->parentSelectOptions = array(0 => '---');
        $tfTemplate->pageTitle = TFISH_SENSORS;
        $tfTemplate->form = TFISH_MACHINES_MODULE_FORM_PATH . "sensorEntry.html";
        $tfTemplate->tfMainContent = $tfTemplate->render('form');
        break;
    
    case "submit":
        if (empty($_REQUEST['type'])) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }

        $cleanType = $tfValidator->trimString($_REQUEST['type']);
        $typeWhitelist = $sensorHandler->getSensorTypes();

        if (!array_key_exists($cleanType, $typeWhitelist)) {
            trigger_error(TFISH_ERROR_NOT_SENSOR, E_USER_ERROR);
            exit;
        }

        $sensor = new $cleanType($tfValidator);
        $sensor->loadPropertiesFromArray($_REQUEST);
        
        // Insert the object
        $result = $sensorHandler->insert($sensor);

        if ($result) {
            $tfCache->flushCache();
            $tfTemplate->pageTitle = TFISH_SUCCESS;
            $tfTemplate->alertClass = 'alert-success';
            $tfTemplate->message = TFISH_OBJECT_WAS_INSERTED;
        } else {
            $tfTemplate->title = TFISH_FAILED;
            $tfTemplate->alertClass = 'alert-danger';
            $tfTemplate->message = TFISH_OBJECT_INSERTION_FAILED;
        }

        $tfTemplate->backUrl = 'sensor.php';
        $tfTemplate->form = TFISH_FORM_PATH . "response.html";
        $tfTemplate->tfMainContent = $tfTemplate->render('form');
        break;
        
    case "edit":
        if (!isset($_REQUEST['id'])) {
            trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
        }
        
        $cleanId = (int) $_REQUEST['id'];
        
        if (!$tfValidator->isInt($cleanId, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }

        $criteria = $tfCriteriaFactory->getCriteria();
        $criteria->add($tfCriteriaFactory->getItem('id', $cleanId));
        $statement = $tfDatabase->select('sensor', $criteria);

        if (!$statement) {
            trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_NOTICE);
            header("Location: sensor.php");
        }
        
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $sensor = $sensorHandler->convertRowToObject($row, false);

        // Assign to template.
        $tfTemplate->pageTitle = TFISH_SENSOR_EDIT;
        $tfTemplate->op = 'update'; // Critical to launch correct submission action.
        $tfTemplate->action = TFISH_UPDATE;
        $tfTemplate->sensor = $sensor;
        $tfTemplate->sensorTypes = $sensorHandler->getsensorTypes();
        $tfTemplate->protocols = $sensorHandler->getDataProtocols();
        //$tfTemplate->parentSelectOptions = $parentTree->makeParentSelectBox((int) $row['parent']);
        $tfTemplate->parentSelectOptions = array(0 => '---');
        $tfTemplate->form = TFISH_MACHINES_MODULE_FORM_PATH . "sensorEdit.html";
        $tfTemplate->tfMainContent = $tfTemplate->render('form');
        break;
    
    case "update":
        if (empty($_REQUEST['type'])) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }

        $type = $tfValidator->trimString($_REQUEST['type']);
        $typeWhitelist = $sensorHandler->getSensorTypes();

        if (!array_key_exists($type, $typeWhitelist)) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }

        $sensor = new $type($tfValidator);
        $sensor->loadPropertiesFromArray($_REQUEST);

        // As this object is being sent to storage, need to decode entities that got encoded for
        // display.
        if (isset($sensor->title)) {
            $sensor->title = htmlspecialchars_decode($sensor->title, ENT_NOQUOTES);
        }

        // Properties that are used within attributes must have quotes encoded.
        $fieldsToDecode = array('metaTitle', 'seo', 'metaDescription');

        foreach ($fieldsToDecode as $field) {
            if (isset($sensor->field)) {
                $sensor->$field = htmlspecialchars_decode($sensor->field, ENT_QUOTES);
            }
        }

        // Update the database row and display a response.
        $result = $sensorHandler->update($sensor);

        if ($result) {
            $tfCache->flushCache();
            $tfTemplate->pageTitle = TFISH_SUCCESS;
            $tfTemplate->alertClass = 'alert-success';
            $tfTemplate->message = TFISH_OBJECT_WAS_UPDATED;
            $tfTemplate->id = $sensor->id;
        } else {
            $tfTemplate->pageTitle = TFISH_FAILED;
            $tfTemplate->alertClass = 'alert-danger';
            $tfTemplate->message = TFISH_OBJECT_UPDATE_FAILED;
        }

        $tfTemplate->backUrl = 'sensor.php';
        $tfTemplate->form = TFISH_MACHINES_MODULE_FORM_PATH . "responseSensorEdit.html";
        $tfTemplate->tfMainContent = $tfTemplate->render('form');
        break;
    
    case "confirmDelete":
        if (isset($_REQUEST['id'])) {

            $cleanId = (int) $_REQUEST['id'];

            if ($tfValidator->isInt($cleanId, 1)) {
                $tfTemplate->pageTitle = TFISH_CONFIRM_DELETE;
                $tfTemplate->sensor = $sensorHandler->getObject($cleanId);
                $tfTemplate->form = TFISH_MACHINES_MODULE_FORM_PATH . "confirmSensorDelete.html";
                $tfTemplate->tfMainContent = $tfTemplate->render('form');
            } else {
                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            }
        } else {
            trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
        }
        break;
    
    case "delete":
        if (isset($_REQUEST['id'])) {
            $cleanId = (int) $_REQUEST['id'];
            $result = $sensorHandler->delete($cleanId);

            if ($result) {
                $tfCache->flushCache();
                $tfTemplate->pageTitle = TFISH_SUCCESS;
                $tfTemplate->alertClass = 'alert-success';
                $tfTemplate->message = TFISH_OBJECT_WAS_DELETED;
            } else {
                $tfTemplate->pageTitle = TFISH_FAILED;
                $tfTemplate->alertClass = 'alert-danger';
                $tfTemplate->message = TFISH_OBJECT_DELETION_FAILED;
            }

            $tfTemplate->backUrl = 'sensor.php';
            $tfTemplate->form = TFISH_FORM_PATH . "response.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
        } else {
            trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
        }
        break;
    
    case "toggle":
        $id = (int) $_REQUEST['id'];
        $cleanId = $tfValidator->isInt($id, 1) ? $id : 0;
        $result = $sensorHandler->toggleOnlineStatus($cleanId);

        if ($result) {
            $tfCache->flushCache();
            $tfTemplate->pageTitle = TFISH_SUCCESS;
            $tfTemplate->alertClass = 'alert-success';
            $tfTemplate->message = TFISH_OBJECT_WAS_UPDATED;
        } else {
            $tfTemplate->pageTitle = TFISH_FAILED;
            $tfTemplate->alertClass = 'alert-danger';
            $tfTemplate->message = TFISH_OBJECT_UPDATE_FAILED;
        }

        $tfTemplate->backUrl = 'sensor.php';
        $tfTemplate->form = TFISH_FORM_PATH . "response.html";
        $tfTemplate->tfMainContent = $tfTemplate->render('form');
        break;
        
    case "view":
        if (!$cleanId) {
            $tfTemplate->tfMainContent = TFISH_ERROR_NO_SUCH_CONTENT;
        }
            
        $sensor = $sensorHandler->getObject($cleanId);

        if (!is_object($sensor)) {
            trigger_error(TFISH_ERROR_NOT_SENSOR, E_USER_ERROR);
        }
        $tfTemplate->sensor = $sensor;

        // Prepare meta information for display.
        $sensorInfo = array();

        if ($sensor->counter) {
            $sensorInfo[] = $sensor->escapeForXss('counter') . ' ' . TFISH_VIEWS;
        }

        $tfTemplate->sensorInfo = implode(' | ', $sensorInfo);

        if ($sensor->metaTitle) $tfMetadata->setTitle($sensor->metaTitle);

        if ($sensor->metaDescription) $tfMetadata->setDescription($sensor->metaDescription);

        // Check if has a parental object; if so display a thumbnail and teaser / link.
        if (!empty($sensor->parent)) {
            $parent = $sensorHandler->getObject($sensor->parent);

            if (is_object($parent) && $parent->online) {
                $tfTemplate->parent = $parent;
            }
        }

        // Render template.
        $tfTemplate->tfMainContent = $tfTemplate->render($sensor->template);
        break;
    
    default:
        $criteria = $tfCriteriaFactory->getCriteria();

        if ($tfValidator->isInt($cleanOnline, 0, 1)) {
            $criteria->add($tfCriteriaFactory->getItem('online', $cleanOnline));
        }

        $typelist = $sensorHandler->getSensorTypes();
        
        if ($cleanType) {
            if (array_key_exists($cleanType, $typelist)) {
                $criteria->add($tfCriteriaFactory->getItem('type', $cleanType));
            } else {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            }
        }

        // Other criteria.
        $criteria->setOffset($cleanStart);
        $criteria->setLimit($tfPreference->adminPagination);
        $criteria->setOrder('submissionTime');
        $criteria->setOrderType('DESC');
        $columns = array('id', 'type', 'title', 'submissionTime', 'counter', 'online');
        $result = $tfDatabase->select('sensor', $criteria, $columns);

        if ($result) {
            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }

        // Convert timestamp to human readable.
        foreach ($rows as &$row) {
            $row['submissionTime']
                    = date($tfPreference->dateFormat, (int) $row['submissionTime']);
        }

        // Pagination control.
        $extraParams = array();
        if (isset($cleanOnline) && $tfValidator->isInt($cleanOnline, 0, 1)) {
            $extraParams['online'] = $cleanOnline;
        }
        if (isset($cleanType) && !empty($cleanType)) {
            $extraParams['type'] = $cleanType;
        }

        $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
        $tfPagination->setUrl('sensor');
        $tfPagination->setCount($tfDatabase->selectCount('sensor', $criteria));
        $tfPagination->setLimit($tfPreference->adminPagination);
        $tfPagination->setStart($cleanStart);
        $tfPagination->setExtraParams($extraParams);
        $tfTemplate->pagination = $tfPagination->renderPaginationControl();

        // Prepare select filters.

        // Assign to template.
        $tfTemplate->pageTitle = TFISH_SENSORS;
        $tfTemplate->rows = $rows;
        $tfTemplate->typelist = $typelist;
        $tfTemplate->form = TFISH_MACHINES_MODULE_FORM_PATH . "sensorTable.html";
        $tfTemplate->tfMainContent = $tfTemplate->render('form');
        break;
}

/**
 * Override page template here (otherwise default site metadata will display).
 */
// $tfMetadata->setTitle('');
// $tfMetadata->setDescription('');
// $tfMetadata->setAuthor('');
// $tfMetadata->setCopyright('');
// $tfMetadata->setGenerator('');
// $tfMetadata->setSeo('');
$tfMetadata->setRobots('noindex,nofollow');

// Include page template and flush buffer
require_once TFISH_PATH . "tfFooter.php";