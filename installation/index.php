<?php

/**
 * Installation script for Tuskfish CMS.
 * 
 * The installation directory should be deleted after use, otherwise someone may decide to reinstall
 * Tuskfish and take over management of your site.
 *
 * @copyright	Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since		1.0
 * @package		installation
 */

// Enable strict type declaration.
declare(strict_types=1);

// Initialise output buffering with gzip compression.
ob_start("ob_gzhandler");

// Include installation language files
include_once "./english.php";

// Check PHP version 7.2+
if (PHP_VERSION_ID < 70200) {
    echo TFISH_PHP_VERSION_TOO_LOW;
    exit;
}

// Check path to mainfile.
if (is_readable("../mainfile.php")) {
    require_once "../mainfile.php";
} else {
    echo TFISH_PATH_TO_MAINFILE_INVALID;
    exit;
}

// HTMLPurifier is a dependency of TfValidator.
require_once TFISH_LIBRARIES_PATH . 'htmlpurifier/library/HTMLPurifier.auto.php';

// Initialise data validator.
$tf_validator_factory = new TfValidatorFactory();
$tf_validator = $tf_validator_factory->getValidator();

// Initialise preference.
$preference_config = array(
    'site_name' => 'Tuskfish CMS',
    'site_description' => 'A cutting edge micro-CMS',
    'site_author' => '',
    'site_copyright' => '',
    'seo' => '',
    'pagination_elements' => '5',
    'enable_cache' => 0
);
$tf_preference = new TfPreference($tf_validator, $preference_config);

// Initialise default content variable.
$tf_content = array('output' => '');

// Set error reporting levels and custom error handler.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_NOTICE);
$tf_logger = new TfLogger($tf_validator);
set_error_handler(array($tf_logger, "logError"));

// Set theme.
$tf_template = new TfTemplate($tf_validator);
$tf_template->setTheme('signin');

$tf_template->tf_url = getUrl();

/**
 * Helper function to grab the site URL and protocol during installation.
 * 
 * @return string Site URL.
 */
function getUrl() {
    $url = @(!isset($_server['HTTPS']) || $_SERVER["HTTPS"] != 'on') ? 'http://'
            . $_SERVER["SERVER_NAME"] : 'https://' . $_SERVER["SERVER_NAME"];
    $url .= ($_SERVER["SERVER_PORT"] != 80 && $_SERVER["SERVER_PORT"] != 443) ? ":"
            . $_SERVER["SERVER_PORT"] : "";
    $url .= '/';
    return $url;
}

