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
require_once TFISH_ADMIN_PATH . "tfish_admin_header.php";

// Specify theme, otherwise 'default' will be used.
$tfish_template->setTheme('admin');

// Collect CSRF token if available.
$clean_token = isset($_POST['token']) ? TfishDataValidator::trimString($_POST['token']) : '';

// Set view option
$op = isset($_REQUEST['op']) ? TfishDataValidator::trimString($_REQUEST['op']) : false;
if (in_array($op, array('edit', 'update', false))) {
    switch ($op) {

        // Edit: Display a data entry form containing the preference settings.
        case "edit":
            TfishSession::validateToken($clean_token); // CSRF check.
            $tfish_template->page_title = TFISH_PREFERENCE_EDIT_PREFERENCES;
            $tfish_template->preferences = TfishPreference::readPreferences();
            $content_handler = new TfishContentHandler();
            $tfish_template->languages = $content_handler->getListOfLanguages();
            $tfish_template->timezones = TfishUtils::getTimezones();
            $tfish_template->form = TFISH_FORM_PATH . "preference_edit.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
            break;

        // Update: Submit the modified object and update the corresponding database row.
        case "update":
            TfishSession::validateToken($clean_token); // CSRF check.
            $tfish_preference->loadPropertiesFromArray($_REQUEST);

            // Update the database row and display a response.
            $tfish_preference_handler = new TfishPreferenceHandler;
            $result = $tfish_preference_handler->writePreferences($tfish_preference);
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
            
            // Flush the cache.
            $tfish_cache->flushCache();
            break;

        // Default: Display a table of existing preferences.
        default:
            $tfish_template->page_title = TFISH_PREFERENCES;
            $preferences = TfishPreference::readPreferences();
            $content_handler = new TfishContentHandler();
            $languages = $content_handler->getListOfLanguages();
            $preferences['default_language'] = $languages[$preferences['default_language']];
            $timezones = TfishUtils::getTimezones();
            $preferences['server_timezone'] = $timezones[$preferences['server_timezone']];
            $preferences['site_timezone'] = $timezones[$preferences['site_timezone']];
            $preferences['close_site'] = empty($preferences['close_site']) ? TFISH_NO : TFISH_YES;
            $preferences['enable_cache'] = empty($preferences['enable_cache']) ? TFISH_NO : TFISH_YES;
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
