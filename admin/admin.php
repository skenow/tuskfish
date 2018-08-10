<?php

/**
 * Add, edit or delete content objects as required.
 *
 * This is the core of the Tuskfish content management system.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     admin
 */
// Enable strict type declaration.
declare(strict_types=1);

require_once "../mainfile.php"; // 1. Access trust path, DB credentials and preferences.
require_once TFISH_ADMIN_PATH . "tfAdminHeader.php"; // 2. Main Tuskfish header, bootstraps Tuskfish.
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php"; // 3. Content header sets module-specific paths.

// Validate input parameters.
$cleanId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$cleanStart = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$cleanTag = isset($_GET['tagId']) ? (int) $_GET['tagId'] : 0;
$cleanOnline = isset($_GET['online']) ? (int) $_GET['online'] : null;
$cleanType = isset($_GET['type']) && !empty($_GET['type'])
        ? $tfValidator->trimString($_GET['type']) : '';
$cleanToken = isset($_POST['token']) ? $tfValidator->trimString($_POST['token']) : '';
$op = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : false;

// Specify the admin theme and the template to be used to preview content (user side template).
if ($op === 'view') {
    $tfTemplate->setTheme('default');
} else {
    $tfTemplate->setTheme('admin');
}

// Set target file for intra-collection pagination controls when viewing objects. False will 
// default to your home page.
$targetFileName = '';
$tfTemplate->targetFileName = $targetFileName;

// Permitted actions.
$optionsWhitelist = array(
    'add',
    'confirmDelete',
    'confirmFlush',
    'delete',
    'edit',
    'flush',
    'submit',
    'toggle',
    'update',
    'view',
    false
    );

// Process actions.
if (in_array($op, $optionsWhitelist)) {
    
    // Cross-site request forgery check for all options except for view and toggle online/offline.
    if (!in_array($op, array('confirmDelete', 'confirmFlush', 'edit', 'toggle', 'view', false), true)) {
        TfSession::validateToken($cleanToken);
    }
    
    $paginationFactory = new TfPaginationControlFactory($tfValidator, $tfPreference);
    $controllerFactory = new TfContentControllerFactory($tfValidator, $tfDatabase, $tfCriteriaFactory,
            $contentHandlerFactory, $paginationFactory, $tfTemplate, $tfMetadata, $tfPreference,
            $tfCache);
    
    switch ($op) {
        // Add: Display an empty content object submission form.
        case "add":
            $contentController = $controllerFactory->getController('content');
            $contentController->addContent();
            break;

        // Confirm deletion of a content object.
        case "confirmDelete":
            if (isset($_REQUEST['id'])) {
                $contentController = $controllerFactory->getController('content');
                $contentController->confirmDelete((int) $_REQUEST['id']);
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;
            
        // Delete a content object. ID must be an integer and > 1.
        case "delete":
            if (isset($_REQUEST['id'])) {
                $contentController = $controllerFactory->getController('content');
                $contentController->deleteContent((int) $_REQUEST['id']);
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;
        
        // Confirm that you want to flush the cache.
        case "confirmFlush":
            $tfTemplate->pageTitle = TFISH_CONFIRM_FLUSH;
            $tfTemplate->form = TFISH_FORM_PATH . "confirmFlush.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            break;

        // Flush the cache.
        case "flush":
            $result = $tfCache->flushCache();
        
            if ($result) {
                $tfTemplate->pageTitle = TFISH_SUCCESS;
                $tfTemplate->alertClass = 'alert-success';
                $tfTemplate->message = TFISH_CACHE_WAS_FLUSHED;
            } else {
                $tfTemplate->pageTitle = TFISH_FAILED;
                $tfTemplate->alertClass = 'alert-danger';
                $tfTemplate->message = TFISH_CACHE_FLUSH_FAILED;
            }

            $tfTemplate->backUrl = 'admin.php';
            $tfTemplate->form = TFISH_FORM_PATH . "response.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            break;

        // Display a data entry form containing the object's current properties.
        case "edit":            
            if (isset($_REQUEST['id'])) {
                $contentController = $controllerFactory->getController('content');
                $contentController->editContent((int) $_REQUEST['id']);
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;

        // Insert a new content object to the database.
        case "submit":
            $contentController = $controllerFactory->getController('content');
            $contentController->submitContent($_REQUEST);
            break;

        // Toggle the online status of a particular object.
        case "toggle":            
            if (isset($_REQUEST['id'])) {
                $contentController = $controllerFactory->getController('content');
                $contentController->toggleOnlineStatus((int) $_REQUEST['id']);
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;

        // Update: Submit the modified object and update the corresponding database row.
        case "update":
            $contentController = $controllerFactory->getController('content');
            $contentController->updateContent($_REQUEST);
            break;

        // View: See the user-side display of a single object, including offline objects.
        case "view":
            if ($cleanId) {
                $viewController = $controllerFactory->getController('view');
                $viewController->setId($cleanId);
                $viewController->setStart($cleanStart);
                $viewController->setTargetFilename($targetFileName);
                $viewController->displaySingleObject();
            }
            break;

        // Default: Display a table of existing content objects and pagination controls.
        default:
            $viewController = $controllerFactory->getController('view');
            $viewController->setStart($cleanStart);
            $viewController->setTag($cleanTag);
            $viewController->setType($cleanType);
            $viewController->setOnline($cleanOnline);
            $viewController->setTargetFileName($targetFileName);
            $viewController->displayMultipleObjects();
            break;
    }
} else {
    trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
    exit;
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
