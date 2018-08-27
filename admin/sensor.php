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

// Specify the admin theme and the template to be used to preview content (user side template).
if ($op === 'view') {
    $tfTemplate->setTheme('default');
} else {
    $tfTemplate->setTheme('admin');
}

$sensorHandler = $sensorFactory->getSensorHandler();
$sensorController = $sensorFactory->getSensorController();

switch ($op) {
    case "add":
        $machineHandler = $machineFactory->getMachineHandler();
        $sensorController->addSensor($machineHandler);
        break;
    
    case "submit":
        $sensorController->submitSensor($_REQUEST);
        break;
        
    case "edit":
        $machineHandler = $machineFactory->getMachineHandler();
        $sensorController->editSensor($cleanId, $machineHandler);
        break;
    
    case "update":
        $sensorController->updateSensor($_REQUEST);
        break;
    
    case "confirmDelete":
        $sensorController->confirmDelete($cleanId);
        break;
    
    case "delete":        
        $sensorController->deleteSensor($cleanId);
        break;
    
    case "toggle":
        $sensorController->toggleOnlineStatus($cleanId);
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