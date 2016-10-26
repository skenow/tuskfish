<?php

/**
* Tuskfish preference management script.
* 
* Allows preferences to be modified.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "../mainfile.php";
require_once TFISH_ADMIN_PATH . "tfish_admin_header.php";

// Specify template set, otherwise 'default' will be used.
$tfish_template->template_set = 'admin';

// Set view option
$op = isset($_REQUEST['op']) ? TfishFilter::trimString($_REQUEST['op']) : false;
if (in_array($op, array('edit', 'update', false))) {
	switch ($op) {

		// Edit: Display a data entry form containing the preference settings.
		case "edit":
			$tfish_template->page_title = TFISH_PREFERENCE_EDIT_PREFERENCES;
			$tfish_template->preferences = TfishPreference::readPreferences();
			$tfish_template->languages = TfishContentHandler::getLanguages();
			$tfish_template->timezones = TfishUtils::getTimezones();
			$tfish_template->form = TFISH_FORM_PATH . "preference_edit.html";
			$tfish_template->tfish_main_content = $tfish_template->render('form');
		break;
		
		// Update: Submit the modified object and update the corresponding database row.
		case "update":
			$tfish_preference->updatePreferences($_REQUEST);
			
			// Update the database row and display a response.
			$result = TfishPreferenceHandler::updatePreferences($tfish_preference);
			if ($result) {
				$tfish_template->page_title = TFISH_SUCCESS;
				$tfish_template->alert_class = 'alert-success';
				$tfish_template->message = TFISH_PREFERENCES_WERE_UPDATED;
			} else {
				$tfish_template->page_title = TFISH_FAILED;
				$tfish_template->alert_class = 'alert-danger';
				$tfish_template->message = TFISH_PREFERENCES_UPDATE_FAILED;
			}
			$tfish_template->back_url = 'preference.php';
			$tfish_template->form = TFISH_FORM_PATH . "response.html";
			$tfish_template->tfish_main_content = $tfish_template->render('form');
		break;
		
		// Default: Display a table of existing preferences.
		default:
			$tfish_template->page_title = TFISH_PREFERENCES;
			$preferences = TfishPreference::readPreferences();
			$languages = TfishContentHandler::getLanguages();
			$preferences['default_language'] = $languages[$preferences['default_language']];
			$timezones = TfishUtils::getTimezones();
			$preferences['server_timezone'] = $timezones[$preferences['server_timezone']];
			$preferences['site_timezone'] = $timezones[$preferences['site_timezone']];
			$preferences['close_site'] = empty($preferences['close_site']) ? TFISH_NO : TFISH_YES;
			$tfish_template->preferences = $preferences;
			$tfish_template->form = TFISH_FORM_PATH . "preference_table.html";
			$tfish_template->tfish_main_content = $tfish_template->render('form');
		break;
	}
} else {
	trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
	exit;
}

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
// $tfish_metadata->title = '';
// $tfish_metadata->description = '';
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
// $tfish_metadata->robots = '';
$tfish_metadata->robots = 'noindex,nofollow';

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";