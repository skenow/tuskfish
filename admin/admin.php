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
require_once TFISH_ADMIN_PATH . "tf_admin_header.php";

// 3. Content header sets module-specific paths and makes TfContentHandlerFactory available.
require_once TFISH_MODULE_PATH . "content/tf_content_header.php";

// Validate input parameters.
$clean_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;
$clean_online = isset($_GET['online']) ? (int) $_GET['online'] : null;
$clean_type = isset($_GET['type']) && !empty($_GET['type'])
        ? $tf_validator->trimString($_GET['type']) : '';
$clean_token = isset($_POST['token']) ? $tf_validator->trimString($_POST['token']) : '';
$op = isset($_REQUEST['op']) ? $tf_validator->trimString($_REQUEST['op']) : false;

// Specify the admin theme and the template to be used to preview content (user side template).
if ($op === 'view') {
    $tf_template->setTheme('default');
} else {
    $tf_template->setTheme('admin');
}

// Set target file for intra-collection pagination controls when viewing objects. False will 
// default to your home page.
$target_file_name = '';
$tf_template->target_file_name = $target_file_name;

// Permitted options.
$options_whitelist = array(
    'add',
    'confirm_delete',
    'confirm_flush',
    'delete',
    'edit',
    'flush',
    'submit',
    'toggle',
    'update',
    'view',
    false
    );

