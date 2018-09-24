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

// 1. Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "../mainfile.php";

// 2. Main Tuskfish header. This file bootstraps Tuskfish.
require_once TFISH_ADMIN_PATH . "tfAdminHeader.php";

// 3. Content header sets module-specific paths and makes TfContentHandlerFactory available.
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php";

// Validate input parameters.
$cleanId = (int) ($_REQUEST['id'] ?? 0);
$cleanStart = (int) ($_GET['start'] ?? 0);
$cleanTag = (int) ($_GET['tagId'] ?? 0);
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

// Permitted options.
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

if (in_array($op, $optionsWhitelist)) {
    
    // Cross-site request forgery check for sensitive options.
    if (!in_array($op, array('confirmDelete', 'confirmFlush', 'edit', 'toggle', 'view', false, ''))) {
        TfSession::validateToken($cleanToken);
    }
    
    $contentHandler = $contentHandlerFactory->getHandler('content');
    $controllerFactory = new TfContentControllerFactory($tfValidator, $tfDatabase, $tfCriteriaFactory,
            $contentHandlerFactory, $tfTemplate, $tfPreference, $tfCache);
    
    // Process actions.
    switch ($op) {
        // Add: Display an empty content object submission form.
        case "add":
            $contentController = $controllerFactory->getController('admin');
            $contentController->addContent();
            break;

        // Confirm deletion of a content object.
        case "confirmDelete":
            if ($cleanId) {
                $contentController = $controllerFactory->getController('admin');
                $contentController->confirmDelete($cleanId);
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;
            
        // Delete a content object. ID must be an integer and > 1.
        case "delete":
            if ($cleanId) {
                $contentController = $controllerFactory->getController('admin');
                $contentController->deleteContent($cleadId);
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;
            
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

        // Insert a new content object to the database.
        case "submit":
            $contentController = $controllerFactory->getController('admin');
            $contentController->submitContent($_REQUEST);
            break;
        
        // Display a data entry form containing the object's current properties.
        case "edit":            
            if (isset($_REQUEST['id'])) {
                $contentController = $controllerFactory->getController('admin');
                $contentController->editContent($cleanId);
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;

        // Update: Submit the modified object and update the corresponding database row.
        case "update":
            $contentController = $controllerFactory->getController('admin');
            $contentController->updateContent($_REQUEST);
            break;
        
        // Toggle the online status of a particular object.
        case "toggle":            
            if (isset($_REQUEST['id'])) {
                $contentController = $controllerFactory->getController('admin');
                $contentController->toggleOnlineStatus((int) $_REQUEST['id']);
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;

        // View: See the user-side display of a single object, including offline objects.
        case "view":
            if ($cleanId) {
                $content = $contentHandler->getObject($cleanId);
                
                if (is_object($content)) {
                    $tfTemplate->content = $content;

                    // Prepare meta information for display.
                    $contentInfo = array();
                    
                    if ($content->creator) $contentInfo[] = $content->escapeForXss('creator');
                    
                    if ($content->date) $contentInfo[] = $content->escapeForXss('date');
                    
                    if ($content->counter) {
                        switch ($content->type) {
                            case "TfDownload": // Display 'downloads' after the counter.
                                $contentInfo[] = $content->escapeForXss('counter') . ' '
                                    . TFISH_DOWNLOADS;
                                break;
                            
                            // Display 'downloads' after the counter if there is an attached media
                            // file; otherwise 'views'.
                            case "TfCollection":
                                if ($content->media) {
                                    $contentInfo[] = $content->escapeForXss('counter') . ' '
                                            . TFISH_DOWNLOADS;
                                    break;
                                }
                                break;
                                
                            default: // Display 'views' after the counter.
                                $contentInfo[] = $content->escapeForXss('counter') . ' ' . TFISH_VIEWS;
                        }
                    }
                    
                    if ($content->format)
                        $contentInfo[] = '.' . $content->escapeForXss('format');
                    
                    if ($content->fileSize)
                        $contentInfo[] = $content->escapeForXss('fileSize');
                    
                    // For a content type-specific page use $content->tags, $content->template.
                    if ($content->tags) {
                        $tags = $contentHandler->makeTagLinks($content->tags);
                        $tags = TFISH_TAGS . ': ' . implode(', ', $tags);
                        $contentInfo[] = $tags;
                    }
                    
                    $tfTemplate->contentInfo = implode(' | ', $contentInfo);
                    
                    if ($content->metaTitle) $tfMetadata->setTitle($content->metaTitle);
                    
                    if ($content->metaDescription) $tfMetadata->setDescription($content->metaDescription);

                    // Check if has a parental object; if so display a thumbnail and teaser / link.
                    if (!empty($content->parent)) {
                        $parent = $contentHandler->getObject($content->parent);
                        
                        if (is_object($parent) && $parent->online) {
                            $tfTemplate->parent = $parent;
                        }
                    }

                    // Initialise criteria object.
                    $criteria = $tfCriteriaFactory->getCriteria();
                    $criteria->setOrder('date');
                    $criteria->setOrderType('DESC');
                    $criteria->setSecondaryOrder('submissionTime');
                    $criteria->setSecondaryOrderType('DESC');

                    // If object is a collection check if has child objects; if so display
                    // thumbnails and teasers / links.
                    if ($content->type === 'TfCollection') {
                        $criteria->add($tfCriteriaFactory->getItem('parent', $content->id));
                        $criteria->add($tfCriteriaFactory->getItem('online', 1));
                        
                        if ($cleanStart) $criteria->setOffset($cleanStart);
                        
                        $criteria->setLimit($tfPreference->userPagination);
                    }

                    // If object is a tag, then a different method is required to call the related
                    // content.
                    if ($content->type === 'TfTag') {
                        if ($cleanStart) $criteria->setOffset($cleanStart);
                        
                        $criteria->setLimit($tfPreference->userPagination);
                        $criteria->setTag(array($content->id));
                        $criteria->add($tfCriteriaFactory->getItem('online', 1));
                    }

                    // Prepare pagination control.
                    if ($content->type === 'TfCollection' || $content->type === 'TfTag') {                        
                        $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
                        $tfPagination->setUrl($targetFileName);
                        $tfPagination->setCount($contentHandler->getCount($criteria));
                        $tfPagination->setLimit($tfPreference->userPagination);
                        $tfPagination->setStart($cleanStart);
                        $tfPagination->setTag(0);
                        $tfPagination->setExtraParams(array('id' => $cleanId));
                        $tfTemplate->collectionPagination = $tfPagination->renderPaginationControl();

                        // Retrieve content objects and assign to template.
                        $firstChildren = $contentHandler->getObjects($criteria);
                        
                        if (!empty($firstChildren)) {
                            $tfTemplate->firstChildren = $firstChildren;
                        }
                    }

                    // Render template.
                    $tfTemplate->tfMainContent
                            = $tfTemplate->render($content->template);
                } else {
                    $tfTemplate->tfMainContent = TFISH_ERROR_NO_SUCH_CONTENT;
                }
            }
            break;

        // Default: Display a table of existing content objects and pagination controls.
        default:
            $criteria = $tfCriteriaFactory->getCriteria();

            // Select box filter input.
            if ($cleanTag) $criteria->setTag(array($cleanTag));
            
            if ($tfValidator->isInt($cleanOnline, 0, 1)) {
                $criteria->add($tfCriteriaFactory->getItem('online', $cleanOnline));
            }
            
            if ($cleanType) {
                if (array_key_exists($cleanType, $contentHandler->getTypes())) {
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
            $result = $tfDatabase->select('content', $criteria, $columns);
            
            if ($result) {
                $rows = $result->fetchAll(PDO::FETCH_ASSOC);
            } else {
                trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
            }
            
            foreach ($rows as &$row) {
                $row['submissionTime']
                        = date($tfPreference->dateFormat, (int) $row['submissionTime']);
            }
            
            $typelist = $contentHandler->getTypes();

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
            $tfPagination->setCount($tfDatabase->selectCount('content', $criteria));
            $tfPagination->setLimit($tfPreference->adminPagination);
            $tfPagination->setStart($cleanStart);
            $tfPagination->setTag($cleanTag);
            $tfPagination->setExtraParams($extraParams);
            $tfTemplate->pagination = $tfPagination->renderPaginationControl();

            // Prepare select filters.
            $tagHandler = $contentHandlerFactory->getHandler('tag');
            $tagSelectBox = $tagHandler->getTagSelectBox($cleanTag, 'content');
            $typeSelectBox = $contentHandler->getTypeSelectBox($cleanType);
            $onlineSelectBox = $contentHandler->getOnlineSelectBox($cleanOnline);
            $tfTemplate->selectAction = 'admin.php';
            $tfTemplate->tagSelect = $tagSelectBox;
            $tfTemplate->typeSelect = $typeSelectBox;
            $tfTemplate->onlineSelect = $onlineSelectBox;
            $tfTemplate->selectFiltersForm = $tfTemplate->render('adminSelectFilters');

            // Assign to template.
            $tfTemplate->pageTitle = TFISH_CURRENT_CONTENT;
            $tfTemplate->rows = $rows;
            $tfTemplate->typelist = $contentHandler->getTypes();
            $tfTemplate->form = TFISH_CONTENT_MODULE_FORM_PATH . "contentTable.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
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
