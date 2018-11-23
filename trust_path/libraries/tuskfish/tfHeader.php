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
// Note that the HTMLPurifier autoloader must be registered AFTER the Tf autoloader.
require_once TFISH_LIBRARIES_PATH . 'htmlpurifier/library/HTMLPurifier.auto.php';

// Set error reporting levels and custom error handler.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Set cache limiter to avoid 'Document expired' errors on forms when clicking the back button in a browser.
//session_cache_limiter('private_no_expire');

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

// CriteriaItem and Criteria factories. Used to compose database queries.
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

// Site cache.
$tfCache = new TfCache($tfValidator, $tfPreference);

// Check if site is closed, if so redirect to the login page and exit.
if ($tfPreference->closeSite && !TfSession::isAdmin()) {
    header('Location: ' . TFISH_ADMIN_URL . "login.php");
    exit;
}
