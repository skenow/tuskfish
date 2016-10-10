<?php

/**
* Installation script for Tuskfish CMS.
* 
* Directory should auto-delete after use. If not, you MUST delete it manually to prevent people
* from re-installing Tuskfish and thereby taking over management of your site (which is not fatal,
* as your own database will not be affected, but it could be embarassing).
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) Version 3 or higher
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

// Initialise output buffering with gzip compression.
ob_start("ob_gzhandler");

// Boot!
require_once "../mainfile.php";

// Autoload core Tuskfish classes, spl_autoload_register() avoids namespace clashes.
function tfish_autoload($classname) {
	include TFISH_CLASS_PATH . $classname . '.php';
}
spl_autoload_register('tfish_autoload');

// Set error reporting levels and custom error handler.
error_reporting(E_ALL & ~E_NOTICE);
set_error_handler("TfishLogger::logErrors");

// Include installation language files
include_once "./english.php";

// Initialise default content variable
$tfish_content = array('output' => '');

// Test and save database credentials
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// Display blank form
	if (empty($_POST['db_name']) || empty($_POST['admin_email']) || empty($_POST['admin_password']) || empty($_POST['hmac_key'])) {
		$tfish_content['output'] .= '<p>' . TFISH_INSTALLATION_COMPLETE_FORM . '</p>';
		$tfish_form = "db_credentials_form.html";
	} else {
		// Filter user input
		$allowed_vars = array(
			'db_name' => 'string',
			'admin_email' => 'string',
			'admin_password' => 'string',
			'hmac_key' => 'string');
		$clean_vars = TfishFilter::filterData($_POST, $allowed_vars);
		
		// Check password length and quality
		$password_quality = TfishSecurityUtility::checkPasswordStrength($clean_vars['admin_password']);
		if ($password_quality['strong'] == true) {
			$tfish_content['output'] .= '<p>' . TFISH_INSTALLATION_STRONG_PASSWORD . '</p>';
			
			// Salt and iteratively hash the password 100,000 times to resist brute force attacks
			$site_salt = TfishSecurityUtility::generateSalt(64);
			$user_salt = TfishSecurityUtility::generateSalt(64);
			$password_hash = TfishSecurityUtility::recursivelyHashPassword($clean_vars['admin_password'], 
					100000, $site_salt, $user_salt);

			// Append site salt to config.php
			$site_salt_constant = 'if (!defined("TFISH_SITE_SALT")) define("TFISH_SITE_SALT", "' . $site_salt . '");';
			$result = TfishFileHandler::appendFile(TFISH_CONFIGURATION_PATH, $site_salt_constant);
			if (!$result) {
				trigger_error(TFISH_ERROR_FAILED_TO_APPEND_FILE, E_USER_ERROR);
				exit;
			}

			// Append HMAC key to config.php
			$hmac_key = 'if (!defined("TFISH_KEY")) define("TFISH_KEY", "' . $clean_vars['hmac_key'] . '");';
			$result = TfishFileHandler::appendFile(TFISH_CONFIGURATION_PATH, $hmac_key);
			if (!$result) {
				trigger_error(TFISH_ERROR_FAILED_TO_APPEND_FILE, E_USER_ERROR);
				exit;
			}
				
			////////////////////////////////////
			// INITIALISE THE SQLITE DATABASE //
			////////////////////////////////////
			try {
				// Create the database
				$db_path = TfishDatabase::create($clean_vars['db_name']);
				if ($db_path) {
					if (!defined("TFISH_DATABASE")) define("TFISH_DATABASE", $db_path);
				}

				// Create user table
				$user_columns = array(
					"id" => "INTEGER",
					"admin_email" => "TEXT",
					"password_hash" => "TEXT",
					"user_salt" => "TEXT",
					"user_group" => "INTEGER"
				);

				TfishDatabase::createTable('user', $user_columns, 'id');
				// Insert admin user's details to database
				$user_data = array('admin_email' => $clean_vars['admin_email'], 'password_hash' => $password_hash, 'user_salt' => $user_salt, 'user_group' => '1');
				$query = TfishDatabase::insert('user', $user_data);

				// Create preference table
				$preference_columns =  array(
					"id" => "INTEGER",
					"title" => "TEXT",
					"value" => "TEXT"
				);
				TfishDatabase::createTable('preference', $preference_columns, 'id');

				// Insert default preferences to database
				$preference_data = array(
					array('title' => 'site_name', 'value' => 'Tuskfish CMS'),
					array('title' => 'site_description', 'value' => 'A cutting edge micro CMS'),
					array('title' => 'site_author', 'value' => ''),
					array('title' => 'site_email', 'value' => $clean_vars['admin_email']),
					array('title' => 'site_copyright', 'value' => 'Copyright all rights reserved'),
					array('title' => 'close_site', 'value' => '0'),
					array('title' => 'server_timezone', 'value' => '0'),
					array('title' => 'site_timezone', 'value' => '0'),
					array('title' => 'min_search_length', 'value' => '3'),
					array('title' => 'search_pagination', 'value' => '20'),
					array('title' => 'user_pagination', 'value' => '10'),
					array('title' => 'admin_pagination', 'value' => '20'),
					array('title' => 'pagination_elements', 'value' => '5'),
					array('title' => 'session_name', 'value' => 'tfish_session'),
					array('title' => 'session_timeout', 'value' => '0'),
					array('title' => 'session_domain', 'value' => '/'),
					array('title' => 'default_language', 'value' => 'en'),
					array('title' => 'date_format', 'value' => 'j F Y'),
				);
				foreach ($preference_data as $preference) {
					TfishDatabase::insert('preference', $preference, 'id');
				}

				// Create session table
				$session_columns = array(
					"id" => "INTEGER",
					"last_active" => "INTEGER",
					"data" => "TEXT"
				);
				TfishDatabase::createTable('session', $session_columns, 'id');	

				// Create content object table. Note that the type must be first column to enable
				// the PDO::FETCH_CLASS|PDO::FETCH_CLASSTYPE functionality, which automatically
				// pulls DB rows into an instance of a class, based on the first column.
				$content_columns = array(
					"type" => "TEXT", // article => , image => , podcast => , etc.
					"id" => "INTEGER", // Auto-increment => , set by database.
					"title" => "TEXT", // The headline or name of this content.
					"teaser" => "TEXT", // A short (one paragraph) summary or abstract for this content.
					"description" => "TEXT", // The full article or description of the content.
					"media" => "TEXT", // URL of an associated audio file.
					"format" => "TEXT", // Mimetype
					"file_size" => "INTEGER", // Specify in bytes.
					"creator" => "TEXT", // Author.
					"image" => "TEXT", // URL of an associated image file => , eg. a screenshot a good way to handle it.
					"caption" => "TEXT", // Caption of the image file.
					"date" => "TEXT", // Date of first publication expressed as a string, hopefully in a standard format to allow time/date conversion.
					"parent" => "INTEGER", // A source work or collection of which this content is part.
					"language" => "TEXT", // English (future proofing).
					"rights" => "INTEGER", // Intellectual property rights scheme or license under which the work is distributed.
					"publisher" => "TEXT", // The entity responsible for distributing this work.
					"online" => "INTEGER", // Toggle object on or offline
					"submission_time" => "INTEGER", // Timestamp representing submission time.
					"counter" => "INTEGER", // Number of times this content was viewed or downloaded.
					"meta_title" => "TEXT", // Set a custom page title for this content.
					"meta_description" => "TEXT", // Set a custom page meta description for this content.
					"seo" => "TEXT"); // SEO-friendly string; it will be appended to the URL for this content.
				TfishDatabase::createTable('content', $content_columns, 'id');

				// Insert a "General" tag content object
				$content_data = array(
					"type" => "TfishTag",
					"title" => "General",
					"teaser" => "Default content tag.",
					"description" => "Default content tag, please edit it to something useful.",
					"language" => "en",
					"online" => "1",
					"submission_time" => time(),
					"counter" => "0",
					"meta_title" => "General",
					"meta_description" => "General information.",
					"seo" => "general");
				$query = TfishDatabase::insert('content', $content_data);

				// Create taglink table
				$taglink_columns = array(
					"id" => "INTEGER",
					"tag_id" => "INTEGER",
					"content_type" => "TEXT",
					"content_id" => "INTEGER");
				TfishDatabase::createTable('taglink', $taglink_columns, 'id');

				// Close database
				TfishDatabase::close();
			} catch (PDOException $e) {
				TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
			}

			// Report on status of database creation
			if ($db_path && $query) {
				$tfish_content['output'] .= '<p>' . TFISH_INSTALLATION_DATABASE_SUCCESS . '</p>'
					. '<p>' . TFISH_INSTALLATION_COMPLETE . '</p>';

				// Delete the installation folder from the server for security reasons
				/*try {
					$tfish_file_handler = new TfishFileHandler();
					$tfish_file_handler->delete_directory(TFISH_ROOT_PATH . 'installation/');
					$tfish_content['output'] .= '<p>' . TFISH_INSTALLATION_DIRECTORY_DELETED . '</p>';
				} catch(Exception $e) {
					$tfish_content['output'] .= '<p>' . TFISH_INSTALLATION_REMOVE_DIRECTORY . '</p>';
				}*/
			} else {
				// If database creation failed, complain and display data entry form again
				$tfish_content['output'] .= '<p>' . TFISH_INSTALLATION_DATABASE_FAILED . '</p>';
				$tfish_form = "db_credentials_form.html";
			}			
		} else {
			$tfish_content['output'] .= '<h1>' . TFISH_INSTALLATION_WARNING . '</h1>';
			$tfish_content['output'] .= '<p>' . TFISH_INSTALLATION_WEAK_PASSWORD . '</p>';
			unset($password_quality['strong']);
			$tfish_content['output'] .= '<ul>';
			foreach ($password_quality as $weakness) {
				$tfish_content['output'] .= '<li>' . $weakness . '</li>';
			}
			$tfish_content['output'] .= '</ul>';
			$tfish_form = "db_credentials_form.html";
		}
	}
} else {
// Display data entry form
	$tfish_form = "db_credentials_form.html";	
}

/**
 * Manually instantiate the metadata object.
 */
$tfish_metadata = new TfishMetadata();
$tfish_metadata->title = TFISH_INSTALLATION_TUSKFISH;
$tfish_metadata->description = TFISH_INSTALLATION_DESCRIPTION;
$tfish_metadata->template = 'admin.html';
require_once TFISH_PATH . "tfish_footer.php";