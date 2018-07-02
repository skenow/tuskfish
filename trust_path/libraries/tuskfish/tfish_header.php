<?php

/**
 * Tuskfish header script, must be included on every page.
 * 
 * Establishes connection with database, sets up preference and template objects, error logging,
 * class autoloading, includes language constants, HTMLPufifier and starts the session and compressed
 * output buffer.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */
// Enable strict type declaration.
declare(strict_types=1);

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
set_error_handler("TfishLogger::logErrors");

// Ensure that a database connection is available.
$tfish_database = new TfishDatabase();
$tfish_database->connect();

// Make core language files available.
include TFISH_DEFAULT_LANGUAGE;

// Ensure that global site preferences are available via $tfish_preference.
$tfish_preference = new TfishPreference($tfish_database);

// Begin secure session. Note that cookies are only relevant in the /admin section of the site.
TfishSession::start($tfish_preference);

// Set default page-level metadata values for essential template variables (overwrite as required).
$tfish_metadata = new TfishMetadata($tfish_preference);

// Instantiate the template object so that it will be available globally.
$tfish_template = new TfishTemplate();

// Instantiate the cache.
$tfish_cache = new TfishCache();

// Check if site is closed, if so redirect to the login page and exit.
if ($tfish_preference->close_site && !TfishSession::isAdmin()) {
    header('Location: ' . TFISH_ADMIN_URL . "login.php");
    exit;
}
