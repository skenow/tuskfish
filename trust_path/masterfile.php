<?php

/**
* Tuskfish masterfile script
*
* Sets useful path constants, includes the database credentials and reads site preferences 
* 
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

//echo '<p>Masterfile included.</p>';
if (!defined("TFISH_MASTERFILE_INCLUDED")) {
	define("TFISH_MASTERFILE_INCLUDED", 1); 

	// Constants that make use of the physical path.
	define("TFISH_ADMIN_PATH", TFISH_ROOT_PATH . "admin/");
	define("TFISH_CACHE_PATH", TFISH_ROOT_PATH . "cache/");
	define("TFISH_TEMPLATES_PATH", TFISH_ROOT_PATH . "templates/");
	define("TFISH_TEMPLATES_BLOCK_PATH", TFISH_ROOT_PATH . "templates/blocks/");
	define("TFISH_TEMPLATES_OBJECT_PATH", TFISH_ROOT_PATH . "templates/objects/");
	
	// Constants that make use of the trust path (which is a derivative of the physical path).
	define("TFISH_PATH", TFISH_TRUST_PATH . "libraries/tuskfish/");
	define("TFISH_CLASS_PATH", TFISH_PATH . "class/");
	define("TFISH_CONFIGURATION_PATH", TFISH_PATH . "configuration/config.php");
	define("TFISH_DATABASE_PATH", TFISH_TRUST_PATH . "database/");
	define("TFISH_ERROR_LOG_PATH", TFISH_TRUST_PATH . "log/tuskfish_log.txt");
	define("TFISH_FORM_PATH", TFISH_PATH . "form/");
	define("TFISH_LIBRARIES_PATH", TFISH_TRUST_PATH . "libraries/");
	define("TFISH_MEDIA_PATH", TFISH_TRUST_PATH . "media/");
	
	// Constants that make use of the virtual (URL) path, these refer to assets accessed by URL
	define("TFISH_ADMIN_URL", TFISH_URL . "admin/");
	define("TFISH_ASSETS_URL", TFISH_URL . "assets/");
	define("TFISH_TEMPLATES_URL", TFISH_URL . "templates/");
	
	// Include DB credentials and salt
	include TFISH_CONFIGURATION_PATH;
	
	/*
	 * Preferences
	 */
	// Language: Specify the file name of the default language file
	define("TFISH_LANGUAGE_PATH", TFISH_PATH . "language/");
	define("TFISH_DEFAULT_LANGUAGE", TFISH_LANGUAGE_PATH . "english.php");
}