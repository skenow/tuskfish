<?php

/**
 * Login controller script.
 * 
 * Handles password-based login to site. For two-factor authentication with Yubikey hardware tokens
 * see trust_path/extras/login_two_factor.php.
 * 
 * tfish_header is manually duplicated on this page but without the site closed check and redirect
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
// Note that the HTMLPurifier autoloader must be registered AFTER the Tfish autoloader.
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
$tfish_validator_factory = new TfishValidatorFactory();
$tfish_validator = $tfish_validator_factory->getValidator();

// Error logger.
$tfish_logger = new TfishLogger($tfish_validator);
set_error_handler(array($tfish_logger, "logError"));

// File handler.
$tfish_file_handler = new TfishFileHandler($tfish_validator);

// Database connection.
$tfish_database = new TfishDatabase($tfish_validator, $tfish_logger, $tfish_file_handler);
$tfish_database->connect();

// Criteria factory. Used to construct TfishCriteria for composing database queries.
$tfish_criteria_factory = new TfishCriteriaFactory($tfish_validator);

// Site preferences.
$preference_handler = new TfishPreferenceHandler($tfish_database);
$tfish_preference = new TfishPreference($tfish_validator, $preference_handler->readPreferencesFromDatabase());

// Begin secure session. Note that cookies are only relevant in the /admin section of the site.
TfishSession::start($tfish_validator, $tfish_database, $tfish_preference);

// Site metadata.
$tfish_metadata = new TfishMetadata($tfish_validator, $tfish_preference);

// Template renderer.
$tfish_template = new TfishTemplate($tfish_validator);

/**
 * End manual duplication of header.
 */
// Specify theme, otherwise 'default' will be used.
$tfish_template->setTheme('signin');

// Page title.
$tfish_template->page_title = TFISH_LOGIN;

// Initialise and whitelist allowed parameters
$clean_op = $clean_email = $dirty_password = '';
$allowed_options = array("login", "logout", "");

// Collect and sanitise parameters. Note that password is NEVER sanitised and therefore dangerous.
if (!empty($_POST['op'])) {
    $op = $tfish_validator->trimString($_POST['op']);
    $clean_op = $tfish_validator->isAlpha($op) ? $op : '';
} elseif (!empty($_GET['op'])) {
    $op = $tfish_validator->trimString($_GET['op']);
    $clean_op = $tfish_validator->isAlpha($op) ? $op : '';
}

if (isset($_POST['email'])) {
    $email = $tfish_validator->trimString($_POST['email']);
    $clean_email = $tfish_validator->isEmail($email) ? $email : '';
}

$dirty_password = isset($_POST['password']) ? $_POST['password'] : '';
$clean_token = isset($_POST['token']) ? $tfish_validator->trimString($_POST['token']) : '';

if (isset($clean_op) && in_array($clean_op, $allowed_options, true)) {
    switch ($clean_op) {
        case "login":
            TfishSession::validateToken($clean_token); // CSRF check.
            TfishSession::login($clean_email, $dirty_password);
            break;

        case "logout":
            TfishSession::logout(TFISH_ADMIN_URL . 'login.php');
            break;

        // Display the login form or a logout link, depending on whether the user is signed in.
        default:
            $tfish_template->tfish_main_content = $tfish_template->render('login');
            break;
    }
} else {
    // Bad input, do nothing
    exit;
}

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
$tfish_metadata->setTitle(TFISH_LOGIN);
$tfish_metadata->setDescription(TFISH_LOGIN_DESCRIPTION);
// $tfish_metadata->setAuthor('');
// $tfish_metadata->setCopyright('');
// $tfish_metadata->setGenerator('');
// $tfish_metadata->setSeo('');
$tfish_metadata->setRobots('noindex,nofollow');

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";
