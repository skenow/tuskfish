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

// Validate input parameters.
$clean_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;
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
            $tfish_template->genders = TfishContactHandler::getGenders();
            $tfish_template->countries = TfishContactHandler::getCountries();
            $tfish_template->tags = TfishContactHandler::getTagList(false);
            
            $contact = new TfishContact();            
            $tfish_template->form = TFISH_FORM_PATH . "contact_entry.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
            break;
        
        case "confirm_delete":
            break;
        
        case "delete":
            break;
        
        case "edit":
            break;
        
        case "submit":
            break;
        
        case "update":
            break;
        
        case "view":
            break;

        // Default: Display a table of existing content objects and pagination controls.
        default:
            $tfish_template->page_title = TFISH_CONTACTS;
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