if (in_array($op, $options_whitelist, true)) {
    
    // Cross-site request forgery check for all options except for view and toggle online/offline.
    // The rationale for not including a check on the toggle option is that i) no data is lost,
    // ii) the admin will be alerted to the change by the unexpected display of a confirmation
    // message, iii) the action is trivial to undo and iv) it would reduce the functionality of
    // one-click status toggling.
    if (!in_array($op, array('confirm_delete', 'confirm_flush', 'edit', 'toggle', 'view', false), true)) {
        TfSession::validateToken($clean_token);
    }
    
    $content_handler = $content_handler_factory->getHandler('content');
    
    switch ($op) {
        // Add: Display an empty content object submission form.
        case "add":
            $content = new TfContentObject($tf_validator);
            
            $tf_template->page_title = TFISH_ADD_CONTENT;
            $tf_template->op = 'submit'; // Critical to launch correct form submission action.
            $tf_template->content_types = $content_handler->getTypes();
            $tf_template->rights = $content->getListOfRights();
            $tf_template->languages = $tf_preference->getListOfLanguages();
            $tf_template->tags = $content_handler->getTagList(false);

            // Make a parent tree select box options.
            $collection_handler = $content_handler_factory->getHandler('collection');
            $collections = $collection_handler->getObjects();
            $parent_tree = new TfAngryTree($collections, 'id', 'parent');
            $tf_template->parent_select_options = $parent_tree->makeParentSelectBox();

            $tf_template->allowed_properties = $content->getPropertyWhitelist();
            $tf_template->zeroed_properties = array(
                'image' => array('image'),
                'tags' => array(
                    'creator',
                    'language',
                    'rights',
                    'publisher',
                    'tags')
            );
            $tf_template->form = TFISH_CONTENT_MODULE_FORM_PATH . "data_entry.html";
            $tf_template->tf_main_content = $tf_template->render('form');
            break;

        // Confirm: Confirm deletion of a content object.
        case "confirm_delete":
            if (isset($_REQUEST['id'])) {
                $clean_id = (int) $_REQUEST['id'];
                
                if ($tf_validator->isInt($clean_id, 1)) {
                    $tf_template->page_title = TFISH_CONFIRM_DELETE;
                    $tf_template->content = $content_handler->getObject($clean_id);
                    $tf_template->form = TFISH_CONTENT_MODULE_FORM_PATH . "confirm_delete.html";
                    $tf_template->tf_main_content = $tf_template->render('form');
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;
            
        case "confirm_flush":
            $tf_template->page_title = TFISH_CONFIRM_FLUSH;
            $tf_template->form = TFISH_FORM_PATH . "confirm_flush.html";
            $tf_template->tf_main_content = $tf_template->render('form');
            break;

        // Delete: Delete a content object. ID must be an integer and > 1.
        case "delete":
            if (isset($_REQUEST['id'])) {
                $clean_id = (int) $_REQUEST['id'];
                $result = $content_handler->delete($clean_id);
                
                if ($result) {
                    $tf_cache->flushCache();
                    $tf_template->page_title = TFISH_SUCCESS;
                    $tf_template->alert_class = 'alert-success';
                    $tf_template->message = TFISH_OBJECT_WAS_DELETED;
                } else {
                    $tf_template->page_title = TFISH_FAILED;
                    $tf_template->alert_class = 'alert-danger';
                    $tf_template->message = TFISH_OBJECT_DELETION_FAILED;
                }
                
                $tf_template->back_url = 'admin.php';
                $tf_template->form = TFISH_FORM_PATH . "response.html";
                $tf_template->tf_main_content = $tf_template->render('form');
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;

        // Edit: Display a data entry form containing the object's current properties.
        case "edit":
            if (isset($_REQUEST['id'])) {
                $clean_id = (int) $_REQUEST['id'];
                
                if ($tf_validator->isInt($clean_id, 1)) {
                    $criteria = $tf_criteria_factory->getCriteria();
                    $criteria->add(new TfCriteriaItem($tf_validator, 'id', $clean_id));
                    $statement = $tf_database->select('content', $criteria);
                    
                    if (!$statement) {
                        trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_NOTICE);
                        header("Location: admin.php");
                    }
                    $row = $statement->fetch(PDO::FETCH_ASSOC);

                    // Make a parent tree select box options.
                    $collection_handler = $content_handler_factory->getHandler('collection');
                    $collections = $collection_handler->getObjects();
                    $parent_tree = new TfAngryTree($collections, 'id', 'parent');
                    
                    // Build the content object.
                    $content = $content_handler->convertRowToObject($row, false);
                    
                    // Assign to template.
                    $tf_template->page_title = TFISH_EDIT_CONTENT;
                    $tf_template->op = 'update'; // Critical to launch correct submission action.
                    $tf_template->action = TFISH_UPDATE;
                    $tf_template->content = $content;
                    $tf_template->content_types = $content_handler->getTypes();
                    $tf_template->rights = $content->getListOfRights();
                    $tf_template->languages = $tf_preference->getListOfLanguages();
                    $tf_template->tags = $content_handler->getTagList(false);
                    $tf_template->parent_select_options = 
                            $parent_tree->makeParentSelectBox((int) $row['parent']);
                    $tf_template->form = TFISH_CONTENT_MODULE_FORM_PATH . "data_edit.html";
                    $tf_template->tf_main_content = $tf_template->render('form');
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;

        // Flush the cache.
        case "flush":
            $result = $tf_cache->flushCache();
            
            if ($result) {
                $tf_template->page_title = TFISH_SUCCESS;
                $tf_template->alert_class = 'alert-success';
                $tf_template->message = TFISH_CACHE_WAS_FLUSHED;
            } else {
                $tf_template->page_title = TFISH_FAILED;
                $tf_template->alert_class = 'alert-danger';
                $tf_template->message = TFISH_CACHE_FLUSH_FAILED;
            }
            
            $tf_template->back_url = 'admin.php';
            $tf_template->form = TFISH_FORM_PATH . "response.html";
            $tf_template->tf_main_content = $tf_template->render('form');
            break;

        // Submit: Determine object type, instantiate, validate input, populate properties  and
        // insert a new content object.
        case "submit":
            if (empty($_REQUEST['type'])) {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                exit;
            }
            
            $clean_type = $tf_validator->trimString($_REQUEST['type']);
            
            $type_whitelist = $content_handler->getTypes();
            
            if (!array_key_exists($clean_type, $type_whitelist)) {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                exit;
            }
            
            $content_object = new $clean_type($tf_validator);
            $content_object->loadPropertiesFromArray($_REQUEST);

            // Insert the object
            $result = $content_handler->insert($content_object);

            if ($result) {
                $tf_cache->flushCache();
                $tf_template->page_title = TFISH_SUCCESS;
                $tf_template->alert_class = 'alert-success';
                $tf_template->message = TFISH_OBJECT_WAS_INSERTED;
            } else {
                $tf_template->title = TFISH_FAILED;
                $tf_template->alert_class = 'alert-danger';
                $tf_template->message = TFISH_OBJECT_INSERTION_FAILED;
            }
            
            $tf_template->back_url = 'admin.php';
            $tf_template->form = TFISH_FORM_PATH . "response.html";
            $tf_template->tf_main_content = $tf_template->render('form');
            break;

        // Toggle the online status of a particular object.
        case "toggle":
            $id = (int) $_REQUEST['id'];
            $clean_id = $tf_validator->isInt($id, 1) ? $id : 0;
            $result = $content_handler->toggleOnlineStatus($clean_id);
            
            if ($result) {
                $tf_cache->flushCache();
                $tf_template->page_title = TFISH_SUCCESS;
                $tf_template->alert_class = 'alert-success';
                $tf_template->message = TFISH_OBJECT_WAS_UPDATED;
            } else {
                $tf_template->page_title = TFISH_FAILED;
                $tf_template->alert_class = 'alert-danger';
                $tf_template->message = TFISH_OBJECT_UPDATE_FAILED;
            }
            
            $tf_template->back_url = 'admin.php';
            $tf_template->form = TFISH_FORM_PATH . "response.html";
            $tf_template->tf_main_content = $tf_template->render('form');
            break;

        // Update: Submit the modified object and update the corresponding database row.
        case "update":
            if (empty($_REQUEST['type'])) {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                exit;
            }

            $type = $tf_validator->trimString($_REQUEST['type']);
            $type_whitelist = $content_handler->getTypes();
            
            if (!array_key_exists($type, $type_whitelist)) {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                exit;
            }

            $content_object = new $type($tf_validator);
            $content_object->loadPropertiesFromArray($_REQUEST);

            // As this object is being sent to storage, need to decode some entities that got
            // encoded for display.
            $fields_to_decode = array('title', 'creator', 'publisher', 'caption');

            foreach ($fields_to_decode as $field) {
                if (isset($content_object->field)) {
                    $content_object->$field = htmlspecialchars_decode($content_object->field,
                            ENT_NOQUOTES);
                }
            }

            // Properties that are used within attributes must have quotes encoded.
            $fields_to_decode = array('meta_title', 'seo', 'meta_description');
            
            foreach ($fields_to_decode as $field) {
                if (isset($content_object->field)) {
                    $content_object->$field = htmlspecialchars_decode($content_object->field,
                            ENT_QUOTES);
                }
            }

            // Update the database row and display a response.
            $result = $content_handler->update($content_object);

            if ($result) {
                $tf_cache->flushCache();
                $tf_template->page_title = TFISH_SUCCESS;
                $tf_template->alert_class = 'alert-success';
                $tf_template->message = TFISH_OBJECT_WAS_UPDATED;
                $tf_template->id = $content_object->id;
            } else {
                $tf_template->page_title = TFISH_FAILED;
                $tf_template->alert_class = 'alert-danger';
                $tf_template->message = TFISH_OBJECT_UPDATE_FAILED;
            }

            $tf_template->back_url = 'admin.php';
            $tf_template->form = TFISH_CONTENT_MODULE_FORM_PATH . "response_edit.html";
            $tf_template->tf_main_content = $tf_template->render('form');
            break;

        // View: See the user-side display of a single object, including offline objects.
        case "view":
            if ($clean_id) {
                $content = $content_handler->getObject($clean_id);
                
                if (is_object($content)) {
                    $tf_template->content = $content;

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
                    
                    if ($content->file_size)
                        $contentInfo[] = $content->escapeForXss('file_size');
                    
                    // For a content type-specific page use $content->tags, $content->template.
                    if ($content->tags) {
                        $tags = $content_handler->makeTagLinks($content->tags);
                        $tags = TFISH_TAGS . ': ' . implode(', ', $tags);
                        $contentInfo[] = $tags;
                    }
                    
                    $tf_template->contentInfo = implode(' | ', $contentInfo);
                    
                    if ($content->meta_title) $tf_metadata->setTitle($content->meta_title);
                    
                    if ($content->meta_description) $tf_metadata->setDescription($content->meta_description);

                    // Check if has a parental object; if so display a thumbnail and teaser / link.
                    if (!empty($content->parent)) {
                        $parent = $content_handler->getObject($content->parent);
                        
                        if (is_object($parent) && $parent->online) {
                            $tf_template->parent = $parent;
                        }
                    }

                    // Initialise criteria object.
                    $criteria = $tf_criteria_factory->getCriteria();
                    $criteria->setOrder('date');
                    $criteria->setOrderType('DESC');
                    $criteria->setSecondaryOrder('submission_time');
                    $criteria->setSecondaryOrderType('DESC');

                    // If object is a collection check if has child objects; if so display
                    // thumbnails and teasers / links.
                    if ($content->type === 'TfCollection') {
                        $criteria->add(new TfCriteriaItem($tf_validator, 'parent', $content->id));
                        $criteria->add(new TfCriteriaItem($tf_validator, 'online', 1));
                        
                        if ($clean_start) $criteria->setOffset($clean_start);
                        
                        $criteria->setLimit($tf_preference->user_pagination);
                    }

                    // If object is a tag, then a different method is required to call the related
                    // content.
                    if ($content->type === 'TfTag') {
                        if ($clean_start) $criteria->setOffset($clean_start);
                        
                        $criteria->setLimit($tf_preference->user_pagination);
                        $criteria->setTag(array($content->id));
                        $criteria->add(new TfCriteriaItem($tf_validator, 'online', 1));
                    }

                    // Prepare pagination control.
                    if ($content->type === 'TfCollection' || $content->type === 'TfTag') {                        
                        $tf_pagination = new TfPaginationControl($tf_validator, $tf_preference);
                        $tf_pagination->setUrl($target_file_name);
                        $tf_pagination->setCount($content_handler->getCount($criteria));
                        $tf_pagination->setLimit($tf_preference->user_pagination);
                        $tf_pagination->setStart($clean_start);
                        $tf_pagination->setTag(0);
                        $tf_pagination->setExtraParams(array('id' => $clean_id));
                        $tf_template->collection_pagination = $tf_pagination->getPaginationControl();

                        // Retrieve content objects and assign to template.
                        $first_children = $content_handler->getObjects($criteria);
                        
                        if (!empty($first_children)) {
                            $tf_template->first_children = $first_children;
                        }
                    }

                    // Render template.
                    $tf_template->tf_main_content
                            = $tf_template->render($content->template);
                } else {
                    $tf_template->tf_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
                }
            }
            break;

        // Default: Display a table of existing content objects and pagination controls.
        default:
            $criteria = $tf_criteria_factory->getCriteria();

            // Select box filter input.
            if ($clean_tag) $criteria->setTag(array($clean_tag));
            
            if ($tf_validator->isInt($clean_online, 0, 1)) {
                $criteria->add(new TfCriteriaItem($tf_validator, 'online', $clean_online));
            }
            
            if ($clean_type) {
                if (array_key_exists($clean_type, $content_handler->getTypes())) {
                    $criteria->add(new TfCriteriaItem($tf_validator, 'type', $clean_type));
                } else {
                    trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                }
            }

            // Other criteria.
            $criteria->setOffset($clean_start);
            $criteria->setLimit($tf_preference->admin_pagination);
            $criteria->setOrder('submission_time');
            $criteria->setOrderType('DESC');
            $columns = array('id', 'type', 'title', 'submission_time', 'counter', 'online');
            $result = $tf_database->select('content', $criteria, $columns);
            
            if ($result) {
                $rows = $result->fetchAll(PDO::FETCH_ASSOC);
            } else {
                trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
            }
            
            foreach ($rows as &$row) {
                $row['submission_time']
                        = date($tf_preference->date_format, (int) $row['submission_time']);
            }
            
            $typelist = $content_handler->getTypes();

            // Pagination control.
            $extra_params = array();
            if (isset($clean_online) && $tf_validator->isInt($clean_online, 0, 1)) {
                $extra_params['online'] = $clean_online;
            }
            if (isset($clean_type) && !empty($clean_type)) {
                $extra_params['type'] = $clean_type;
            }
            
            $tf_pagination = new TfPaginationControl($tf_validator, $tf_preference);
            $tf_pagination->setUrl('admin');
            $tf_pagination->setCount($tf_database->selectCount('content', $criteria));
            $tf_pagination->setLimit($tf_preference->admin_pagination);
            $tf_pagination->setStart($clean_start);
            $tf_pagination->setTag($clean_tag);
            $tf_pagination->setExtraParams($extra_params);
            $tf_template->pagination = $tf_pagination->getPaginationControl();

            // Prepare select filters.
            $tag_handler = $content_handler_factory->getHandler('tag');
            $tag_select_box = $tag_handler->getTagSelectBox($clean_tag);
            $type_select_box = $content_handler->getTypeSelectBox($clean_type);
            $online_select_box = $content_handler->getOnlineSelectBox($clean_online);
            $tf_template->select_action = 'admin.php';
            $tf_template->tag_select = $tag_select_box;
            $tf_template->type_select = $type_select_box;
            $tf_template->online_select = $online_select_box;
            $tf_template->select_filters_form = $tf_template->render('admin_select_filters');

            // Assign to template.
            $tf_template->page_title = TFISH_CURRENT_CONTENT;
            $tf_template->rows = $rows;
            $tf_template->typelist = $content_handler->getTypes();
            $tf_template->form = TFISH_CONTENT_MODULE_FORM_PATH . "content_table.html";
            $tf_template->tf_main_content = $tf_template->render('form');
            break;
    }
} else {
    trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
    exit;
}

/**
 * Override page template here (otherwise default site metadata will display).
 */
// $tf_metadata->setTitle('');
// $tf_metadata->setDescription('');
// $tf_metadata->setAuthor('');
// $tf_metadata->setCopyright('');
// $tf_metadata->setGenerator('');
// $tf_metadata->setSeo('');
$tf_metadata->setRobots('noindex,nofollow');

// Include page template and flush buffer
require_once TFISH_PATH . "tf_footer.php";
