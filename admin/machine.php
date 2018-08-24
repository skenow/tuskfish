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

if (!in_array($op, $optionsWhitelist)) {
    exit;
}
    
// Cross-site request forgery check for all options except toggle, view and default.
if (!in_array($op, array('confirmDelete', 'confirmFlush', 'edit', 'toggle', 'view', ''))) {
    TfSession::validateToken($cleanToken);
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