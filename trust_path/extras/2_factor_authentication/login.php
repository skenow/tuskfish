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

// tfish_header is manually duplicated on this page but without the site closed check and redirect
// as that creates a redirect loop.

// Initialise output buffering with gzip compression.
ob_start("ob_gzhandler");

// HTMLPurifier library is used to validate the teaser and description fields of objects.
require_once TFISH_LIBRARIES_PATH . 'htmlpurifier/library/HTMLPurifier.auto.php';

// Set error reporting levels and custom error handler.
error_reporting(E_ALL & ~E_NOTICE);
set_error_handler("TfishLogger::logError");

// Ensure that a database connection is available
TfishDatabase::connect();

// Make core language files available.
include TFISH_DEFAULT_LANGUAGE;

// Ensure that global site preferences are available via $tfish_preference
$tfish_preference = new TfishPreference();

// Begin secure session. Note that cookies are only relevant in the /admin section of the site
TfishSession::start($tfish_preference);

// Set default page-level metadata values for essential template variables (overwrite as required).
$tfish_metadata = new TfishMetadata($tfish_preference);

// Instantiate the template object so that it will be available globally.
$tfish_template = new TfishTemplate();

// End manual duplication of header.

// Specify theme, otherwise 'default' will be used.
$tfish_template->setTheme('signin');

// Page title.
$tfish_template->page_title = TFISH_LOGIN;

// Initialise and whitelist allowed parameters
$clean_op = false;
$dirty_password = false;
$dirty_otp = false;
$allowed_options = array("login", "logout", "");

// Collect and sanitise parameters. Note that password is NOT sanitised and therefore it is dangerous.
if (!empty($_POST['op'])) {
    $op = TfishDataValidator::trimString($_POST['op']);
    $clean_op = TfishDataValidator::isAlpha($op) ? $op : false;
} elseif (!empty($_GET['op'])) {
    $op = TfishDataValidator::trimString($_GET['op']);
    $clean_op = TfishDataValidator::isAlpha($op) ? $op : false;
}

$dirty_password = isset($_POST['password']) ? $_POST['password'] : false;
$dirty_otp = isset($_POST['yubikey_otp']) ? $_POST['yubikey_otp'] : false;

if (isset($clean_op) && in_array($clean_op, $allowed_options)) {
    switch ($clean_op) {
        case "login":
            $yubikey = new TfishYubikeyAuthenticator();
            TfishSession::twoFactorLogin($dirty_password, $dirty_otp, $yubikey);
            break;

        case "logout":
            TfishSession::logout(TFISH_ADMIN_URL . 'login.php');
            break;

        // Display the login form or a logout link, depending on whether the user is signed in or not
        default:
            $tfish_template->tfish_main_content = $tfish_template->render('yubikey');
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