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
require_once TFISH_ADMIN_PATH . "tfAdminHeader.php";

// Specify theme, otherwise 'default' will be used.
$tfTemplate->setTheme('admin');

// Collect CSRF token if available.
$cleanToken = isset($_POST['token']) ? $tfValidator->trimString($_POST['token']) : '';

// Set view option
$op = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : false;
if (in_array($op, array('edit', 'update', false), true)) {
    switch ($op) {

        // Edit: Display a data entry form containing the preference settings.
        case "edit":
            TfSession::validateToken($cleanToken); // CSRF check.
            $tfTemplate->pageTitle = TFISH_PREFERENCE_EDIT_PREFERENCES;
            $tfTemplate->preferences = $tfPreference->getPreferencesAsArray();
            $tfTemplate->languages = $tfPreference->getListOfLanguages();
            $tfTemplate->timezones = TfUtils::getListOfTimezones();
            $tfTemplate->form = TFISH_FORM_PATH . "preferenceEdit.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            break;

        // Update: Submit the modified object and update the corresponding database row.
        case "update":
            TfSession::validateToken($cleanToken); // CSRF check.
            $tfPreference->loadPropertiesFromArray($_REQUEST);

            // Update the database row and display a response.
            $tfPreferenceHandler = new TfPreferenceHandler($tfDatabase);
            $result = $tfPreferenceHandler->writePreferences($tfPreference);
            
            if ($result) {
                $tfTemplate->pageTitle = TFISH_SUCCESS;
                $tfTemplate->alertClass = 'alert-success';
                $tfTemplate->message = TFISH_PREFERENCES_WERE_UPDATED;
            } else {
                $tfTemplate->pageTitle = TFISH_FAILED;
                $tfTemplate->alertClass = 'alert-danger';
                $tfTemplate->message = TFISH_PREFERENCES_UPDATE_FAILED;
            }
            
            $tfTemplate->backUrl = 'preference.php';
            $tfTemplate->form = TFISH_FORM_PATH . "response.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            
            // Flush the cache.
            $tfCache->flushCache();
            break;

        // Default: Display a table of existing preferences.
        default:
            $tfTemplate->pageTitle = TFISH_PREFERENCES;
            $preferences = $tfPreference->getPreferencesAsArray();
            $languages = $tfPreference->getListOfLanguages();
            $preferences['defaultLanguage'] = $languages[$preferences['defaultLanguage']];
            $timezones = TfUtils::getListOfTimezones();
            $preferences['serverTimezone'] = $timezones[$preferences['serverTimezone']];
            $preferences['siteTimezone'] = $timezones[$preferences['siteTimezone']];
            $preferences['closeSite'] = empty($preferences['closeSite']) ? TFISH_NO : TFISH_YES;
            $preferences['enableCache'] = empty($preferences['enableCache']) ? TFISH_NO : TFISH_YES;
            $tfTemplate->preferences = $preferences;
            $tfTemplate->form = TFISH_FORM_PATH . "preferenceTable.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            break;
    }
} else {
    trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
    exit;
}

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
// $tfMetadata->setTitle('');
// $tfMetadata->setDescription('');
// $tfMetadata->setAuthor('');
// $tfMetadata->setCopyright('');
// $tfMetadata->setGenerator('');
// $tfMetadata->setSeo('');
$tfMetadata->setRobots('noindex,nofollow');

// Include page template and flush buffer
require_once TFISH_PATH . "tfFooter.php";
