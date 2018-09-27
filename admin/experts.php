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

// Set target file for intra-collection pagination controls when viewing objects.
$targetFileName = 'experts';
$tfTemplate->targetFileName = $targetFileName;

// Validate input parameters.
$cleanId = (int) ($_REQUEST['id'] ?? 0);
$cleanStart = (int) ($_GET['start'] ?? 0);
$cleanState = (int) ($_GET['state'] ?? 0);
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

// Specify the admin theme and the template to be used to preview content (user side template).
if ($op === 'view') {
    $tfTemplate->setTheme('default');
} else {
    $tfTemplate->setTheme('admin');
}

// Get handlers and controllers.
$contentHandler = $contentFactory->getContentHandler('tag');
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
        if ($cleanId) {
            $expert = $expertHandler->getObject($cleanId);
            
            if (!is_object($expert)) {
                $tfTemplate->tfMainContent = TFISH_ERROR_NO_SUCH_CONTENT;
                break;
            }

            $tfTemplate->expert = $expert;

            $expertInfo = array();

            if ($expert->tags) {
                $tags = $expertHandler->makeTagLinks($expert->tags, 'experts');
                $tags = TFISH_TAGS . ': ' . implode(', ', $tags);
                $expertInfo[] = $tags;
            }

            $tfTemplate->expertInfo = implode(' | ', $expertInfo);
            
            if ($expert->metaTitle) $tfMetadata->setTitle($expert->metaTitle);
            if ($expert->metaDescription) $tfMetadata->setDescription($expert->metaDescription);
            
            $tfTemplate->tfMainContent = $tfTemplate->render($expert->template);
        }
        break;
    
    default:
        $criteria = $tfCriteriaFactory->getCriteria();

        // Select box filter input.
        if ($cleanTag) $criteria->setTag(array($cleanTag));
        
        if ($cleanState) {
            $criteria->add($tfCriteriaFactory->getItem('country', $cleanState));
        }
        
        if (isset($cleanOnline) && $tfValidator->isInt($cleanOnline, 0, 1)) {
            $criteria->add($tfCriteriaFactory->getItem('online', $cleanOnline));
        }

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

        $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
        $tfPagination->setUrl('experts');
        $tfPagination->setCount($tfDatabase->selectCount('expert', $criteria));
        $tfPagination->setLimit($tfPreference->adminPagination);
        $tfPagination->setStart($cleanStart);
        $tfPagination->setTag($cleanTag);
        $tfPagination->setExtraParams($extraParams);
        $tfTemplate->pagination = $tfPagination->renderPaginationControl();
        
        // Tag select filter.
        $tagHandler = $contentFactory->getContentHandler('tag');
        $tfTemplate->tagSelect = $tagHandler->getTagSelectBox($cleanTag, 'experts');
        
        // Country select filter.
        $tfTemplate->countrySelect = $expertHandler->getCountrySelectBox($cleanState, TFISH_EXPERTS_SELECT_STATE);
        
        // Online select filter.
        $tfTemplate->onlineSelect = $contentHandler->getOnlineSelectBox($cleanOnline);

        $tfTemplate->selectAction = 'experts.php';
        $tfTemplate->selectFiltersForm = $tfTemplate->render('expertSelectFilters');
        
        // Render template;
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