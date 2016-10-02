<?php

/**
* Tuskfish content management script. Add, edit or delete content objects as required.
* 
* Site preferences can be accessed via $tfish_preference->key;
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "../mainfile.php";
require_once TFISH_ADMIN_PATH . "tfish_admin_header.php";

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int)$_GET['tag_id'] : 0;
$clean_status = isset($_GET['status']) ? (int)$_GET['status'] : null;
$clean_type = isset($_GET['type']) ? TfishFilter::trimString($_GET['type']) : null;
$op = isset($_REQUEST['op']) ? TfishFilter::trimString($_REQUEST['op']) : false;

if (in_array($op, array('add', 'confirm', 'delete', 'edit', 'submit', 'toggle', 'update', 'view', false))) {
	switch ($op) {
		
		// Add: Display an empty content object submission form.
		case "add":
			$tfish_template->page_title = TFISH_ADD_CONTENT;
			$tfish_template->content_types = TfishContentHandler::getTypes();
			$tfish_template->rights = TfishContentHandler::getRights();
			$tfish_template->languages = TfishContentHandler::getLanguages();
			$tfish_template->tags = TfishContentHandler::getTagList();
			$tfish_template->form = TFISH_FORM_PATH . "data_entry.html";
			$tfish_template->tfish_main_content = $tfish_template->render('form');
		break;
		
		// Confirm: Confirm deletion of a content object.
		case "confirm":
			if (isset($_REQUEST['id'])) {
				$clean_id = (int)$_REQUEST['id'];
				if (TfishFilter::isInt($clean_id, 1)) {
					$tfish_template->page_title = TFISH_CONFIRM_DELETE;
					$tfish_template->content = TfishContentHandler::getObject($clean_id);
					$tfish_template->form = TFISH_FORM_PATH . "confirm_delete.html";
					$tfish_template->tfish_main_content = $tfish_template->render('form');
				} else {
					trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
				}
			} else {
				trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
			}
		break;
		
		// Delete: Delete a content object. ID must be an integer and > 1.
		case "delete":
			if (isset($_REQUEST['id'])) {
				$clean_id = (int)$_REQUEST['id'];
				$result = TfishContentHandler::delete($clean_id);
				if ($result) {
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
				$clean_id = (int)$_REQUEST['id'];
				if (TfishFilter::isInt($clean_id, 1)) {
					$criteria = new TfishCriteria();
					$criteria->add(new TfishCriteriaItem('id', $clean_id));
					$statement = TfishDatabase::select('content', $criteria);
					if (!$statement) {
						trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_NOTICE);
						header("Location: admin.php");
					}
					$row = $statement->fetch(PDO::FETCH_ASSOC);
					
					// Assign to template.
					$tfish_template->page_title = TFISH_EDIT_CONTENT;
					$tfish_template->content = TfishContentHandler::toObject($row);
					$tfish_template->content_types = TfishContentHandler::getTypes();
					$tfish_template->rights = TfishContentHandler::getRights();
					$tfish_template->languages = TfishContentHandler::getLanguages();
					$tfish_template->tags = TfishContentHandler::getTagList();
					$tfish_template->form = TFISH_FORM_PATH . "data_edit.html";
					$tfish_template->tfish_main_content = $tfish_template->render('form');
				} else {
					trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
				}
			} else {
				trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
			}
		break;
	
		// Submit: Determine object type, instantiate, validate input, populate properties  and and
		// insert a new content object.
		case "submit":
			// Determine the type of object. This is not a great way to do it, there needs to be
			// a config file somewhere, or perhaps it could just check that the class is available
			// (but then it would need to be restricted to content types).
			if (empty($_REQUEST['type'])) {
				trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
				exit;
			}
			
			$type = TfishFilter::trimString($_REQUEST['type']);
			$type_whitelist =  TfishContentHandler::getTypes();
			if (!array_key_exists($type, $type_whitelist)) {
				trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
				exit;
			}
			$content_object = new $type;
			$content_object->loadProperties($_REQUEST);
			
			// Insert the object
			$result = TfishContentHandler::insert($content_object);
			if ($result) {
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
			$id = (int)$_REQUEST['id'];
			$clean_id = TfishFilter::isInt($id, 1) ? $id : 0;
			$result = TfishContentHandler::toggleOnlineStatus($clean_id);
			if ($result) {
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
			$type = TfishFilter::trimString($_REQUEST['type']);
			$type_whitelist =  TfishContentHandler::getTypes();
			if (!array_key_exists($type, $type_whitelist)) {
				trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
				exit;
			}
			$content_object = new $type;
			$content_object->loadProperties($_REQUEST);
			
			// Update the database row and display a response.
			$result = TfishContentHandler::update($content_object);
			if ($result) {
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
	
		// View: Display a content object.
		case "view":
			echo 'View';
		break;
		
		// Default: Display a table of existing content objects and pagination controls.
		default:
			$criteria = new TfishCriteria;
			
			// Select box filter input.
			if ($clean_tag) $criteria->tag = array($clean_tag);
			if (TfishFilter::isInt($clean_status, 0, 1)) {
				$criteria->add(new TfishCriteriaItem('online', $clean_status));
			}
			if ($clean_type) {
				if (array_key_exists($clean_type, TfishContentHandler::getTypes())) {
					$criteria->add(new TfishCriteriaItem('type', $clean_type));
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
				$row['submission_time'] = date($tfish_preference->date_format, $row['submission_time']);
			}
			$typelist = TfishContentHandler::getTypes();
			
			// Pagination control.
			$count = TfishDatabase::selectCount('content', $criteria);
			$tfish_template->pagination = $tfish_metadata->getPaginationControl($count, $tfish_preference->admin_pagination, 'admin', $clean_start, $clean_tag);
			
			// Prepare select filters.
			$tag_select_box = TfishTagHandler::getTagSelectBox('', $clean_tag);
			$status_select_box = TfishContentHandler::getOnlineSelectBox('', $clean_status);
			$type_select_box = TfishContentHandler::getTypeSelectBox('', $clean_type);
			$select_filters = '<form name="select_filters" action="admin.php" method="get">';
			$select_filters .= $tag_select_box;
			$select_filters .= $status_select_box;
			$select_filters .= $type_select_box;
			$select_filters .= '</form>';
			$tfish_template->select_filters = $select_filters;
			
			// Assign to template.
			$tfish_template->page_title = TFISH_CURRENT_CONTENT;
			$tfish_form = TFISH_FORM_PATH . "content_table.html";
			$tfish_template->rows = $rows;
			$tfish_template->typelist = TfishContentHandler::getTypes();
			$tfish_template->form = TFISH_FORM_PATH . "content_table.html";
			$tfish_template->tfish_main_content = $tfish_template->render('form'); 
		break;
	}
} else {
	trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
	exit;
}

/**
 * Override page template and metadata here (otherwise default site metadata will display).
 */
// $tfish_metadata->title = '';
// $tfish_metadata->description = '';
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
$tfish_metadata->robots = 'noindex,nofollow';
//$tfish_metadata->template = 'admin.html';

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";