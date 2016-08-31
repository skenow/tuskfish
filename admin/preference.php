<?php

/**
* Tuskfish preference management script. Modify preferences as required.
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
if (in_array($op, array('edit', 'update', false))) {
	switch ($op) {

		// Edit: Display a data entry form containing the preference settings.
		case "edit":
			$preferences = TfishPreference::readPreferences();
			$tfish_form = TFISH_FORM_PATH . "preference_edit.html";
		break;
		
		// Update: Submit the modified object and update the corresponding database row.
		case "update":
			//$tfish_form = TFISH_FORM_PATH . "something.html";
		break;
		
		// Default: Display a table of existing preferences.
		default:
			$preferences = TfishPreference::readPreferences();
			$tfish_form = TFISH_FORM_PATH . "preference_table.html";
		break;
	}
} else {
	trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
	exit;
}

/**
 * Override page template and metadata here (otherwise default site metadata will display).
 */
$tfish_metadata->template = 'admin.html';
// $tfish_metadata->title = '';
// $tfish_metadata->description = '';
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
$tfish_metadata->robots = 'noindex,nofollow';

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";