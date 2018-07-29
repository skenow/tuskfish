<?php

/**
 * Installation script for Tuskfish CMS.
 * 
 * The installation directory should be deleted after use, otherwise someone may decide to reinstall
 * Tuskfish and take over management of your site.
 *
 * @copyright	Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since		1.0
 * @package		installation
 */

// Enable strict type declaration.
declare(strict_types=1);

// Initialise output buffering with gzip compression.
ob_start("ob_gzhandler");

// Include installation language files
include_once "./english.php";

// Check PHP version 7.2+
if (PHP_VERSION_ID < 70200) {
    echo TFISH_PHP_VERSION_TOO_LOW;
    exit;
}

// Check path to mainfile.
if (is_readable("../mainfile.php")) {
    require_once "../mainfile.php";
} else {
    echo TFISH_PATH_TO_MAINFILE_INVALID;
    exit;
}

// HTMLPurifier is a dependency of TfValidator.
require_once TFISH_LIBRARIES_PATH . 'htmlpurifier/library/HTMLPurifier.auto.php';

// Initialise data validator.
$tfValidatorFactory = new TfValidatorFactory();
$tfValidator = $tfValidatorFactory->getValidator();

// Initialise preference.
$preferenceConfig = array(
    'siteName' => 'Tuskfish CMS',
    'siteDescription' => 'A cutting edge micro-CMS',
    'siteAuthor' => '',
    'siteCopyright' => '',
    'seo' => '',
    'paginationElements' => '5',
    'enableCache' => 0
);
$tfPreference = new TfPreference($tfValidator, $preferenceConfig);

// Initialise default content variable.
$tfContent = array('output' => '');

// Set error reporting levels and custom error handler.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_NOTICE);
$tfLogger = new TfLogger($tfValidator);
set_error_handler(array($tfLogger, "logError"));

// Set theme.
$tfTemplate = new TfTemplate($tfValidator);
$tfTemplate->setTheme('signin');

$tfTemplate->tfUrl = getUrl();

/**
 * Helper function to grab the site URL and protocol during installation.
 * 
 * @return string Site URL.
 */
function getUrl() {
    $url = @(!isset($_server['HTTPS']) || $_SERVER["HTTPS"] != 'on') ? 'http://'
            . $_SERVER["SERVER_NAME"] : 'https://' . $_SERVER["SERVER_NAME"];
    $url .= ($_SERVER["SERVER_PORT"] != 80 && $_SERVER["SERVER_PORT"] != 443) ? ":"
            . $_SERVER["SERVER_PORT"] : "";
    $url .= '/';
    return $url;
}

