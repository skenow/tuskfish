<?php

/**
 * English language file for Tuskfish installer script.
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     installation
 */
define("TFISH_CMS", "Tuskfish CMS");
define("TFISH_INSTALLATION_TUSKFISH", "Installation");
define("TFISH_INSTALLATION_PLEASE_NOTE", "Please note");
define("TFISH_INSTALLATION_SECURITY", "<p>The security of your website hinges on the following 
	things:</p>
	<ol>
	<li>Using a <b>strong</b> password (> 14 characters, at least one upper and lower case letter,
	number and symbol, no names or words that can be found in <em>any</em> dictionary).</li>
	<li>Putting your trust_path folder <b>outside the web root</b> (eg. outside of public_html), so
	that it is not accessible via browser. You can rename it if you want.</li>
	</ol>
	<p>Further instructions on securing your site will be displayed when installation is complete.</p>");

define("TFISH_INSTALLATION_DESCRIPTION", "Script to install the Tuskfish CMS. Delete from server "
        . "after use.");
define("TFISH_INSTALLATION_ENTER_DB_NAME", "Please enter a name for your database and the "
        . "administrator's email/password below.");
define("TFISH_INSTALLATION_DB_NAME", "Database name");
define("TFISH_INSTALLATION_ALNUMUNDER", "Alphanumeric and underscore characters only");
define("TFISH_INSTALLATION_COMPLETE_FORM", "Please enter a database name and resubmit the form.");
define("TFISH_INSTALLATION_DATABASE_SUCCESS", "Database successfully created.");
define("TFISH_INSTALLATION_DATABASE_FAILED", "Failed to create database. Please check the script "
        . "has write permission to /your_trust_path/database");
define("TFISH_INSTALLATION_HOME_PAGE", "home page");
define("TFISH_INSTALLATION_COMPLETE", "Installation complete!");
define("TFISH_INSTALLATION_SECURE_YOUR_SITE", "Secure your site");
define("TFISH_INSTALLATION_SECURITY_INSTRUCTIONS", "<ul>"
        . "<li><strong>Delete</strong> the <strong>/installation</strong> directory.</li>"
        . "<li>Set the file permissions for <strong>/mainfile.php</strong> to <strong>0400</strong>.</li>"
        . "<li>Set the file permissions for trust_path/database/<strong>yourdatabase.db</strong> to "
        . "<strong>0600</strong>.</li>"
        . "<li>Set the file permissions for trust_path/configuration/<strong>config.php</strong> to "
        . "<strong>0400</strong>.</li>"
        . "<li>Please note that you may need to use the cPanel File Manager or shell access to set "
        . "file permissions to these levels.</li>"
        . "</ul>"
        . "<p>Please <strong><a href='../admin/login.php'>login</a></strong> and configure your "
        . "site preferences. The <strong><a href='https://tuskfish.biz/?id=15' "
        . "target='_blank'>Tuskfish User Manual</a></strong> is available to help you. </p>");
define("TFISH_INSTALLATION_DIRECTORY_DELETED", "Successfully removed the installation directory.");
define("TFISH_INSTALLATION_REMOVE_DIRECTORY", "Removal of the installation directory failed. Please "
        . "delete it manually as it can be used to overwrite your site.");
define("TFISH_INSTALLATION_ADMIN_EMAIL", "Admin email address");
define("TFISH_INSTALLATION_SOME_EMAIL", "youremail@somedomain.com");
define("TFISH_INSTALLATION_ADMIN_PASSWORD", "Admin password");
define("TFISH_INSTALLATION_USER_SALT", "User password salt");
define("TFISH_INSTALLATION_SITE_SALT", "Site password salt");
define("TFISH_INSTALLATION_GRC", "Get one from: https://grc.com/passwords/");
define("TFISH_INSTALLATION_STRONG_PASSWORD", "Password is strong.");
define("TFISH_INSTALLATION_PASSWORD_REQUIREMENTS", "> 14 characters: Letters, numbers and symbols");
define("TFISH_INSTALLATION_URL", "Domain with trailing slash");
define("TFISH_INSTALLATION_ROOT_PATH", "File path to web root");
define("TFISH_INSTALLATION_TRUST_PATH", "File path to trust_path");
define("TFISH_INSTALLATION_TRAILING_SLASH", "With trailing slash /");
define("TFISH_INSTALLATION_WARNING", "Error(s)");
define("TFISH_INSTALLATION_WEAK_PASSWORD", "This password is <strong>WEAK</strong>, please try "
        . "again. For maximum strength your password needs to incorporate the following attributes:");
define("TFISH_PASSWORD_MINIMUM_LENGTH_WEAKNESS", "At least 15 characters long to resist exhaustive "
        . "searches of the keyspace.");
define("TFISH_PASSWORD_LOWER_CASE_WEAKNESS", "At least one lower case letter.");
define("TFISH_PASSWORD_UPPER_CASE_WEAKNESS", "At least one upper case letter.");
define("TFISH_PASSWORD_NUMBERIC_WEAKNESS", "At least one number.");
define("TFISH_PASSWORD_SYMBOLIC_WEAKNESS", "At least one non-alphanumeric character.");
define("TFISH_WELCOME", "Welcome to Tuskfish CMS");
define("TFISH_LOGIN", "Login");
define("TFISH_SUBMIT", "Submit");

// Errors
define("TFISH_ERROR_BAD_PATH", "Bad file path.");
define("TFISH_ERROR_FAILED_TO_DELETE_DIRECTORY.", "Failed to delete directory");
define("TFISH_INSTALLATION_DB_ALNUMUNDERSCORE", "Database name is restricted to <strong>alphanumeric and underscore</strong> characters.");
define("TFISH_INSTALLATION_BAD_EMAIL", "Bad email address.");

// Constants used in theme, to prevent errors.
define("TFISH_SEARCH", "Search");
define("TFISH_RSS", "RSS");
