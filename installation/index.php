<?php

/**
 * Installation script for Tuskfish CMS.
 * 
 * The installation directory should be deleted after use, otherwise someone may decide to reinstall
 * Tuskfish and take over management of your site.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since		1.0
 * @package		installation
 */
// Initialise output buffering with gzip compression.
ob_start("ob_gzhandler");

// Boot!
require_once "../mainfile.php";

// Set error reporting levels and custom error handler.
error_reporting(E_ALL & ~E_NOTICE);
set_error_handler("TfishLogger::logErrors");

// Include installation language files
include_once "./english.php";

// Set theme.
$tfish_template = new TfishTemplate();
$tfish_template->setTheme('admin');

// No preferences available yet, so just set up a preference analogue
$tfish_preference = new stdClass();
$tfish_preference->site_name = 'Tuskfish CMS';
$tfish_preference->site_description = 'A cutting edge micro-CMS';
$tfish_preference->site_author = '';
$tfish_preference->site_copyright = '';
$tfish_preference->generator = 'Tuskfish CMS';
$tfish_preference->seo = '';
$tfish_preference->robots = 'noindex,nofollow';
$tfish_preference->pagination_elements = '5';

// Initialise default content variable
$tfish_content = array('output' => '');

/** Helper function to grab the site URL. */
function getUrl() {
    $url = @(!isset($_server['HTTPS']) || $_SERVER["HTTPS"] != 'on') ? 'http://'
            . $_SERVER["SERVER_NAME"] : 'https://' . $_SERVER["SERVER_NAME"];
    $url .= ($_SERVER["SERVER_PORT"] != 80 && $_SERVER["SERVER_PORT"] != 443) ? ":"
            . $_SERVER["SERVER_PORT"] : "";
    $url .= '/';
    return $url;
}

