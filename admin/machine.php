<?php

/**
 * Admin controller script for the Machines module.
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
$cleanToken = isset($_POST['token']) ? $tfValidator->trimString($_POST['token']) : '';
$op = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : false;

// Permitted options.
$op = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : '';
$optionsWhitelist = array('', 'add', 'confirmDelete', 'delete', 'edit', 'submit', 'toggle',
    'update', 'view');

// Target filename.
$targetFileName = 'machine';
$tfTemplate->sensorFileName = 'sensor';

if (!in_array($op, $optionsWhitelist)) {
    exit;
}
    
// Cross-site request forgery check for sensitive operations.
if (!in_array($op, array('confirmDelete', 'confirmFlush', 'edit', 'toggle', 'view', ''))) {
    TfSession::validateToken($cleanToken);
}

// If an ID is set, this implies that an object should be viewed.
if ($cleanId) {
    $op = 'view';
}

// Specify the admin theme and the template to be used to preview machine (user side template).
if ($op === 'view') {
    $tfTemplate->setTheme('default');
} else {
    $tfTemplate->setTheme('admin');
}

$machineHandler = $machineFactory->getMachineHandler();
$machineController = $machineFactory->getMachineController();

switch ($op) {
    case "add":
        $machineController->addMachine();
        break;
    
    case "submit":
        $machineController->submitMachine($_REQUEST);
        break;
        
    case "edit":
        $machineController->editMachine($cleanId);
        break;
    
    case "update":
        $machineController->updateMachine($_REQUEST);
        break;
    
    case "confirmDelete":
        $machineController->confirmDelete($cleanId);
        break;
    
    case "delete":        
        $machineController->deleteMachine($cleanId);
        break;
    
    case "toggle":
        $machineController->toggleOnlineStatus($cleanId);
        break;
    
    case "view":
        if (!$cleanId) {
            $tfTemplate->tfMainContent = TFISH_ERROR_NO_SUCH_CONTENT;
        }
            
        $machine = $machineHandler->getObject($cleanId);

        if (!is_object($machine)) {
            trigger_error(TFISH_ERROR_NOT_SENSOR, E_USER_ERROR);
        }
        $tfTemplate->machine = $machine;

        // Prepare meta information for display.
        $machineInfo = array();

        if ($machine->counter) {
            $machineInfo[] = $machine->escapeForXss('counter') . ' ' . TFISH_VIEWS;
        }

        $tfTemplate->machineInfo = implode(' | ', $machineInfo);

        if ($machine->metaTitle) $tfMetadata->setTitle($machine->metaTitle);

        if ($machine->metaDescription) $tfMetadata->setDescription($machine->metaDescription);
        
        // Check for child objects.
        $sensorHandler = $sensorFactory->getSensorHandler();
        
        $criteria = $tfCriteriaFactory->getCriteria();
        $criteria->setOrder('title');
        $criteria->setOrderType('ASC');
        $criteria->add($tfCriteriaFactory->getItem('parent', $machine->id));
        $criteria->add($tfCriteriaFactory->getItem('online', $machine->online));
        $criteria->setLimit($tfPreference->userPagination);
        if ($cleanStart) $criteria->setOffset($cleanStart);

        // Prepare pagination control.
        $machinePagination = new TfPaginationControl($tfValidator, $tfPreference);
        $machinePagination->setUrl($targetFileName);
        $machinePagination->setCount($sensorHandler->getCount($criteria));
        $machinePagination->setLimit($tfPreference->userPagination);
        $machinePagination->setStart($cleanStart);
        $machinePagination->setTag(0);
        $machinePagination->setExtraParams(array('id' => $cleanId));
        $tfTemplate->machinePagination = $machinePagination->renderPaginationControl();

        // Retrieve sensor objects and assign to template.
        $childSensors = $sensorHandler->getObjects($criteria);

        if (!empty($childSensors)) {
            $tfTemplate->childSensors = $childSensors;
        }

        // Render template.
        $tfTemplate->tfMainContent = $tfTemplate->render($machine->template);
        break;
    
    default:
        $criteria = $tfCriteriaFactory->getCriteria();

        if ($tfValidator->isInt($cleanOnline, 0, 1)) {
            $criteria->add($tfCriteriaFactory->getItem('online', $cleanOnline));
        }

        // Other criteria.
        $criteria->setOffset($cleanStart);
        $criteria->setLimit($tfPreference->adminPagination);
        $criteria->setOrder('submissionTime');
        $criteria->setOrderType('DESC');
        $columns = array('id', 'title', 'submissionTime', 'counter', 'online');
        $result = $tfDatabase->select('machine', $criteria, $columns);

        if ($result) {
            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }

        foreach ($rows as &$row) {
            $row['submissionTime']
                    = date($tfPreference->dateFormat, (int) $row['submissionTime']);
        }

        // Pagination control.
        $extraParams = array();
        if (isset($cleanOnline) && $tfValidator->isInt($cleanOnline, 0, 1)) {
            $extraParams['online'] = $cleanOnline;
        }

        $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
        $tfPagination->setUrl('machine');
        $tfPagination->setCount($tfDatabase->selectCount('machine', $criteria));
        $tfPagination->setLimit($tfPreference->adminPagination);
        $tfPagination->setStart($cleanStart);
        $tfPagination->setExtraParams($extraParams);
        $tfTemplate->pagination = $tfPagination->renderPaginationControl();

        // Prepare select filters.

        // Assign to template.
        $tfTemplate->pageTitle = TFISH_MACHINES;
        $tfTemplate->rows = $rows;
        $tfTemplate->form = TFISH_MACHINES_MODULE_FORM_PATH . "machineTable.html";
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