<?php

/**
 * Login controller script.
 * 
 * Handles password-based login to site. For two-factor authentication with Yubikey hardware tokens
 * see trust_path/extras/login_two_factor.php.
 * 
 * tf_header is manually duplicated on this page but without the site closed check and redirect
 * as that creates a redirect loop. 
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     admin
 */
// Enable strict type declaration.
declare(strict_types=1);

require_once "../mainfile.php";

// Initialise output buffering with gzip compression.
ob_start("ob_gzhandler");

// Lock charset to UTF-8.
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// HTMLPurifier library is used to validate the teaser and description fields of objects.
// Note that the HTMLPurifier autoloader must be registered AFTER the Tf autoloader.
require_once TFISH_LIBRARIES_PATH . 'htmlpurifier/library/HTMLPurifier.auto.php';

// Set error reporting levels and custom error handler.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Make core language files available.
include TFISH_DEFAULT_LANGUAGE;
/**
 * Initialise essential resources. Note that the order is important, as some are dependencies for
 * those that follow.
 */
// Data validator.
$tf_validator_factory = new TfValidatorFactory();
$tf_validator = $tf_validator_factory->getValidator();

// Error logger.
$tf_logger = new TfLogger($tf_validator);
set_error_handler(array($tf_logger, "logError"));

// File handler.
$tf_file_handler = new TfFileHandler($tf_validator);

// Database connection.
$tf_database = new TfDatabase($tf_validator, $tf_logger, $tf_file_handler);
$tf_database->connect();

// Criteria factory. Used to construct TfCriteria for composing database queries.
$tf_criteria_factory = new TfCriteriaFactory($tf_validator);

// Site preferences.
$preference_handler = new TfPreferenceHandler($tf_database);
$tf_preference = new TfPreference($tf_validator, $preference_handler->readPreferencesFromDatabase());

// Begin secure session. Note that cookies are only relevant in the /admin section of the site.
TfSession::start($tf_validator, $tf_database, $tf_preference);

// Site metadata.
$tf_metadata = new TfMetadata($tf_validator, $tf_preference);

// Template renderer.
$tf_template = new TfTemplate($tf_validator);

/**
 * End manual duplication of header.
 */
// Specify theme, otherwise 'default' will be used.
$tf_template->setTheme('signin');

// Page title.
$tf_template->page_title = TFISH_LOGIN;

// Initialise and whitelist allowed parameters
$clean_op = $clean_email = $dirty_password = '';
$allowed_options = array("login", "logout", "");

// Collect and sanitise parameters. Note that password is NEVER sanitised and therefore dangerous.
if (!empty($_POST['op'])) {
    $op = $tf_validator->trimString($_POST['op']);
    $clean_op = $tf_validator->isAlpha($op) ? $op : '';
} elseif (!empty($_GET['op'])) {
    $op = $tf_validator->trimString($_GET['op']);
    $clean_op = $tf_validator->isAlpha($op) ? $op : '';
}

if (isset($_POST['email'])) {
    $email = $tf_validator->trimString($_POST['email']);
    $clean_email = $tf_validator->isEmail($email) ? $email : '';
}

$dirty_password = isset($_POST['password']) ? $_POST['password'] : '';
$clean_token = isset($_POST['token']) ? $tf_validator->trimString($_POST['token']) : '';

if (isset($clean_op) && in_array($clean_op, $allowed_options, true)) {
    switch ($clean_op) {
        case "login":
            TfSession::validateToken($clean_token); // CSRF check.
            TfSession::login($clean_email, $dirty_password);
            break;

        case "logout":
            TfSession::logout(TFISH_ADMIN_URL . 'login.php');
            break;

        // Display the login form or a logout link, depending on whether the user is signed in.
        default:
            $tf_template->tf_main_content = $tf_template->render('login');
            break;
    }
} else {
    // Bad input, do nothing
    exit;
}

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
$tf_metadata->setTitle(TFISH_LOGIN);
$tf_metadata->setDescription(TFISH_LOGIN_DESCRIPTION);
// $tf_metadata->setAuthor('');
// $tf_metadata->setCopyright('');
// $tf_metadata->setGenerator('');
// $tf_metadata->setSeo('');
$tf_metadata->setRobots('noindex,nofollow');

// Include page template and flush buffer
require_once TFISH_PATH . "tf_footer.php";
