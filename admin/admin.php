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

// Set view option
$op = isset($_REQUEST['op']) ? TfishFilter::trimString($_REQUEST['op']) : false;
if (in_array($op, array('add', 'confirm', 'delete', 'edit', 'submit', 'update', 'view', false))) {
	switch ($op) {
		
		// Add: Display an empty content object submission form.
		case "add":
			$content_types = TfishContentHandler::getTypes();
			$rights = TfishContentHandler::getRights();
			$languages = TfishContentHandler::getLanguages();
			$tfish_form = TFISH_FORM_PATH . "data_entry.html";
		break;
		
		// Confirm: Confirm deletion of a content object.
		case "confirm":
			if (isset($_REQUEST['id'])) {
				$clean_id = (int)$_REQUEST['id'];
				if (TfishFilter::isInt($clean_id, 1)) {
					$content = TfishContentHandler::getObject($clean_id);
					$tfish_form = TFISH_FORM_PATH . "confirm_delete.html";
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
				if (TfishFilter::isInt($clean_id, 1)) {
					$result = TfishDatabase::delete('content', $clean_id);
					if ($result) {
						$alert_class = 'alert-success';
						$title = TFISH_SUCCESS;
						$message = TFISH_OBJECT_WAS_DELETED;
					} else {
						$alert_class = 'alert-danger';
						$title = TFISH_FAILED;
						$message = TFISH_OBJECT_DELETION_FAILED;
					}
					$back_url = 'admin.php';
					$tfish_form = TFISH_FORM_PATH . "response.html";
				} else {
					trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
				}
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
					$content = TfishContentHandler::toObject($row);
					$content_types = TfishContentHandler::getTypes();
					$rights = TfishContentHandler::getRights();
					$languages = TfishContentHandler::getLanguages();
					$tfish_form = TFISH_FORM_PATH . "data_edit.html";
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
			$content_handler = new $content_object->handler;
			
			// Insert the object
			$result = $content_handler->insert($content_object);
			if ($result) {
				$alert_class = 'alert-success';
				$title = TFISH_SUCCESS;
				$message = TFISH_OBJECT_WAS_INSERTED;
			} else {
				$alert_class = 'alert-danger';
				$title = TFISH_FAILED;
				$message = TFISH_OBJECT_INSERTION_FAILED;
			}
			$back_url = 'admin.php';
			$tfish_form = TFISH_FORM_PATH . "response.html";
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
			$content_handler = new $content_object->handler;
			
			// Update the database row and display a response.
			$result = $content_handler->update($content_object);
			if ($result) {
				$alert_class = 'alert-success';
				$title = TFISH_SUCCESS;
				$message = TFISH_OBJECT_WAS_UPDATED;
			} else {
				$alert_class = 'alert-danger';
				$title = TFISH_FAILED;
				$message = TFISH_OBJECT_UPDATE_FAILED;
			}
			$back_url = 'admin.php';
			$tfish_form = TFISH_FORM_PATH . "response.html";
			
		break;
	
		// View: Display a content object.
		case "view":
			echo 'View';
		break;
		
		// Default: Display a table of existing content objects and pagination controls.
		default:
			$criteria = new TfishCriteria;
			$criteria->order = 'submission_time';
			$criteria->ordertype = 'DESC';
			$criteria->limit = 10; // Preference
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
			$tfish_form = TFISH_FORM_PATH . "content_table.html";
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
$tfish_metadata->template = 'admin.html';

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";