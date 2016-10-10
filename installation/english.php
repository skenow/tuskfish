<?php

/**
* Language file for Tuskfish installer script.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

define("TFISH_INSTALLATION_TUSKFISH", "Installation");
define("TFISH_INSTALLATION_DESCRIPTION", "Script to install the Tuskfish CMS. Delete from server after use.");
define("TFISH_INSTALLATION_ENTER_DB_NAME", "Please enter a name for your database and the administrator's email/password below.");
define("TFISH_INSTALLATION_DB_NAME", "Database name");
define("TFISH_INSTALLATION_COMPLETE_FORM", "Please enter a database name and resubmit the form.");
define("TFISH_INSTALLATION_DATABASE_SUCCESS", "Database successfully created.");
define("TFISH_INSTALLATION_DATABASE_FAILED", "Failed to create database. Please check the script has write permission to /your_trust_path/database");
define("TFISH_INSTALLATION_HOME_PAGE", "home page");
define("TFISH_INSTALLATION_COMPLETE", "Installation complete! Proceed to <a href=\"" .  TFISH_URL . "\">" . TFISH_INSTALLATION_HOME_PAGE . "</a>.");
define("TFISH_INSTALLATION_DIRECTORY_DELETED", "Successfully removed the installation directory.");
define("TFISH_INSTALLATION_REMOVE_DIRECTORY", "Removal of the installation directory failed. Please delete it manually as it can be used to overwrite your site.");
define("TFISH_INSTALLATION_ADMIN_EMAIL", "Admin email address");
define("TFISH_INSTALLATION_ADMIN_PASSWORD", "Admin password");
define("TFISH_INSTALLATION_USER_SALT", "User password salt");
define("TFISH_INSTALLATION_SITE_SALT", "Site password salt");
define("TFISH_INSTALLATION_KEY", "HMAC key (63 random characters)");
define("TFISH_INSTALLATION_STRONG_PASSWORD", "Password is strong");
define("TFISH_INSTALLATION_WARNING", "Warning");
define("TFISH_INSTALLATION_WEAK_PASSWORD", "This password is WEAK, please try again. For maximum strength your password needs to incorporate the following attributes:");
define("TFISH_PASSWORD_MINIMUM_LENGTH_WEAKNESS", "At least 13 characters long to resist exhaustive searches of the keyspace.");
define("TFISH_PASSWORD_LOWER_CASE_WEAKNESS", "At least one lower case letter.");
define("TFISH_PASSWORD_UPPER_CASE_WEAKNESS", "At least one upper case letter.");
define("TFISH_PASSWORD_NUMBERIC_WEAKNESS", "At least one number.");
define("TFISH_PASSWORD_SYMBOLIC_WEAKNESS", "At least one non-alphanumeric character.");
define("TFISH_WELCOME", "Welcome to Tuskfish CMS");
define("TFISH_LOGIN", "Login");
define("TFISH_SUBMIT", "Submit");