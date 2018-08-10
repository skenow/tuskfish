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
    
    // Cross-site request forgery check for all options except for view and toggle online/offline.
    // The rationale for not including a check on the toggle option is that i) no data is lost,
    // ii) the admin will be alerted to the change by the unexpected display of a confirmation
    // message, iii) the action is trivial to undo and iv) it would reduce the functionality of
    // one-click status toggling.
    if (!in_array($op, array('confirmDelete', 'confirmFlush', 'edit', 'toggle', 'view', false), true)) {
        TfSession::validateToken($cleanToken);
    }
    
    $contentHandler = $contentHandlerFactory->getHandler('content');
    
    switch ($op) {
        // Add: Display an empty content object submission form.
        case "add":
            $content = new TfContentObject($tfValidator);
            
            $tfTemplate->pageTitle = TFISH_ADD_CONTENT;
            $tfTemplate->op = 'submit'; // Critical to launch correct form submission action.
            $tfTemplate->contentTypes = $contentHandler->getTypes();
            $tfTemplate->rights = $content->getListOfRights();
            $tfTemplate->languages = $tfPreference->getListOfLanguages();
            $tfTemplate->tags = $contentHandler->getTagList(false);

            // Make a parent tree select box options.
            $collectionHandler = $contentHandlerFactory->getHandler('collection');
            $collections = $collectionHandler->getObjects();
            $parentTree = new TfAngryTree($collections, 'id', 'parent');
            $tfTemplate->parentSelectOptions = $parentTree->makeParentSelectBox();

            $tfTemplate->allowedProperties = $content->getPropertyWhitelist();
            $tfTemplate->zeroedProperties = array(
                'image' => array('image'),
                'tags' => array(
                    'creator',
                    'language',
                    'rights',
                    'publisher',
                    'tags')
            );
            $tfTemplate->form = TFISH_CONTENT_MODULE_FORM_PATH . "dataEntry.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            break;

        // Confirm: Confirm deletion of a content object.
        case "confirmDelete":
            if (isset($_REQUEST['id'])) {
                $cleanId = (int) $_REQUEST['id'];
                
                if ($tfValidator->isInt($cleanId, 1)) {
                    $tfTemplate->pageTitle = TFISH_CONFIRM_DELETE;
                    $tfTemplate->content = $contentHandler->getObject($cleanId);
                    $tfTemplate->form = TFISH_CONTENT_MODULE_FORM_PATH . "confirmDelete.html";
                    $tfTemplate->tfMainContent = $tfTemplate->render('form');
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;
            
        case "confirmFlush":
            $tfTemplate->pageTitle = TFISH_CONFIRM_FLUSH;
            $tfTemplate->form = TFISH_FORM_PATH . "confirmFlush.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            break;

        // Delete: Delete a content object. ID must be an integer and > 1.
        case "delete":
            if (isset($_REQUEST['id'])) {
                $cleanId = (int) $_REQUEST['id'];
                $result = $contentHandler->delete($cleanId);
                
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
                
                $tfTemplate->backUrl = 'admin.php';
                $tfTemplate->form = TFISH_FORM_PATH . "response.html";
                $tfTemplate->tfMainContent = $tfTemplate->render('form');
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;

        // Edit: Display a data entry form containing the object's current properties.
        case "edit":
            if (isset($_REQUEST['id'])) {
                $cleanId = (int) $_REQUEST['id'];
                
                if ($tfValidator->isInt($cleanId, 1)) {
                    $criteria = $tfCriteriaFactory->getCriteria();
                    $criteria->add(new TfCriteriaItem($tfValidator, 'id', $cleanId));
                    $statement = $tfDatabase->select('content', $criteria);
                    
                    if (!$statement) {
                        trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_NOTICE);
                        header("Location: admin.php");
                    }
                    $row = $statement->fetch(PDO::FETCH_ASSOC);

                    // Make a parent tree select box options.
                    $collectionHandler = $contentHandlerFactory->getHandler('collection');
                    $collections = $collectionHandler->getObjects();
                    $parentTree = new TfAngryTree($collections, 'id', 'parent');
                    
                    // Build the content object.
                    $content = $contentHandler->convertRowToObject($row, false);
                    
                    // Assign to template.
                    $tfTemplate->pageTitle = TFISH_EDIT_CONTENT;
                    $tfTemplate->op = 'update'; // Critical to launch correct submission action.
                    $tfTemplate->action = TFISH_UPDATE;
                    $tfTemplate->content = $content;
                    $tfTemplate->contentTypes = $contentHandler->getTypes();
                    $tfTemplate->rights = $content->getListOfRights();
                    $tfTemplate->languages = $tfPreference->getListOfLanguages();
                    $tfTemplate->tags = $contentHandler->getTagList(false);
                    $tfTemplate->parentSelectOptions = 
                            $parentTree->makeParentSelectBox((int) $row['parent']);
                    $tfTemplate->form = TFISH_CONTENT_MODULE_FORM_PATH . "dataEdit.html";
                    $tfTemplate->tfMainContent = $tfTemplate->render('form');
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
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

        // Submit: Determine object type, instantiate, validate input, populate properties  and
        // insert a new content object.
        case "submit":
            if (empty($_REQUEST['type'])) {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                exit;
            }
            
            $cleanType = $tfValidator->trimString($_REQUEST['type']);
            
            $typeWhitelist = $contentHandler->getTypes();
            
            if (!array_key_exists($cleanType, $typeWhitelist)) {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                exit;
            }
            
            $contentObject = new $cleanType($tfValidator);
            $contentObject->loadPropertiesFromArray($_REQUEST);

            // Insert the object
            $result = $contentHandler->insert($contentObject);

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
            
            $tfTemplate->backUrl = 'admin.php';
            $tfTemplate->form = TFISH_FORM_PATH . "response.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            break;

        // Toggle the online status of a particular object.
        case "toggle":
            $id = (int) $_REQUEST['id'];
            $cleanId = $tfValidator->isInt($id, 1) ? $id : 0;
            $result = $contentHandler->toggleOnlineStatus($cleanId);
            
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
            
            $tfTemplate->backUrl = 'admin.php';
            $tfTemplate->form = TFISH_FORM_PATH . "response.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            break;

        // Update: Submit the modified object and update the corresponding database row.
        case "update":
            if (empty($_REQUEST['type'])) {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                exit;
            }

            $type = $tfValidator->trimString($_REQUEST['type']);
            $typeWhitelist = $contentHandler->getTypes();
            
            if (!array_key_exists($type, $typeWhitelist)) {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                exit;
            }

            $contentObject = new $type($tfValidator);
            $contentObject->loadPropertiesFromArray($_REQUEST);

            // As this object is being sent to storage, need to decode some entities that got
            // encoded for display.
            $fieldsToDecode = array('title', 'creator', 'publisher', 'caption');

            foreach ($fieldsToDecode as $field) {
                if (isset($contentObject->field)) {
                    $contentObject->$field = htmlspecialchars_decode($contentObject->field,
                            ENT_NOQUOTES);
                }
            }

            // Properties that are used within attributes must have quotes encoded.
            $fieldsToDecode = array('metaTitle', 'seo', 'metaDescription');
            
            foreach ($fieldsToDecode as $field) {
                if (isset($contentObject->field)) {
                    $contentObject->$field = htmlspecialchars_decode($contentObject->field,
                            ENT_QUOTES);
                }
            }

            // Update the database row and display a response.
            $result = $contentHandler->update($contentObject);

            if ($result) {
                $tfCache->flushCache();
                $tfTemplate->pageTitle = TFISH_SUCCESS;
                $tfTemplate->alertClass = 'alert-success';
                $tfTemplate->message = TFISH_OBJECT_WAS_UPDATED;
                $tfTemplate->id = $contentObject->id;
            } else {
                $tfTemplate->pageTitle = TFISH_FAILED;
                $tfTemplate->alertClass = 'alert-danger';
                $tfTemplate->message = TFISH_OBJECT_UPDATE_FAILED;
            }

            $tfTemplate->backUrl = 'admin.php';
            $tfTemplate->form = TFISH_CONTENT_MODULE_FORM_PATH . "responseEdit.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
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
                        $criteria->add(new TfCriteriaItem($tfValidator, 'parent', $content->id));
                        $criteria->add(new TfCriteriaItem($tfValidator, 'online', 1));
                        
                        if ($cleanStart) $criteria->setOffset($cleanStart);
                        
                        $criteria->setLimit($tfPreference->userPagination);
                    }

                    // If object is a tag, then a different method is required to call the related
                    // content.
                    if ($content->type === 'TfTag') {
                        if ($cleanStart) $criteria->setOffset($cleanStart);
                        
                        $criteria->setLimit($tfPreference->userPagination);
                        $criteria->setTag(array($content->id));
                        $criteria->add(new TfCriteriaItem($tfValidator, 'online', 1));
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
                $criteria->add(new TfCriteriaItem($tfValidator, 'online', $cleanOnline));
            }
            
            if ($cleanType) {
                if (array_key_exists($cleanType, $contentHandler->getTypes())) {
                    $criteria->add(new TfCriteriaItem($tfValidator, 'type', $cleanType));
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
            $tagSelectBox = $tagHandler->getTagSelectBox($cleanTag);
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
