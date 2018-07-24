<?php

/**
 * Preference management script.
 * 
 * Allows site preferences to be modified.
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
require_once TFISH_ADMIN_PATH . "tf_admin_header.php";

// Specify theme, otherwise 'default' will be used.
$tf_template->setTheme('admin');

// Collect CSRF token if available.
$clean_token = isset($_POST['token']) ? $tf_validator->trimString($_POST['token']) : '';

// Set view option
$op = isset($_REQUEST['op']) ? $tf_validator->trimString($_REQUEST['op']) : false;
if (in_array($op, array('edit', 'update', false), true)) {
    switch ($op) {

        // Edit: Display a data entry form containing the preference settings.
        case "edit":
            TfSession::validateToken($clean_token); // CSRF check.
            $tf_template->page_title = TFISH_PREFERENCE_EDIT_PREFERENCES;
            $tf_template->preferences = $tf_preference->getPreferencesAsArray();
            $tf_template->languages = $tf_preference->getListOfLanguages();
            $tf_template->timezones = TfUtils::getListOfTimezones();
            $tf_template->form = TFISH_FORM_PATH . "preference_edit.html";
            $tf_template->tf_main_content = $tf_template->render('form');
            break;

        // Update: Submit the modified object and update the corresponding database row.
        case "update":
            TfSession::validateToken($clean_token); // CSRF check.
            $tf_preference->loadPropertiesFromArray($_REQUEST);

            // Update the database row and display a response.
            $tf_preference_handler = new TfPreferenceHandler($tf_database);
            $result = $tf_preference_handler->writePreferences($tf_preference);
            
            if ($result) {
                $tf_template->page_title = TFISH_SUCCESS;
                $tf_template->alert_class = 'alert-success';
                $tf_template->message = TFISH_PREFERENCES_WERE_UPDATED;
            } else {
                $tf_template->page_title = TFISH_FAILED;
                $tf_template->alert_class = 'alert-danger';
                $tf_template->message = TFISH_PREFERENCES_UPDATE_FAILED;
            }
            
            $tf_template->back_url = 'preference.php';
            $tf_template->form = TFISH_FORM_PATH . "response.html";
            $tf_template->tf_main_content = $tf_template->render('form');
            
            // Flush the cache.
            $tf_cache->flushCache();
            break;

        // Default: Display a table of existing preferences.
        default:
            $tf_template->page_title = TFISH_PREFERENCES;
            $preferences = $tf_preference->getPreferencesAsArray();
            $languages = $tf_preference->getListOfLanguages();
            $preferences['default_language'] = $languages[$preferences['default_language']];
            $timezones = TfUtils::getListOfTimezones();
            $preferences['server_timezone'] = $timezones[$preferences['server_timezone']];
            $preferences['site_timezone'] = $timezones[$preferences['site_timezone']];
            $preferences['close_site'] = empty($preferences['close_site']) ? TFISH_NO : TFISH_YES;
            $preferences['enable_cache'] = empty($preferences['enable_cache']) ? TFISH_NO : TFISH_YES;
            $tf_template->preferences = $preferences;
            $tf_template->form = TFISH_FORM_PATH . "preference_table.html";
            $tf_template->tf_main_content = $tf_template->render('form');
            break;
    }
} else {
    trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
    exit;
}

/**
 * Override page metadata here (otherwise default site metadata will display).
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
