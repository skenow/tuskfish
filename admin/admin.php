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

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "../mainfile.php";
require_once TFISH_ADMIN_PATH . "tfish_admin_header.php";

// Validate input parameters.
$clean_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;
$clean_online = isset($_GET['online']) ? (int) $_GET['online'] : null;
$clean_type = isset($_GET['type']) && !empty($_GET['type'])
        ? TfishDataValidator::trimString($_GET['type']) : '';
$clean_token = isset($_POST['token']) ? TfishDataValidator::trimString($_POST['token']) : '';
$op = isset($_REQUEST['op']) ? TfishDataValidator::trimString($_REQUEST['op']) : false;

// Specify the admin theme and the template to be used to preview content (user side template).
if ($op === 'view') {
    $tfish_template->setTheme('default');
} else {
    $tfish_template->setTheme('admin');
}

// Set target file for intra-collection pagination controls when viewing objects. False will 
// default to your home page.
$target_file_name = '';
$tfish_template->target_file_name = $target_file_name;

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

if (in_array($op, $options_whitelist)) {
    
    // Cross-site request forgery check for all options except for view and toggle online/offline.
    // The rationale for not including a check on the toggle option is that i) no data is lost,
    // ii) the admin will be alerted to the change by the unexpected display of a confirmation
    // message, iii) the action is trivial to undo and iv) it would reduce the functionality of
    // one-click status toggling.
    if (!in_array($op, array('confirm_delete', 'confirm_flush', 'edit', 'toggle', 'view', false))) {
        TfishSession::validateToken($clean_token);
    }
    
    $content_handler = new TfishContentHandler();
    
    switch ($op) {
        // Add: Display an empty content object submission form.
        case "add":
            $tfish_template->page_title = TFISH_ADD_CONTENT;
            $tfish_template->op = 'submit'; // Critical to launch correct form submission action.
            $tfish_template->content_types = $content_handler->getTypes();
            $tfish_template->rights = $content_handler->getListOfRights();
            $tfish_template->languages = $content_handler->getListOfLanguages();
            $tfish_template->tags = $content_handler->getTagList(false);

            // Make a parent tree select box options.
            $collection_handler = new TfishCollectionHandler();
            $collections = $collection_handler->getObjects();
            $parent_tree = new TfishAngryTree($collections, 'id', 'parent');
            $tfish_template->parent_select_options = $parent_tree->makeParentSelectBox();

            $content = new TfishContentObject();
            $tfish_template->allowed_properties = $content->getPropertyWhitelist();
            $tfish_template->zeroed_properties = array(
                'image' => array('image'),
                'tags' => array(
                    'caption',
                    'format',
                    'file_size',
                    'creator',
                    'media',
                    'date',
                    'parent',
                    'language',
                    'rights',
                    'publisher',
                    'tags')
            );
            $tfish_template->form = TFISH_FORM_PATH . "data_entry.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
            break;

        // Confirm: Confirm deletion of a content object.
        case "confirm_delete":
            if (isset($_REQUEST['id'])) {
                $clean_id = (int) $_REQUEST['id'];
                
                if (TfishDataValidator::isInt($clean_id, 1)) {
                    $tfish_template->page_title = TFISH_CONFIRM_DELETE;
                    $tfish_template->content = $content_handler->getObject($clean_id);
                    $tfish_template->form = TFISH_FORM_PATH . "confirm_delete.html";
                    $tfish_template->tfish_main_content = $tfish_template->render('form');
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;
            
        case "confirm_flush":
            $tfish_template->page_title = TFISH_CONFIRM_FLUSH;
            $tfish_template->form = TFISH_FORM_PATH . "confirm_flush.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
            break;

        // Delete: Delete a content object. ID must be an integer and > 1.
        case "delete":
            if (isset($_REQUEST['id'])) {
                $clean_id = (int) $_REQUEST['id'];
                $result = $content_handler->delete($clean_id);
                
                if ($result) {
                    $tfish_cache->flushCache();
                    $tfish_template->page_title = TFISH_SUCCESS;
                    $tfish_template->alert_class = 'alert-success';
                    $tfish_template->message = TFISH_OBJECT_WAS_DELETED;
                } else {
                    $tfish_template->page_title = TFISH_FAILED;
                    $tfish_template->alert_class = 'alert-danger';
                    $tfish_template->message = TFISH_OBJECT_DELETION_FAILED;
                }
                
                $tfish_template->back_url = 'admin.php';
                $tfish_template->form = TFISH_FORM_PATH . "response.html";
                $tfish_template->tfish_main_content = $tfish_template->render('form');
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;

        // Edit: Display a data entry form containing the object's current properties.
        case "edit":
            if (isset($_REQUEST['id'])) {
                $clean_id = (int) $_REQUEST['id'];
                
                if (TfishDataValidator::isInt($clean_id, 1)) {
                    $criteria = new TfishCriteria();
                    $criteria->add(new TfishCriteriaItem('id', $clean_id));
                    $statement = TfishDatabase::select('content', $criteria);
                    
                    if (!$statement) {
                        trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_NOTICE);
                        header("Location: admin.php");
                    }
                    
                    $row = $statement->fetch(PDO::FETCH_ASSOC);

                    // Make a parent tree select box options.
                    $collection_handler = new TfishCollectionHandler();
                    $collections = $collection_handler->getObjects();
                    $parent_tree = new TfishAngryTree($collections, 'id', 'parent');

                    // Assign to template.
                    $tfish_template->page_title = TFISH_EDIT_CONTENT;
                    $tfish_template->op = 'update'; // Critical to launch correct submission action.
                    $tfish_template->action = TFISH_UPDATE;
                    $tfish_template->content = $content_handler->convertRowToObject($row, false);
                    $tfish_template->content_types = $content_handler->getTypes();
                    $tfish_template->rights = $content_handler->getListOfRights();
                    $tfish_template->languages = $content_handler->getListOfLanguages();
                    $tfish_template->tags = $content_handler->getTagList(false);
                    $tfish_template->parent_select_options = 
                            $parent_tree->makeParentSelectBox((int) $row['parent']);
                    $tfish_template->form = TFISH_FORM_PATH . "data_edit.html";
                    $tfish_template->tfish_main_content = $tfish_template->render('form');
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;

        // Flush the cache.
        case "flush":
            $result = $tfish_cache->flushCache();
            
            if ($result) {
                $tfish_template->page_title = TFISH_SUCCESS;
                $tfish_template->alert_class = 'alert-success';
                $tfish_template->message = TFISH_CACHE_WAS_FLUSHED;
            } else {
                $tfish_template->page_title = TFISH_FAILED;
                $tfish_template->alert_class = 'alert-danger';
                $tfish_template->message = TFISH_CACHE_FLUSH_FAILED;
            }
            
            $tfish_template->back_url = 'admin.php';
            $tfish_template->form = TFISH_FORM_PATH . "response.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
            break;

        // Submit: Determine object type, instantiate, validate input, populate properties  and
        // insert a new content object.
        case "submit":
            if (empty($_REQUEST['type'])) {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                exit;
            }
            
            $clean_type = TfishDataValidator::trimString($_REQUEST['type']);
            $type_whitelist = $content_handler->getTypes();
            
            if (!array_key_exists($clean_type, $type_whitelist)) {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                exit;
            }
            
            $content_object = new $clean_type;
            $content_object->loadPropertiesFromArray($_REQUEST);

            // Insert the object
            $result = $content_handler->insert($content_object);
            
            if ($result) {
                $tfish_cache->flushCache();
                $tfish_template->page_title = TFISH_SUCCESS;
                $tfish_template->alert_class = 'alert-success';
                $tfish_template->message = TFISH_OBJECT_WAS_INSERTED;
            } else {
                $tfish_template->title = TFISH_FAILED;
                $tfish_template->alert_class = 'alert-danger';
                $tfish_template->message = TFISH_OBJECT_INSERTION_FAILED;
            }
            
            $tfish_template->back_url = 'admin.php';
            $tfish_template->form = TFISH_FORM_PATH . "response.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
            break;

        // Toggle the online status of a particular object.
        case "toggle":
            $id = (int) $_REQUEST['id'];
            $clean_id = TfishDataValidator::isInt($id, 1) ? $id : 0;
            $result = $content_handler->toggleOnlineStatus($clean_id);
            
            if ($result) {
                $tfish_cache->flushCache();
                $tfish_template->page_title = TFISH_SUCCESS;
                $tfish_template->alert_class = 'alert-success';
                $tfish_template->message = TFISH_OBJECT_WAS_UPDATED;
            } else {
                $tfish_template->page_title = TFISH_FAILED;
                $tfish_template->alert_class = 'alert-danger';
                $tfish_template->message = TFISH_OBJECT_UPDATE_FAILED;
            }
            
            $tfish_template->back_url = 'admin.php';
            $tfish_template->form = TFISH_FORM_PATH . "response.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
            break;

        // Update: Submit the modified object and update the corresponding database row.
        case "update":
            if (empty($_REQUEST['type'])) {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                exit;
            }

            $type = TfishDataValidator::trimString($_REQUEST['type']);
            $type_whitelist = $content_handler->getTypes();
            
            if (!array_key_exists($type, $type_whitelist)) {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                exit;
            }

            $content_object = new $type;
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
                $tfish_cache->flushCache();
                $tfish_template->page_title = TFISH_SUCCESS;
                $tfish_template->alert_class = 'alert-success';
                $tfish_template->message = TFISH_OBJECT_WAS_UPDATED;
                $tfish_template->id = $content_object->id;
            } else {
                $tfish_template->page_title = TFISH_FAILED;
                $tfish_template->alert_class = 'alert-danger';
                $tfish_template->message = TFISH_OBJECT_UPDATE_FAILED;
            }

            $tfish_template->back_url = 'admin.php';
            $tfish_template->form = TFISH_FORM_PATH . "response_edit.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
            break;

        // View: See the user-side display of a single object, including offline objects.
        case "view":
            if ($clean_id) {
                $content = $content_handler->getObject($clean_id);
                
                if (is_object($content)) {
                    $tfish_template->content = $content;

                    // Prepare meta information for display.
                    $contentInfo = array();
                    
                    if ($content->creator) $contentInfo[] = $content->escapeForXss('creator');
                    
                    if ($content->date) $contentInfo[] = $content->escapeForXss('date');
                    
                    if ($content->counter) {
                        switch ($content->type) {
                            case "TfishDownload": // Display 'downloads' after the counter.
                                $contentInfo[] = $content->escapeForXss('counter') . ' '
                                    . TFISH_DOWNLOADS;
                                break;
                            
                            // Display 'downloads' after the counter if there is an attached media
                            // file; otherwise 'views'.
                            case "TfishCollection":
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
                    
                    $tfish_template->contentInfo = implode(' | ', $contentInfo);
                    
                    if ($content->meta_title) $tfish_metadata->title = $content->meta_title;
                    
                    if ($content->meta_description) $tfish_metadata->description 
                        = $content->meta_description;

                    // Check if has a parental object; if so display a thumbnail and teaser / link.
                    if (!empty($content->parent)) {
                        $parent = $content_handler->getObject($content->parent);
                        
                        if (is_object($parent) && $parent->online) {
                            $tfish_template->parent = $parent;
                        }
                    }

                    // Initialise criteria object.
                    $criteria = new TfishCriteria();
                    $criteria->order = 'date';
                    $criteria->ordertype = 'DESC';

                    // If object is a collection check if has child objects; if so display
                    // thumbnails and teasers / links.
                    if ($content->type === 'TfishCollection') {
                        $criteria->add(new TfishCriteriaItem('parent', $content->id));
                        $criteria->add(new TfishCriteriaItem('online', 1));
                        
                        if ($clean_start) $criteria->offset = $clean_start;
                        
                        $criteria->limit = $tfish_preference->user_pagination;
                    }

                    // If object is a tag, then a different method is required to call the related
                    // content.
                    if ($content->type === 'TfishTag') {
                        if ($clean_start) $criteria->offset = $clean_start;
                        
                        $criteria->limit = $tfish_preference->user_pagination;
                        $criteria->tag = array($content->id);
                        $criteria->add(new TfishCriteriaItem('online', 1));
                    }

                    // Prepare pagination control.
                    $tfish_pagination = new TfishPaginationControl($tfish_preference);
                    
                    if ($content->type === 'TfishCollection' || $content->type === 'TfishTag') {
                        $first_child_count = $content_handler->getCount($criteria);
                        $tfish_template->collection_pagination = 
                                $tfish_pagination->getPaginationControl(
                                        $first_child_count, 
                                        $tfish_preference->user_pagination,
                                        $target_file_name,
                                        $clean_start,
                                        0,
                                        array('id' => $clean_id));

                        // Retrieve content objects and assign to template.
                        $first_children = $content_handler->getObjects($criteria);
                        
                        if (!empty($first_children)) {
                            $tfish_template->first_children = $first_children;
                        }
                    }

                    // Render template.
                    $tfish_template->tfish_main_content
                            = $tfish_template->render($content->template);
                } else {
                    $tfish_template->tfish_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
                }
            }
            break;

        // Default: Display a table of existing content objects and pagination controls.
        default:
            $criteria = new TfishCriteria;

            // Select box filter input.
            if ($clean_tag) $criteria->tag = array($clean_tag);
            
            if (TfishDataValidator::isInt($clean_online, 0, 1)) {
                $criteria->add(new TfishCriteriaItem('online', $clean_online));
            }
            
            if ($clean_type) {
                if (array_key_exists($clean_type, $content_handler->getTypes())) {
                    $criteria->add(new TfishCriteriaItem('type', $clean_type));
                } else {
                    trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
                }
            }

            // Other criteria.
            $criteria->offset = $clean_start;
            $criteria->limit = $tfish_preference->admin_pagination;
            $criteria->order = 'submission_time';
            $criteria->ordertype = 'DESC';
            $columns = array('id', 'type', 'title', 'submission_time', 'counter', 'online');
            $result = TfishDatabase::select('content', $criteria, $columns);
            
            if ($result) {
                $rows = $result->fetchAll(PDO::FETCH_ASSOC);
            } else {
                trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
            }
            
            foreach ($rows as &$row) {
                $row['submission_time']
                        = date($tfish_preference->date_format, (int) $row['submission_time']);
            }
            
            $typelist = $content_handler->getTypes();

            // Pagination control.
            $tfish_pagination = new TfishPaginationControl($tfish_preference);
            $count = TfishDatabase::selectCount('content', $criteria);
            $extra_params = array();
            
            if (isset($clean_online) && TfishDataValidator::isInt($clean_online, 0, 1)) {
                $extra_params['online'] = $clean_online;
            }
            
            if (isset($clean_type)) {
                $extra_params['type'] = $clean_type;
            }
            
            $tfish_template->pagination = $tfish_pagination->getPaginationControl(
                    $count,
                    $tfish_preference->admin_pagination,
                    'admin',
                    $clean_start,
                    $clean_tag,
                    $extra_params);

            // Prepare select filters.
            $tag_handler = new TfishTagHandler();
            $tag_select_box = $tag_handler->getTagSelectBox($clean_tag);
            $type_select_box = $content_handler->getTypeSelectBox($clean_type);
            $online_select_box = $content_handler->getOnlineSelectBox($clean_online);
            $tfish_template->select_action = 'admin.php';
            $tfish_template->tag_select = $tag_select_box;
            $tfish_template->type_select = $type_select_box;
            $tfish_template->online_select = $online_select_box;
            $tfish_template->select_filters_form = $tfish_template->render('admin_select_filters');

            // Assign to template.
            $tfish_template->page_title = TFISH_CURRENT_CONTENT;
            $tfish_template->rows = $rows;
            $tfish_template->typelist = $content_handler->getTypes();
            $tfish_template->form = TFISH_FORM_PATH . "content_table.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
            break;
    }
} else {
    trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
    exit;
}

/**
 * Override page template here (otherwise default site metadata will display).
 */
// $tfish_metadata->title = '';
// $tfish_metadata->description = '';
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
$tfish_metadata->robots = 'noindex,nofollow';

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";
