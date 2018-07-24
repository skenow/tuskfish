<?php

/**
 * Yubikey 2-factor authentication script.
 *
 * Replace /admin/login.php with this script to enable 2-factor authentication. You *must* own a
 * Yubikey hardware authentication token to use it though; order them from www.yubico.com. Please
 * see the manual for setup instructions.
 * 
 * Do not attempt to use this script without reading the manual.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */
// Enable strict type declaration.
declare(strict_types=1);

require_once "../mainfile.php";

// tfHeader is manually duplicated on this page but without the site closed check and redirect
// as that creates a redirect loop.

// Initialise output buffering with gzip compression.
ob_start("ob_gzhandler");

// HTMLPurifier library is used to validate the teaser and description fields of objects.
require_once TFISH_LIBRARIES_PATH . 'htmlpurifier/library/HTMLPurifier.auto.php';

// Initialise data validator.
$tfValidator = new TfValidator();

// Set error reporting levels and custom error handler.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_NOTICE);
$tfLogger = new TfLogger($tfValidator);
set_error_handler(array($tfLogger, "logError"));

// Ensure that a database connection is available
$tfDatabase = new TfDatabase($tfValidator, $tfLogger, $tfFileHandler);
$tfDatabase->connect();

// Make core language files available.
include TFISH_DEFAULT_LANGUAGE;

// Ensure that global site preferences are available via $tfPreference
$preferenceHandler = new TfPreferenceHandler($tfDatabase);
$tfPreference = new TfPreference($tfValidator, $preferenceHandler->readPreferencesFromDatabase());

// Begin secure session. Note that cookies are only relevant in the /admin section of the site
TfSession::start($tfValidator, $tfDatabase, $tfPreference);

// Set default page-level metadata values for essential template variables (overwrite as required).
$tfMetadata = new TfMetadata($tfValidator, $tfPreference);

// Instantiate the template object so that it will be available globally.
$tfTemplate = new TfTemplate($tfValidator);

// End manual duplication of header.

// Specify theme, otherwise 'default' will be used.
$tfTemplate->setTheme('signin');

// Page title.
$tfTemplate->pageTitle = TFISH_LOGIN;

// Initialise and whitelist allowed parameters
$cleanOp = false;
$dirtyPassword = false;
$dirty_otp = false;
$allowedOptions = array("login", "logout", "");

// Collect and sanitise parameters. Note that password is NOT sanitised and therefore it is dangerous.
if (!empty($_POST['op'])) {
    $op = $tfValidator->trimString($_POST['op']);
    $cleanOp = $tfValidator->isAlpha($op) ? $op : false;
} elseif (!empty($_GET['op'])) {
    $op = $tfValidator->trimString($_GET['op']);
    $cleanOp = $tfValidator->isAlpha($op) ? $op : false;
}

$dirtyPassword = isset($_POST['password']) ? $_POST['password'] : false;
$dirty_otp = isset($_POST['yubikey_otp']) ? $_POST['yubikey_otp'] : false;

if (isset($cleanOp) && in_array($cleanOp, $allowedOptions, true)) {
    switch ($cleanOp) {
        case "login":
            $yubikey = new TfYubikeyAuthenticator($tfValidator);
            TfSession::twoFactorLogin($dirtyPassword, $dirty_otp, $yubikey);
            break;

        case "logout":
            TfSession::logout(TFISH_ADMIN_URL . 'login.php');
            break;

        // Display the login form or a logout link, depending on whether the user is signed in or not
        default:
            $tfTemplate->tfMainContent = $tfTemplate->render('yubikey');
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