// Test and save database credentials
if ($_SERVER['REQUEST_METHOD'] == 'POST') { 
    ////////////////////////////////////
    ////////// VALIDATE INPUT //////////
    ////////////////////////////////////
    
    // Check that form was completed.
    if (empty($_POST['db_name']) || empty($_POST['admin_email']) || empty($_POST['admin_password']) 
            || empty($_POST['hmac_key'])) {
        $tfish_content['output'] .= '<p>' . TFISH_INSTALLATION_COMPLETE_FORM . '</p>';
    }

    // Database name is restricted to alphanumeric and underscore characters only.
    $db_name = TfishFilter::trimString($_POST['db_name']);
    if (!TfishFilter::isAlnumUnderscore($db_name)) {
        $tfish_content['output'] .= '<p>' . TFISH_INSTALLATION_DB_ALNUMUNDERSCORE . '</p>';
    }

    // Admin email must conform to email specification.
    $admin_email = TfishFilter::trimString($_POST['admin_email']);
    if (!TfishFilter::isEmail($admin_email)) {
        $tfish_content['output'] .= '<p>' . TFISH_INSTALLATION_BAD_EMAIL . '</p>';
    }

    // There are no restrictions on what characters you use for a password. Only only on what you
    // don't use!
    $admin_password = TfishFilter::trimString($_POST['admin_password']);

    // HMAC key must be alphanumeric characters only. Actually it's only quotes that cause a
    // problem as they break strings unless you escape them, and I don't want to get into the
    // business of escaping keys at this stage. Maybe later.
    $hmac_key = TfishFilter::trimString($_POST['hmac_key']);
    if (!TfishFilter::isAlnum($hmac_key)) {
        $tfish_content['output'] .= '<p>' . TFISH_INSTALLATION_HMAC_ALNUM . '</p>';
    }

    // Check password length and quality
    $password_quality = TfishSecurityUtility::checkPasswordStrength($admin_password);

    if (!$password_quality) {
        $tfish_content['output'] .= '<p>' . TFISH_INSTALLATION_WEAK_PASSWORD . '</p>';
        unset($password_quality['strong']);
        $tfish_content['output'] .= '<ul>';

        foreach ($password_quality as $weakness) {
            $tfish_content['output'] .= '<li>' . $weakness . '</li>';
        }
        
        $tfish_content['output'] .= '</ul>';
    }
    
    // Report errors.
    if (!empty($tfish_content['output'])) {
        $tfish_content['output'] = '<h1>' . TFISH_INSTALLATION_WARNING . '</h1>'
                . $tfish_content['output'];
        
        $tfish_template->output = $tfish_content['output'];
        $tfish_template->form = "db_credentials_form.html";
        $tfish_template->tfish_main_content = $tfish_template->render('form');
        
    // All input validated, proceed to process and set up database.    
    } else {
        // Salt and iteratively hash the password 100,000 times to resist brute force attacks
        $site_salt = TfishSecurityUtility::generateSalt(64);
        $user_salt = TfishSecurityUtility::generateSalt(64);
        $password_hash = TfishSecurityUtility::recursivelyHashPassword($admin_password, 100000,
                $site_salt, $user_salt);

        // Append site salt to config.php
        $site_salt_constant = 'if (!defined("TFISH_SITE_SALT")) define("TFISH_SITE_SALT", "'
                . $site_salt . '");';
        $result = TfishFileHandler::appendFile(TFISH_CONFIGURATION_PATH, $site_salt_constant);

        if (!$result) {
            trigger_error(TFISH_ERROR_FAILED_TO_APPEND_FILE, E_USER_ERROR);
            exit;
        }

        // Append HMAC key to config.php
        $hmac_key = 'if (!defined("TFISH_KEY")) define("TFISH_KEY", "' . $hmac_key . '");';
        $result = TfishFileHandler::appendFile(TFISH_CONFIGURATION_PATH, $hmac_key);

        if (!$result) {
            trigger_error(TFISH_ERROR_FAILED_TO_APPEND_FILE, E_USER_ERROR);
            exit;
        }

        ////////////////////////////////////
        // INITIALISE THE SQLITE DATABASE //
        ////////////////////////////////////
        // Create the database
        $db_path = TfishDatabase::create($db_name);

        if ($db_path) {
            if (!defined("TFISH_DATABASE"))
                define("TFISH_DATABASE", $db_path);
        }

        // Create user table
        $user_columns = array(
            "id" => "INTEGER",
            "admin_email" => "TEXT",
            "password_hash" => "TEXT",
            "user_salt" => "TEXT",
            "user_group" => "INTEGER",
            "yubikey_id" => "TEXT",
            "yubikey_id2" => "TEXT"
        );

        TfishDatabase::createTable('user', $user_columns, 'id');
        // Insert admin user's details to database
        $user_data = array(
            'admin_email' => $admin_email,
            'password_hash' => $password_hash,
            'user_salt' => $user_salt,
            'user_group' => '1',
            'yubikey_id' => '',
            'yubikey_id2' => ''
            );
        $query = TfishDatabase::insert('user', $user_data);

        // Create preference table
        $preference_columns = array(
            "id" => "INTEGER",
            "title" => "TEXT",
            "value" => "TEXT"
        );
        TfishDatabase::createTable('preference', $preference_columns, 'id');

        // Insert default preferences to database
        $preference_data = array(
            array('title' => 'site_name', 'value' => 'Tuskfish CMS'),
            array('title' => 'site_description', 'value' => 'A cutting edge micro CMS'),
            array('title' => 'site_author', 'value' => ''),
            array('title' => 'site_email', 'value' => $admin_email),
            array('title' => 'site_copyright', 'value' => 'Copyright all rights reserved'),
            array('title' => 'close_site', 'value' => '0'),
            array('title' => 'server_timezone', 'value' => '0'),
            array('title' => 'site_timezone', 'value' => '0'),
            array('title' => 'min_search_length', 'value' => '3'),
            array('title' => 'search_pagination', 'value' => '20'),
            array('title' => 'user_pagination', 'value' => '10'),
            array('title' => 'admin_pagination', 'value' => '20'),
            array('title' => 'gallery_pagination', 'value' => '20'),
            array('title' => 'pagination_elements', 'value' => '5'),
            array('title' => 'session_name', 'value' => 'tfish'),
            array('title' => 'session_life', 'value' => '20'),
            array('title' => 'default_language', 'value' => 'en'),
            array('title' => 'date_format', 'value' => 'j F Y'),
            array('title' => 'enable_cache', 'value' => '0'),
            array('title' => 'cache_life', 'value' => '86400')
        );

        foreach ($preference_data as $preference) {
            TfishDatabase::insert('preference', $preference, 'id');
        }

        // Create session table
        $session_columns = array(
            "id" => "INTEGER",
            "last_active" => "INTEGER",
            "data" => "TEXT"
        );
        TfishDatabase::createTable('session', $session_columns, 'id');

        // Create content object table. Note that the type must be first column to enable
        // the PDO::FETCH_CLASS|PDO::FETCH_CLASSTYPE functionality, which automatically
        // pulls DB rows into an instance of a class, based on the first column.
        $content_columns = array(
            "type" => "TEXT", // article => , image => , audio => , etc.
            "id" => "INTEGER", // Auto-increment => , set by database.
            "title" => "TEXT", // The headline or name of this content.
            "teaser" => "TEXT", // A short (one paragraph) summary or abstract for this content.
            "description" => "TEXT", // The full article or description of the content.
            "media" => "TEXT", // URL of an associated audio file.
            "format" => "TEXT", // Mimetype
            "file_size" => "INTEGER", // Specify in bytes.
            "creator" => "TEXT", // Author.
            "image" => "TEXT", // URL of an associated image file => , eg. a screenshot a good way to handle it.
            "caption" => "TEXT", // Caption of the image file.
            "date" => "TEXT", // Date of first publication expressed as a string, hopefully in a standard format to allow time/date conversion.
            "parent" => "INTEGER", // A source work or collection of which this content is part.
            "language" => "TEXT", // English (future proofing).
            "rights" => "INTEGER", // Intellectual property rights scheme or license under which the work is distributed.
            "publisher" => "TEXT", // The entity responsible for distributing this work.
            "online" => "INTEGER", // Toggle object on or offline
            "submission_time" => "INTEGER", // Timestamp representing submission time.
            "counter" => "INTEGER", // Number of times this content was viewed or downloaded.
            "meta_title" => "TEXT", // Set a custom page title for this content.
            "meta_description" => "TEXT", // Set a custom page meta description for this content.
            "seo" => "TEXT"); // SEO-friendly string; it will be appended to the URL for this content.
        TfishDatabase::createTable('content', $content_columns, 'id');

        // Insert a "General" tag content object
        $content_data = array(
            "type" => "TfishTag",
            "title" => "General",
            "teaser" => "Default content tag.",
            "description" => "Default content tag, please edit it to something useful.",
            "language" => "en",
            "online" => "1",
            "submission_time" => time(),
            "counter" => "0",
            "meta_title" => "General",
            "meta_description" => "General information.",
            "seo" => "general");
        $query = TfishDatabase::insert('content', $content_data);

        // Create taglink table
        $taglink_columns = array(
            "id" => "INTEGER",
            "tag_id" => "INTEGER",
            "content_type" => "TEXT",
            "content_id" => "INTEGER");
        TfishDatabase::createTable('taglink', $taglink_columns, 'id');

        // Close database
        TfishDatabase::close();

        // Report on status of database creation
        if ($db_path && $query) {
            $tfish_content['output'] .= '<h3>' . TFISH_INSTALLATION_SECURE_YOUR_SITE . '</h3>';
            $tfish_content['output'] .= TFISH_INSTALLATION_SECURITY_INSTRUCTIONS;
            $tfish_template->output = $tfish_content['output'];
            $tfish_template->form = "success.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
        } else {
            // If database creation failed, complain and display data entry form again
            $tfish_content['output'] .= '<p>' . TFISH_INSTALLATION_DATABASE_FAILED . '</p>';
            $tfish_template->output = $tfish_content['output'];
            $tfish_template->form = "db_credentials_form.html";
            $tfish_template->tfish_main_content = $tfish_template->render('form');
        }
    }
} else {
    // Display data entry form
    $tfish_template->page_title = TFISH_INSTALLATION_TUSKFISH;
    $tfish_template->tfish_url = getUrl();
    $tfish_template->tfish_root_path = realpath('../') . '/';
    $tfish_template->form = "db_credentials_form.html";
    $tfish_template->tfish_main_content = $tfish_template->render('form');
}

/**
 * Manually instantiate the metadata object.
 */
$tfish_metadata = new TfishMetadata($tfish_preference);
$tfish_metadata->title = TFISH_INSTALLATION_TUSKFISH;
$tfish_metadata->description = TFISH_INSTALLATION_DESCRIPTION;

require_once TFISH_PATH . "tfish_footer.php";