// Test and save database credentials.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    ////////////////////////////////////
    ////////// VALIDATE INPUT //////////
    ////////////////////////////////////
    
    // Check that form was completed.
    if (empty($_POST['db_name']) || empty($_POST['admin_email']) || empty($_POST['admin_password'])) {
        $tf_content['output'] .= '<p>' . TFISH_INSTALLATION_COMPLETE_FORM . '</p>';
    }

    // Database name is restricted to alphanumeric and underscore characters only.
    $db_name = $tf_validator->trimString($_POST['db_name']);
    if (!$tf_validator->isAlnumUnderscore($db_name)) {
        $tf_content['output'] .= '<p>' . TFISH_INSTALLATION_DB_ALNUMUNDERSCORE . '</p>';
    }

    // Admin email must conform to email specification.
    $admin_email = $tf_validator->trimString($_POST['admin_email']);
    if (!$tf_validator->isEmail($admin_email)) {
        $tf_content['output'] .= '<p>' . TFISH_INSTALLATION_BAD_EMAIL . '</p>';
    }

    // There are no restrictions on what characters you use for a password. Only only on what you
    // don't use!
    $admin_password = $tf_validator->trimString($_POST['admin_password']);

    // Check password length and quality.
    $security_utility = new TfSecurityUtility();
    $password_quality = $security_utility->checkPasswordStrength($admin_password);

    if ($password_quality['strong'] === false) {
        $tf_content['output'] .= '<p>' . TFISH_INSTALLATION_WEAK_PASSWORD . '</p>';
        unset($password_quality['strong']);
        $tf_content['output'] .= '<ul>';

        foreach ($password_quality as $weakness) {
            $tf_content['output'] .= '<li>' . $weakness . '</li>';
        }
        
        $tf_content['output'] .= '</ul>';
    }
    
    // Report errors.
    if (!empty($tf_content['output'])) {
        $tf_content['output'] = '<h1 class="text-center">' . TFISH_INSTALLATION_WARNING . '</h1>'
                . $tf_content['output'];
        $tf_template->output = $tf_content['output'];
        $tf_template->form = "db_credentials_form.html";
        $tf_template->tf_main_content = $tf_template->render('form');
        
    // All input validated, proceed to process and set up database.    
    } else {
        // Salt and iteratively hash the password 100,000 times to resist brute force attacks.
        $security_utility = new TfSecurityUtility();
        $site_salt = $security_utility->generateSalt(64);
        $user_salt = $security_utility->generateSalt(64);
        $password_hash = TfSession::recursivelyHashPassword($admin_password, 100000,
                $site_salt, $user_salt);

        // Append site salt to config.php.
        $site_salt_constant = 'if (!defined("TFISH_SITE_SALT")) define("TFISH_SITE_SALT", "'
                . $site_salt . '");';
        $tf_file_handler = new TfFileHandler($tf_validator);
        $result = $tf_file_handler->appendToFile(TFISH_CONFIGURATION_PATH, $site_salt_constant);

        if (!$result) {
            trigger_error(TFISH_ERROR_FAILED_TO_APPEND_FILE, E_USER_ERROR);
            exit;
        }

        ////////////////////////////////////
        // INITIALISE THE SQLITE DATABASE //
        ////////////////////////////////////
        
        // Create the database
        $tf_database = new TfDatabase($tf_validator, $tf_logger, $tf_file_handler);
        $db_path = $tf_database->create($db_name);

        if ($db_path) {
            if (!defined("TFISH_DATABASE"))
                define("TFISH_DATABASE", $db_path);
        }

        // Create user table.
        $user_columns = array(
            "id" => "INTEGER",
            "admin_email" => "TEXT",
            "password_hash" => "TEXT",
            "user_salt" => "TEXT",
            "user_group" => "INTEGER",
            "yubikey_id" => "TEXT",
            "yubikey_id2" => "TEXT",
            "login_errors" => "INTEGER"
        );

        $tf_database->createTable('user', $user_columns, 'id');
        // Insert admin user's details to database.
        $user_data = array(
            'admin_email' => $admin_email,
            'password_hash' => $password_hash,
            'user_salt' => $user_salt,
            'user_group' => '1',
            'yubikey_id' => '',
            'yubikey_id2' => '',
            'login_errors' => '0'
            );
        $query = $tf_database->insert('user', $user_data);

        // Create preference table.
        $preference_columns = array(
            "id" => "INTEGER",
            "title" => "TEXT",
            "value" => "TEXT"
        );
        $tf_database->createTable('preference', $preference_columns, 'id');

        // Insert default preferences to database.
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
            array('title' => 'rss_posts', 'value' => '10'),
            array('title' => 'session_name', 'value' => 'tf'),
            array('title' => 'session_life', 'value' => '20'),
            array('title' => 'default_language', 'value' => 'en'),
            array('title' => 'date_format', 'value' => 'j F Y'),
            array('title' => 'enable_cache', 'value' => '0'),
            array('title' => 'cache_life', 'value' => '86400')
        );

        foreach ($preference_data as $preference) {
            $tf_database->insert('preference', $preference, 'id');
        }

        // Create session table.
        $session_columns = array(
            "id" => "INTEGER",
            "last_active" => "INTEGER",
            "data" => "TEXT"
        );
        $tf_database->createTable('session', $session_columns, 'id');

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
        $tf_database->createTable('content', $content_columns, 'id');

        // Insert a "General" tag content object.
        $content_data = array(
            "type" => "TfTag",
            "title" => "General",
            "teaser" => "Default content tag.",
            "description" => "Default content tag, please edit it to something useful.",
            "date" => date('Y-m-d'),
            "language" => "en",
            "online" => "1",
            "submission_time" => time(),
            "counter" => "0",
            "meta_title" => "General",
            "meta_description" => "General information.",
            "seo" => "general");
        $query = $tf_database->insert('content', $content_data);

        // Create taglink table.
        $taglink_columns = array(
            "id" => "INTEGER",
            "tag_id" => "INTEGER",
            "content_type" => "TEXT",
            "content_id" => "INTEGER");
        $tf_database->createTable('taglink', $taglink_columns, 'id');
        
        // Close the database connection.
        $tf_database->close();

        // Report on status of database creation.
        if ($db_path && $query) {
            $tf_template->page_title = TFISH_INSTALLATION_COMPLETE;
            $tf_content['output'] .= '<div class="row"><div class="text-left col-8 offset-2 mt-3"><h3><i class="fas fa-exclamation-triangle text-danger"></i> ' . TFISH_INSTALLATION_SECURE_YOUR_SITE . '</h3></div></div>';
            $tf_content['output'] .= '<div class="row"><div class="text-left col-8 offset-2">' . TFISH_INSTALLATION_SECURITY_INSTRUCTIONS . '</div></div>';
            $tf_template->output = $tf_content['output'];
            $tf_template->form = "success.html";
            $tf_template->tf_main_content = $tf_template->render('form');
        } else {
            // If database creation failed, complain and display data entry form again.
            $tf_content['output'] .= '<p>' . TFISH_INSTALLATION_DATABASE_FAILED . '</p>';
            $tf_template->output = $tf_content['output'];
            $tf_template->form = "db_credentials_form.html";
            $tf_template->tf_main_content = $tf_template->render('form');
        }
    }
} else {
    /**
     * Preflight checks
     */
    $tf_content['output'] .= '<div class="row"><div class="col-xs-6 offset-xs-3 col-lg-4 offset-md-4 text-left">';

    $required_extensions = array('sqlite3', 'PDO', 'pdo_sqlite', 'gd');
    $loaded_extensions = get_loaded_extensions();
    $present_list = '';
    $missing_list = '';

    // Check PHP version 7.2+
    if (PHP_VERSION_ID < 70200) {
        $missing_list = '<li><i class="fas fa-times text-danger"></i> ' . TFISH_PHP_VERSION_TOO_LOW . '</li>';
    } else {
        $present_list = '<li><i class="fas fa-check text-success"></i> ' . TFISH_PHP_VERSION_OK . '</li>';
    }

    // Check extensions.
    foreach ($required_extensions as $extension) {
        if (in_array($extension, $loaded_extensions, true)) {
            $present_list .= '<li><i class="fas fa-check text-success"></i> ' . $extension . ' '
                    . TFISH_EXTENSION . '</li>';
        } else {
            $missing_list .= '<li><i class="fas fa-times text-danger"></i> ' . $extension . ' '
                    . TFISH_EXTENSION . '</li>';
        }
    }

    // Check path to mainfile.
    if (is_readable("../mainfile.php")) {
        $present_list .= '<li><i class="fas fa-check text-success"></i> ' . TFISH_PATH_TO_MAINFILE_OK . '</li>';
    }

    // Check root_path.
    if (defined("TFISH_ROOT_PATH") && is_readable(TFISH_ROOT_PATH)) {
        $present_list .= '<li><i class="fas fa-check text-success"></i> ' . TFISH_ROOT_PATH_OK . '</li>';
    } else {
        $missing_list .= '<li><i class="fas fa-times text-danger"></i> ' . TFISH_ROOT_PATH_INVALID . '</li>';
    }

    // Check trust_path.
    if (defined("TFISH_TRUST_PATH") && is_readable(TFISH_TRUST_PATH)) {
        $present_list .= '<li><i class="fas fa-check text-success"></i> ' . TFISH_TRUST_PATH_OK . '</li>';
    } else {
        $missing_list .= '<li><i class="fas fa-times text-danger"></i> ' . TFISH_TRUST_PATH_INVALID . '</li>';
    }

    if ($present_list) {
        $present_list = '<ul class="fa-ul">' . $present_list . '</ul>';
        $tf_content['output'] .= '<p><b>' . TFISH_SYSTEM_REQUIREMENTS_MET . '</b></p>'
                . $present_list;
    }

    if ($missing_list) {
        $missing_list = '<ul class="fa-ul">' . $missing_list . '</ul>';
        $tf_content['output'] .= '<p><b>' . TFISH_SYSTEM_REQUIREMENTS_NOT_MET . '</b></p>'
                . $missing_list;
    }
    
    $tf_content['output'] .= '</div></div>';
    
    // Display data entry form.
    $tf_template->page_title = TFISH_INSTALLATION_TUSKFISH;
    $tf_template->tf_root_path = realpath('../') . '/';
    $tf_template->form = "db_credentials_form.html";
    $tf_template->tf_main_content = $tf_content['output'] . $tf_template->render('form');
}

/**
 * Manually instantiate the metadata object.
 */
$tf_metadata = new TfMetadata($tf_validator, $tf_preference);
$tf_metadata->setTitle(TFISH_INSTALLATION_TUSKFISH);
$tf_metadata->setDescription('');
$tf_metadata->setRobots('noindex,nofollow');
$tf_metadata->setGenerator(''); // Do not advertise an installation script.

// Manual duplication of footer (as database is not yet available on first page view).
if ($tf_template && !empty($tf_template->getTheme())) {
    include_once TFISH_THEMES_PATH . $tf_template->getTheme() . "/theme.html";
} else {
    include_once TFISH_THEMES_PATH . "default/theme.html";
}

// Flush the output buffer to screen and clear it.
ob_end_flush();
