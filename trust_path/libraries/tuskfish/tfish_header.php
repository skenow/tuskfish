<?php

/**
* Tuskfish header script, must be included on every page
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

// Initialise output buffering with gzip compression.
ob_start("ob_gzhandler");
// Enabling gzip compression breaks file downloads via headers. Don't know why - clash in php.ini setting?
//ob_start();

// Autoload core Tuskfish classes, spl_autoload_register() avoids namespace clashes.
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
TfishSession::sessionStart();

// Set default page-level metadata values for essential template variables (overwrite as required).
$tfish_metadata = new TfishMetadata($tfish_preference);

// Set an array to hold page-genenerated content and provide a consistent mechanism for accessing it. 
$tfish_content = array('output' => '');

// echo '<p>Header included.</p>';