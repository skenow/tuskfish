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

// CriteriaItem and Criteria factories. Used to compose database queries.
$tf_criteria_factory = new TfCriteriaFactory($tf_validator);
$tf_criteria_item_factory = new TfCriteriaItemFactory($tf_validator);

// Site preferences.
$preference_handler = new TfPreferenceHandler($tf_database);
$tf_preference = new TfPreference($tf_validator, $preference_handler->readPreferencesFromDatabase());

// Begin secure session. Note that cookies are only relevant in the /admin section of the site.
TfSession::start($tf_validator, $tf_database, $tf_preference);

// Site metadata.
$tf_metadata = new TfMetadata($tf_validator, $tf_preference);

// Template renderer.
$tf_template = new TfTemplate($tf_validator);

// Site cache.
$tf_cache = new TfCache($tf_validator, $tf_preference);

// Check if site is closed, if so redirect to the login page and exit.
if ($tf_preference->close_site && !TfSession::isAdmin()) {
    header('Location: ' . TFISH_ADMIN_URL . "login.php");
    exit;
}
