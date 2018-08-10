<?php

/**
 * Login controller script.
 * 
 * Handles password-based login to site. For two-factor authentication with Yubikey hardware tokens
 * see trust_path/extras/login_two_factor.php.
 * 
 * tfHeader is manually duplicated on this page but without the site closed check and redirect
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
$tfValidatorFactory = new TfValidatorFactory();
$tfValidator = $tfValidatorFactory->getValidator();

// Error logger.
$tfLogger = new TfLogger($tfValidator);
set_error_handler(array($tfLogger, "logError"));

// File handler.
$tfFileHandler = new TfFileHandler($tfValidator);

// Database connection.
$tfDatabase = new TfDatabase($tfValidator, $tfLogger, $tfFileHandler);
$tfDatabase->connect();

// Criteria factory. Used to construct TfCriteria for composing database queries.
$tfCriteriaFactory = new TfCriteriaFactory($tfValidator);

// Site preferences.
$preferenceHandler = new TfPreferenceHandler($tfDatabase);
$tfPreference = new TfPreference($tfValidator, $preferenceHandler->readPreferencesFromDatabase());

// Begin secure session. Note that cookies are only relevant in the /admin section of the site.
TfSession::start($tfValidator, $tfDatabase, $tfPreference);

// Site metadata.
$tfMetadata = new TfMetadata($tfValidator, $tfPreference);

// Template renderer.
$tfTemplate = new TfTemplate($tfValidator);

/**
 * End manual duplication of header.
 */
// Specify theme, otherwise 'default' will be used.
$tfTemplate->setTheme('signin');

// Page title.
$tfTemplate->pageTitle = TFISH_LOGIN;

// Initialise and whitelist allowed parameters
$cleanOp = $cleanEmail = $dirtyPassword = '';
$allowedOptions = array("login", "logout", "");

// Collect and sanitise parameters. Note that password is NEVER sanitised and therefore dangerous.
if (!empty($_POST['op'])) {
    $op = $tfValidator->trimString($_POST['op']);
    $cleanOp = $tfValidator->isAlpha($op) ? $op : '';
} elseif (!empty($_GET['op'])) {
    $op = $tfValidator->trimString($_GET['op']);
    $cleanOp = $tfValidator->isAlpha($op) ? $op : '';
}

if (isset($_POST['email'])) {
    $email = $tfValidator->trimString($_POST['email']);
    $cleanEmail = $tfValidator->isEmail($email) ? $email : '';
}

$dirtyPassword = $_POST['password'] ?? '';
$cleanToken = isset($_POST['token']) ? $tfValidator->trimString($_POST['token']) : '';

if (isset($cleanOp) && in_array($cleanOp, $allowedOptions, true)) {
    switch ($cleanOp) {
        case "login":
            TfSession::validateToken($cleanToken); // CSRF check.
            TfSession::login($cleanEmail, $dirtyPassword);
            break;

        case "logout":
            TfSession::logout(TFISH_ADMIN_URL . 'login.php');
            break;

        // Display the login form or a logout link, depending on whether the user is signed in.
        default:
            $tfTemplate->tfMainContent = $tfTemplate->render('login');
            break;
    }
} else {
    // Bad input, do nothing
    exit;
}

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
$tfMetadata->setTitle(TFISH_LOGIN);
$tfMetadata->setDescription(TFISH_LOGIN_DESCRIPTION);
// $tfMetadata->setAuthor('');
// $tfMetadata->setCopyright('');
// $tfMetadata->setGenerator('');
// $tfMetadata->setSeo('');
$tfMetadata->setRobots('noindex,nofollow');

// Include page template and flush buffer
require_once TFISH_PATH . "tfFooter.php";