// Test and save database credentials.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    ////////////////////////////////////
    ////////// VALIDATE INPUT //////////
    ////////////////////////////////////
    
    // Check that form was completed.
    if (empty($_POST['dbName']) || empty($_POST['adminEmail']) || empty($_POST['adminPassword'])) {
        $tfContent['output'] .= '<p>' . TFISH_INSTALLATION_COMPLETE_FORM . '</p>';
    }

    // Database name is restricted to alphanumeric and underscore characters only.
    $dbName = $tfValidator->trimString($_POST['dbName']);
    if (!$tfValidator->isAlnumUnderscore($dbName)) {
        $tfContent['output'] .= '<p>' . TFISH_INSTALLATION_DB_ALNUMUNDERSCORE . '</p>';
    }

    // Admin email must conform to email specification.
    $adminEmail = $tfValidator->trimString($_POST['adminEmail']);
    if (!$tfValidator->isEmail($adminEmail)) {
        $tfContent['output'] .= '<p>' . TFISH_INSTALLATION_BAD_EMAIL . '</p>';
    }

    // There are no restrictions on what characters you use for a password. Only only on what you
    // don't use!
    $adminPassword = $tfValidator->trimString($_POST['adminPassword']);

    // Check password length and quality.
    $securityUtility = new TfSecurityUtility();
    $passwordQuality = $securityUtility->checkPasswordStrength($adminPassword);

    if ($passwordQuality['strong'] === false) {
        $tfContent['output'] .= '<p>' . TFISH_INSTALLATION_WEAK_PASSWORD . '</p>';
        unset($passwordQuality['strong']);
        $tfContent['output'] .= '<ul>';

        foreach ($passwordQuality as $weakness) {
            $tfContent['output'] .= '<li>' . $weakness . '</li>';
        }
        
        $tfContent['output'] .= '</ul>';
    }
    
    // Report errors.
    if (!empty($tfContent['output'])) {
        $tfContent['output'] = '<h1 class="text-center">' . TFISH_INSTALLATION_WARNING . '</h1>'
                . $tfContent['output'];
        $tfTemplate->output = $tfContent['output'];
        $tfTemplate->form = "dbCredentialsForm.html";
        $tfTemplate->tfMainContent = $tfTemplate->render('form');
        
    // All input validated, proceed to process and set up database.    
    } else {
        // Salt and iteratively hash the password 100,000 times to resist brute force attacks.
        $securityUtility = new TfSecurityUtility();
        $siteSalt = $securityUtility->generateSalt(64);
        $userSalt = $securityUtility->generateSalt(64);
        $passwordHash = TfSession::recursivelyHashPassword($adminPassword, 100000,
                $siteSalt, $userSalt);

        // Append site salt to config.php.
        $siteSaltConstant = 'if (!defined("TFISH_SITE_SALT")) define("TFISH_SITE_SALT", "'
                . $siteSalt . '");';
        $tfFileHandler = new TfFileHandler($tfValidator);
        $result = $tfFileHandler->appendToFile(TFISH_CONFIGURATION_PATH, $siteSaltConstant);

        if (!$result) {
            trigger_error(TFISH_ERROR_FAILED_TO_APPEND_FILE, E_USER_ERROR);
            exit;
        }

        ////////////////////////////////////
        // INITIALISE THE SQLITE DATABASE //
        ////////////////////////////////////
        
        // Create the database
        $tfDatabase = new TfDatabase($tfValidator, $tfLogger, $tfFileHandler);
        $dbPath = $tfDatabase->create($dbName);

        if ($dbPath) {
            if (!defined("TFISH_DATABASE"))
                define("TFISH_DATABASE", $dbPath);
        }

        // Create user table.
        $userColumns = array(
            "id" => "INTEGER",
            "adminEmail" => "TEXT",
            "passwordHash" => "TEXT",
            "userSalt" => "TEXT",
            "userGroup" => "INTEGER",
            "yubikeyId" => "TEXT",
            "yubikeyId2" => "TEXT",
            "loginErrors" => "INTEGER"
        );

        $tfDatabase->createTable('user', $userColumns, 'id');
        // Insert admin user's details to database.
        $userData = array(
            'adminEmail' => $adminEmail,
            'passwordHash' => $passwordHash,
            'userSalt' => $userSalt,
            'userGroup' => '1',
            'yubikeyId' => '',
            'yubikeyId2' => '',
            'loginErrors' => '0'
            );
        $query = $tfDatabase->insert('user', $userData);

        // Create preference table.
        $preferenceColumns = array(
            "id" => "INTEGER",
            "title" => "TEXT",
            "value" => "TEXT"
        );
        $tfDatabase->createTable('preference', $preferenceColumns, 'id');

        // Insert default preferences to database.
        $preferenceData = array(
            array('title' => 'siteName', 'value' => 'Tuskfish CMS'),
            array('title' => 'siteDescription', 'value' => 'A cutting edge micro CMS'),
            array('title' => 'siteAuthor', 'value' => 'Tuskfish'),
            array('title' => 'siteEmail', 'value' => $adminEmail),
            array('title' => 'siteCopyright', 'value' => 'Copyright all rights reserved'),
            array('title' => 'closeSite', 'value' => '0'),
            array('title' => 'serverTimezone', 'value' => '0'),
            array('title' => 'siteTimezone', 'value' => '0'),
            array('title' => 'minSearchLength', 'value' => '3'),
            array('title' => 'searchPagination', 'value' => '20'),
            array('title' => 'userPagination', 'value' => '10'),
            array('title' => 'adminPagination', 'value' => '20'),
            array('title' => 'galleryPagination', 'value' => '20'),
            array('title' => 'paginationElements', 'value' => '5'),
            array('title' => 'rssPosts', 'value' => '10'),
            array('title' => 'sessionName', 'value' => 'tfish'),
            array('title' => 'sessionLife', 'value' => '20'),
            array('title' => 'defaultLanguage', 'value' => 'en'),
            array('title' => 'dateFormat', 'value' => 'j F Y'),
            array('title' => 'enableCache', 'value' => '0'),
            array('title' => 'cacheLife', 'value' => '86400')
        );

        foreach ($preferenceData as $preference) {
            $tfDatabase->insert('preference', $preference, 'id');
        }

        // Create session table.
        $sessionColumns = array(
            "id" => "INTEGER",
            "lastActive" => "INTEGER",
            "data" => "TEXT"
        );
        $tfDatabase->createTable('session', $sessionColumns, 'id');

        // Create content object table. Note that the type must be first column to enable
        // the PDO::FETCH_CLASS|PDO::FETCH_CLASSTYPE functionality, which automatically
        // pulls DB rows into an instance of a class, based on the first column.
        $contentColumns = array(
            "type" => "TEXT", // article => , image => , audio => , etc.
            "id" => "INTEGER", // Auto-increment => , set by database.
            "title" => "TEXT", // The headline or name of this content.
            "teaser" => "TEXT", // A short (one paragraph) summary or abstract for this content.
            "description" => "TEXT", // The full article or description of the content.
            "media" => "TEXT", // URL of an associated audio file.
            "format" => "TEXT", // Mimetype
            "fileSize" => "INTEGER", // Specify in bytes.
            "creator" => "TEXT", // Author.
            "image" => "TEXT", // URL of an associated image file => , eg. a screenshot a good way to handle it.
            "caption" => "TEXT", // Caption of the image file.
            "date" => "TEXT", // Date of first publication expressed as a string, hopefully in a standard format to allow time/date conversion.
            "parent" => "INTEGER", // A source work or collection of which this content is part.
            "language" => "TEXT", // English (future proofing).
            "rights" => "INTEGER", // Intellectual property rights scheme or license under which the work is distributed.
            "publisher" => "TEXT", // The entity responsible for distributing this work.
            "online" => "INTEGER", // Toggle object on or offline
            "submissionTime" => "INTEGER", // Timestamp representing submission time.
            "counter" => "INTEGER", // Number of times this content was viewed or downloaded.
            "metaTitle" => "TEXT", // Set a custom page title for this content.
            "metaDescription" => "TEXT", // Set a custom page meta description for this content.
            "seo" => "TEXT"); // SEO-friendly string; it will be appended to the URL for this content.
        $tfDatabase->createTable('content', $contentColumns, 'id');

        // Insert a "General" tag content object.
        $contentData = array(
            "type" => "TfTag",
            "title" => "General",
            "teaser" => "Default content tag.",
            "description" => "Default content tag, please edit it to something useful.",
            "date" => date('Y-m-d'),
            "language" => "en",
            "online" => "1",
            "submissionTime" => time(),
            "counter" => "0",
            "metaTitle" => "General",
            "metaDescription" => "General information.",
            "seo" => "general");
        $query = $tfDatabase->insert('content', $contentData);

        // Create taglink table.
        $taglinkColumns = array(
            "id" => "INTEGER",
            "tagId" => "INTEGER",
            "contentType" => "TEXT",
            "contentId" => "INTEGER");
        $tfDatabase->createTable('taglink', $taglinkColumns, 'id');
        
        // Close the database connection.
        $tfDatabase->close();

        // Report on status of database creation.
        if ($dbPath && $query) {
            $tfTemplate->pageTitle = TFISH_INSTALLATION_COMPLETE;
            $tfContent['output'] .= '<div class="row"><div class="text-left col-8 offset-2 mt-3"><h3><i class="fas fa-exclamation-triangle text-danger"></i> ' . TFISH_INSTALLATION_SECURE_YOUR_SITE . '</h3></div></div>';
            $tfContent['output'] .= '<div class="row"><div class="text-left col-8 offset-2">' . TFISH_INSTALLATION_SECURITY_INSTRUCTIONS . '</div></div>';
            $tfTemplate->output = $tfContent['output'];
            $tfTemplate->form = "success.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
        } else {
            // If database creation failed, complain and display data entry form again.
            $tfContent['output'] .= '<p>' . TFISH_INSTALLATION_DATABASE_FAILED . '</p>';
            $tfTemplate->output = $tfContent['output'];
            $tfTemplate->form = "dbCredentialsForm.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
        }
    }
} else {
    /**
     * Preflight checks
     */
    $tfContent['output'] .= '<div class="row"><div class="col-xs-6 offset-xs-3 col-lg-4 offset-md-4 text-left">';

    $requiredExtentions = array('sqlite3', 'PDO', 'pdo_sqlite', 'gd');
    $loadedExtensions = get_loaded_extensions();
    $presentList = '';
    $missingList = '';

    // Check PHP version 7.2+
    if (PHP_VERSION_ID < 70200) {
        $missingList = '<li><i class="fas fa-times text-danger"></i> ' . TFISH_PHP_VERSION_TOO_LOW . '</li>';
    } else {
        $presentList = '<li><i class="fas fa-check text-success"></i> ' . TFISH_PHP_VERSION_OK . '</li>';
    }

    // Check extensions.
    foreach ($requiredExtentions as $extension) {
        if (in_array($extension, $loadedExtensions, true)) {
            $presentList .= '<li><i class="fas fa-check text-success"></i> ' . $extension . ' '
                    . TFISH_EXTENSION . '</li>';
        } else {
            $missingList .= '<li><i class="fas fa-times text-danger"></i> ' . $extension . ' '
                    . TFISH_EXTENSION . '</li>';
        }
    }

    // Check path to mainfile.
    if (is_readable("../mainfile.php")) {
        $presentList .= '<li><i class="fas fa-check text-success"></i> ' . TFISH_PATH_TO_MAINFILE_OK . '</li>';
    }

    // Check root_path.
    if (defined("TFISH_ROOT_PATH") && is_readable(TFISH_ROOT_PATH)) {
        $presentList .= '<li><i class="fas fa-check text-success"></i> ' . TFISH_ROOT_PATH_OK . '</li>';
    } else {
        $missingList .= '<li><i class="fas fa-times text-danger"></i> ' . TFISH_ROOT_PATH_INVALID . '</li>';
    }

    // Check trust_path.
    if (defined("TFISH_TRUST_PATH") && is_readable(TFISH_TRUST_PATH)) {
        $presentList .= '<li><i class="fas fa-check text-success"></i> ' . TFISH_TRUST_PATH_OK . '</li>';
    } else {
        $missingList .= '<li><i class="fas fa-times text-danger"></i> ' . TFISH_TRUST_PATH_INVALID . '</li>';
    }

    if ($presentList) {
        $presentList = '<ul class="fa-ul">' . $presentList . '</ul>';
        $tfContent['output'] .= '<p><b>' . TFISH_SYSTEM_REQUIREMENTS_MET . '</b></p>'
                . $presentList;
    }

    if ($missingList) {
        $missingList = '<ul class="fa-ul">' . $missingList . '</ul>';
        $tfContent['output'] .= '<p><b>' . TFISH_SYSTEM_REQUIREMENTS_NOT_MET . '</b></p>'
                . $missingList;
    }
    
    $tfContent['output'] .= '</div></div>';
    
    // Display data entry form.
    $tfTemplate->pageTitle = TFISH_INSTALLATION_TUSKFISH;
    $tfTemplate->tfRootPath = realpath('../') . '/';
    $tfTemplate->form = "dbCredentialsForm.html";
    $tfTemplate->tfMainContent = $tfContent['output'] . $tfTemplate->render('form');
}

/**
 * Manually instantiate the metadata object.
 */
$tfMetadata = new TfMetadata($tfValidator, $tfPreference);
$tfMetadata->setTitle(TFISH_INSTALLATION_TUSKFISH);
$tfMetadata->setDescription('');
$tfMetadata->setRobots('noindex,nofollow');
$tfMetadata->setGenerator(''); // Do not advertise an installation script.

// Manual duplication of footer (as database is not yet available on first page view).
if ($tfTemplate && !empty($tfTemplate->getTheme())) {
    include_once TFISH_THEMES_PATH . $tfTemplate->getTheme() . "/theme.html";
} else {
    include_once TFISH_THEMES_PATH . "default/theme.html";
}

// Flush the output buffer to screen and clear it.
ob_end_flush();
