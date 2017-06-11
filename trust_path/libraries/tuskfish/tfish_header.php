<?php

/**
 * Tuskfish header script, must be included on every page.
 * 
 * Establishes connection with database, sets up preference and template objects, error logging,
 * class autoloading, includes language constants, HTMLPufifier and starts the session and compressed
 * output buffer.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since		1.0
 * @package		core
 */
// Initialise output buffering with gzip compression.
ob_start("ob_gzhandler");

/**
 * Autoload core Tuskfish classes. spl_autoload_register() avoids namespace clashes.
 * @param string $classname Name of class to autoload. 
 */
function tfish_autoload($classname) {
    include TFISH_CLASS_PATH . $classname . '.php';
}

spl_autoload_register('tfish_autoload');

// HTMLPurifier library is used to validate the teaser and description fields of objects.
// Note that the HTMLPurifier autoloader must be registered AFTER the Tfish autoloader.
// Ideally, it would be best if this library was only included on the admin side. However, the
// description and teaser fields currently force filtering every time they are set, and so the
// library is also required on the user side. There isn't really a need to filter these fields
// when they are being set based on input from the database (which was filtered on the way in).
require_once TFISH_LIBRARIES_PATH . 'htmlpurifier/library/HTMLPurifier.auto.php';

// Set error reporting levels and custom error handler.
error_reporting(E_ALL & ~E_NOTICE);
set_error_handler("TfishLogger::logErrors");

// Ensure that a database connection is available
TfishDatabase::connect();

// Make core language files available.
include TFISH_DEFAULT_LANGUAGE;

// Ensure that global site preferences are available via $tfish_preference
$tfish_preference = new TfishPreference();

// Begin secure session. Note that cookies are only relevant in the /admin section of the site
TfishSession::start();

// Set default page-level metadata values for essential template variables (overwrite as required).
$tfish_metadata = new TfishMetadata($tfish_preference);

// Instantiate the template object so that it will be available globally.
$tfish_template = new TfishTemplate();

// Check if site is closed, if so redirect to the login page and exit.
if ($tfish_preference->close_site == true && !TfishSession::isAdmin()) {
    header('Location: ' . TFISH_ADMIN_URL . "login.php");
    exit;
}