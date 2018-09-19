<?php

/**
 * Admin controller script for the Experts module.
 * 
 * @copyright   Simon Wilkinson 2018+ (https://isengard.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     experts
 */
// Enable strict type declaration.
declare(strict_types=1);

// Boot! Set file paths, preferences and connect to database.
require_once "../mainfile.php";
require_once TFISH_ADMIN_PATH . "tfAdminHeader.php";
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php"; // Required for tags.
require_once TFISH_MODULE_PATH . "experts/tfExpertsHeader.php";

// Specify the admin theme you want to use.
$tfTemplate->setTheme('admin');

// Set target file for intra-collection pagination controls when viewing objects.
$targetFileName = 'experts';
$tfTemplate->targetFileName = $targetFileName;

// Validate input parameters.
$cleanId = (int) ($_REQUEST['id'] ?? 0);
$cleanStart = (int) ($_GET['start'] ?? 0);
$cleanTag = (int) ($_GET['tagId'] ?? 0);
$cleanOnline = isset($_GET['online']) ? (int) $_GET['online'] : null;
$cleanToken = isset($_POST['token']) ? $tfValidator->trimString($_POST['token']) : '';
$op = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : false;

// Permitted options.
$op = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : false;
$optionsWhitelist = array(
    "add",
    "submit",
    "confirmDelete",
    "delete",
    "edit",
    "update",
    "toggle",
    "view",
    "");
   
// Cross-site request forgery check.
if (!in_array($op, $optionsWhitelist)) {
    TfSession::validateToken($cleanToken);
}

$contentHandler = $contentHandlerFactory->getHandler('tag');
$expertHandler = $expertFactory->getExpertHandler();
$expertController = $expertFactory->getExpertController();

// Business logic goes here.
switch ($op) {
    case "add":
        $expertController->addExpert($contentHandler);
        break;
    
    case "submit":
        $expertController->submitExpert($_REQUEST);
        break;
    
    case "confirmDelete":
        $expertController->confirmDelete($cleanId);
        break;

    // Delete a content object. ID must be an integer and > 1.
    case "delete":
        $expertController->deleteExpert($cleanId);
        break;
        
    case "edit":
        $expertController->editExpert($cleanId, $contentHandler);
        break;
    
    case "update":
        $expertController->updateExpert($_REQUEST);
        break;
    
    case "toggle":
        $expertController->toggleOnlineStatus($cleanId);
        break;
    
    case "view":
        break;
    
    default:
        $criteria = $tfCriteriaFactory->getCriteria();

        // Select box filter input.

        // Other criteria.
        $criteria->setOffset($cleanStart);
        $criteria->setLimit($tfPreference->adminPagination);
        $criteria->setOrder('submissionTime');
        $criteria->setOrderType('DESC');
        $columns = array('id', 'salutation', 'firstName', 'lastName', 'lastUpdated',
            'submissionTime', 'counter', 'online');
        $result = $tfDatabase->select('expert', $criteria, $columns);

        if ($result) {
            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }

        foreach ($rows as &$row) {
            if ($row['lastName'] && $row['firstName']) {
                $row['name'] = $row['lastName'] . ', ' . $row['firstName'];
            } else {
                $row['name'] = $row['firstName']; // Some people only have one name.
            }
            
            $row['lastUpdated']
                    = date($tfPreference->dateFormat, (int) $row['lastUpdated']);
            $row['submissionTime']
                    = date($tfPreference->dateFormat, (int) $row['submissionTime']);
        }
        
        $tfTemplate->salutationList = $expertHandler->getSalutationList();
        
        // Pagination control.
        $extraParams = array();
        if (isset($cleanOnline) && $tfValidator->isInt($cleanOnline, 0, 1)) {
            $extraParams['online'] = $cleanOnline;
        }
        if (isset($cleanType) && !empty($cleanType)) {
            $extraParams['type'] = $cleanType;
        }

        $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
        $tfPagination->setUrl('admin');
        $tfPagination->setCount($tfDatabase->selectCount('expert', $criteria));
        $tfPagination->setLimit($tfPreference->adminPagination);
        $tfPagination->setStart($cleanStart);
        $tfPagination->setTag($cleanTag);
        $tfPagination->setExtraParams($extraParams);
        $tfTemplate->pagination = $tfPagination->renderPaginationControl();

        // Prepare select filters.
        
        $tfTemplate->pageTitle = TFISH_EXPERTS;
        $tfTemplate->rows = $rows;
        $tfTemplate->form = TFISH_EXPERTS_MODULE_FORM_PATH . "expertTable.html";
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