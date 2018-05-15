<?php

/**
 * Add, edit or delete contacts as required.
 *
 * This is the administrative page for the contacts database.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
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

/**
 * Configuration
 * 
 * Set the ID of the collections that holds your activity / country tags. These are used to build
 * the activity / country select boxes.
 */

$activity_collection = 11;
$country_collection = 14;

// Validate input parameters.
$clean_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;
$clean_country = isset($_GET['country_id']) ? (int) $_GET['country_id'] : 0;
$clean_year = isset($_GET['year']) ? (int) $_GET['year'] : 0;
$clean_token = isset($_POST['token']) ? TfishFilter::trimString($_POST['token']) : '';
$op = isset($_REQUEST['op']) ? TfishFilter::trimString($_REQUEST['op']) : false;

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
    'delete',
    'edit',
    'submit',
    'update',
    'view',
    false);

if (in_array($op, $options_whitelist)) {
    
    // Cross-site request forgery check for all options except for view and toggle online/offline.
    // The rationale for not including a check on the toggle option is that i) no data is lost,
    // ii) the admin will be alerted to the change by the unexpected display of a confirmation
    // message, iii) the action is trivial to undo and iv) it would reduce the functionality of
    // one-click status toggling.
    if (!in_array($op, array('confirm_delete', 'edit', 'view', false))) {
        TfishSession::validateToken($clean_token);
    }
    
    switch ($op) {
        case "add":
            $tfish_template->page_title = TFISH_ADD_CONTACT;
            $tfish_template->op = 'submit'; // Critical to launch correct form submission action.
            $tfish_template->titles = TfishContactHandler::getTitles();
            
            // Build activities (tag) select box.
            $criteria = new TfishCriteria();
            $criteria->add(new TfishCriteriaItem('parent', $activity_collection));
            $taglist = TfishTagHandler::getList($criteria);
            $tfish_template->tags = array(0 => '---') + $taglist;
            unset($criteria);
            
            // Build countries (tag) select box.
            $criteria = new TfishCriteria();
            $criteria->add(new TfishCriteriaItem('parent', $country_collection));
            $country_list = TfishTagHandler::getList($criteria);
            asort($country_list);
            $tfish_template->country_list = array(0 => '---') + $country_list;
            
            //$tfish_template->countries = TfishContactHandler::getCountries();
            //$tfish_template->tags = TfishContactHandler::getTagList(false);
            
            $contact = new TfishContact();            
            $tfish_template->form = TFISH_FORM_PATH . "contact_entry.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
            break;
        
        case "confirm_delete":
            if (isset($_REQUEST['id'])) {
                $clean_id = (int) $_REQUEST['id'];
                
                if (TfishFilter::isInt($clean_id, 1)) {
                    $tfish_template->page_title = TFISH_CONFIRM_DELETE;
                    $tfish_template->contact = TfishContactHandler::getObject($clean_id);
                    $tfish_template->form = TFISH_FORM_PATH . "confirm_delete_contact.html";
                    $tfish_template->tfish_main_content = $tfish_template->render('form');
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;
        
        case "delete":
            if (isset($_REQUEST['id'])) {
                $clean_id = (int) $_REQUEST['id'];
                $result = TfishContactHandler::delete($clean_id);
                
                if ($result) {
                    TfishCache::flushCache();
                    $tfish_template->page_title = TFISH_SUCCESS;
                    $tfish_template->alert_class = 'alert-success';
                    $tfish_template->message = TFISH_OBJECT_WAS_DELETED;
                } else {
                    $tfish_template->page_title = TFISH_FAILED;
                    $tfish_template->alert_class = 'alert-danger';
                    $tfish_template->message = TFISH_OBJECT_DELETION_FAILED;
                }
                
                $tfish_template->back_url = 'contacts.php';
                $tfish_template->form = TFISH_FORM_PATH . "response.html";
                $tfish_template->tfish_main_content = $tfish_template->render('form');
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;
        
        case "edit":
            if (isset($_REQUEST['id'])) {
                $clean_id = (int) $_REQUEST['id'];
                
                if (TfishFilter::isInt($clean_id, 1)) {
                    $criteria = new TfishCriteria();
                    $criteria->add(new TfishCriteriaItem('id', $clean_id));
                    $statement = TfishDatabase::select('contact', $criteria);
                    
                    if (!$statement) {
                        trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_NOTICE);
                        header("Location: contact.php");
                    }
                    
                    $row = $statement->fetch(PDO::FETCH_ASSOC);

                    // Populate a contact object
                    $contact = new TfishContact();
                    foreach ($row as $key => $value) {
                        if (isset($contact->$key)) {
                            $contact->$key = $value;
                        }
                    }
                    
                    // Build titles select box.
                    $tfish_template->titles = TfishContactHandler::getTitles();

                    // Build activities (tag) select box.
                    $criteria = new TfishCriteria();
                    $criteria->add(new TfishCriteriaItem('parent', $activity_collection));
                    $tfish_template->tags = array(0 => '---') + TfishTagHandler::getList($criteria);

                    // Build countries (tag) select box.
                    $criteria = new TfishCriteria();
                    $criteria->add(new TfishCriteriaItem('parent', $country_collection));
                    $country_list = TfishTagHandler::getList($criteria);
                    asort($country_list);
                    $tfish_template->country_list = array(0 => '---') + $country_list;

                    // Assign to template.
                    $tfish_template->page_title = TFISH_EDIT_CONTACT;
                    $tfish_template->op = 'update'; // Critical to launch correct submission action.
                    $tfish_template->action = TFISH_UPDATE;
                    $tfish_template->contact = $contact;
                    $tfish_template->form = TFISH_FORM_PATH . "contact_edit.html";
                    $tfish_template->tfish_main_content = $tfish_template->render('form');
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;
        
        case "submit":
            // Populate a contact object.
            $contact = new TfishContact();
            foreach ($_REQUEST as $key => $value) {
                if (isset($contact->$key)) {
                    $contact->$key = $_REQUEST[$key]; // Note that object does internal validation.
                }
            }

            // Insert the object
            $result = TfishContactHandler::insert($contact);
 
            if ($result) {
                TfishCache::flushCache();
                $tfish_template->page_title = TFISH_SUCCESS;
                $tfish_template->alert_class = 'alert-success';
                $tfish_template->message = TFISH_OBJECT_WAS_INSERTED;
            } else {
                $tfish_template->title = TFISH_FAILED;
                $tfish_template->alert_class = 'alert-danger';
                $tfish_template->message = TFISH_OBJECT_INSERTION_FAILED;
            }
            
            $tfish_template->back_url = 'contacts.php';
            $tfish_template->form = TFISH_FORM_PATH . "response.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
            break;
        
        case "update":
            // Populate a contact object.
            $contact = new TfishContact();
            foreach ($_REQUEST as $key => $value) {
                if (isset($contact->$key)) {
                    $contact->$key = $_REQUEST[$key]; // Note that object does internal validation.
                }
            }
            
            // As this object is being sent to storage, need to decode some entities that got
            // encoded for display.
            $fields_to_decode = array('firstname', 'midname', 'lastname', 'job', 'business_unit',
                'organisation', 'email', 'state', 'mobile');

            foreach ($fields_to_decode as $field) {
                if (isset($contact->field)) {
                    $contact->$field = htmlspecialchars_decode($contact->field,
                            ENT_NOQUOTES);
                }
            }

            // Update the database row and display a response.
            $result = TfishContactHandler::update($contact);

            if ($result) {
                TfishCache::flushCache();
                $tfish_template->page_title = TFISH_SUCCESS;
                $tfish_template->alert_class = 'alert-success';
                $tfish_template->message = TFISH_OBJECT_WAS_UPDATED;
                $tfish_template->id = $contact->id;
            } else {
                $tfish_template->page_title = TFISH_FAILED;
                $tfish_template->alert_class = 'alert-danger';
                $tfish_template->message = TFISH_OBJECT_UPDATE_FAILED;
            }

            $tfish_template->back_url = 'contacts.php';
            $tfish_template->form = TFISH_FORM_PATH . "response_edit_contact.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
            break;
        
        case "view":
            if ($clean_id) {
                $contact = TfishContactHandler::getObject($clean_id);
                
                if (is_object($contact)) {
                    $tfish_template->contact = $contact;
                    $tfish_template->titles = TfishContactHandler::getTitles();
                    $criteria = new TfishCriteria();
                    $criteria->add(new TfishCriteriaItem('parent', $country_collection));
                    $country_list = TfishTagHandler::getList($criteria);
                    asort($country_list);
                    $tfish_template->country_list = array(0 => '---') + $country_list;

                    // Render template.
                    $tfish_template->tfish_main_content
                            = $tfish_template->render($contact->template);
                } else {
                    $tfish_template->tfish_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
                }
            }
            break;

        // Default: Display a table of existing content objects and pagination controls.
        default:
            $criteria = new TfishCriteria;

            // Select box filter input.
            if ($clean_tag) {
                $criteria->add(new TfishCriteriaItem('tags', $clean_tag));
            }
            
            if ($clean_country) {
                $criteria->add(new TfishCriteriaItem('country', $clean_country));
            }

            // Other criteria.
            $criteria->offset = $clean_start;
            $criteria->limit = $tfish_preference->admin_pagination;
            $criteria->order = 'lastname';
            $criteria->ordertype = 'ASC';
            $columns = array('id', 'title', 'firstname', 'lastname', 'gender', 'job',
                'organisation', 'email');
            $result = TfishDatabase::select('contact', $criteria, $columns);
            
            if ($result) {
                $rows = $result->fetchAll(PDO::FETCH_ASSOC);
            } else {
                trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
            }

            // Pagination control.
            $count = TfishDatabase::selectCount('contact', $criteria);
            $extra_params = array();
            
            $tfish_template->pagination = $tfish_metadata->getPaginationControl(
                    $count,
                    $tfish_preference->admin_pagination,
                    'contacts',
                    $clean_start,
                    $clean_tag,
                    $extra_params);

            unset($criteria);
            
            // Country select filter.
            $criteria = new TfishCriteria();
            $criteria->add(new TfishCriteriaItem('parent', $country_collection));
            $country_list = TfishTagHandler::getList($criteria);
            asort($country_list);
            $country_select = TfishTagHandler::getArbitraryTagSelectBox($clean_country, $country_list,
                    'country_id', '-- All countries --');
            unset($criteria);
            
            // Activity select filter.
            $criteria = new TfishCriteria();
            $criteria->add(new TfishCriteriaItem('parent', $activity_collection));
            $activity_list = TfishTagHandler::getList($criteria);
            $activity_select = TfishTagHandler::getArbitraryTagSelectBox($clean_tag, $activity_list,
                    'tag_id', '-- All activities --');
            unset($criteria);
            
            // Year select filter. Retrieve dates for activities (using existing $criteria),
            // compute years. Remove duplicates and sort chronologically.
            $activity_objects = TfishTagHandler::getObjects($criteria);
            $dates = array();
            foreach ($activity_objects as $activity) {
                $years[] = date("Y", strtotime($activity->date));
            }
            $years = array_unique($years);
            $year_select = '<select class="form-control custom-select" name="year" id="year" '
                    . 'onchange="this.form.submit()">';
            if (!$clean_year) {
                $year_select .= '<option value="0" selected>-- All years --</option>';
            } else {
                $year_select .= '<option value="0">-- All years --</option>';
            }
            foreach ($years as $year) {
                $year_select .= ($clean_year == $year) ? '<option value="' . $year . '" selected>'
                        . $year . '</option>' : '<option value="' . $year . '">' . $year . '</option>';
            }
            $year_select .= '</select>';
            
            $tfish_template->select_action = 'contacts.php';
            $tfish_template->country_select = $country_select;
            $tfish_template->activity_select = $activity_select;
            $tfish_template->year_select = $year_select;
            $tfish_template->select_filters_form = $tfish_template->render('contact_filters');

            // Assign to template.
            $tfish_template->page_title = TFISH_CONTACTS;
            $tfish_template->rows = $rows;
            $tfish_template->titles = TfishContactHandler::getTitles();
            $tfish_template->form = TFISH_FORM_PATH . "contact_table.html";            
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