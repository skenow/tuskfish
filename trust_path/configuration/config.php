<?php

/**
 * Tuskfish configuration script.
 * 
 * Stores the site salt (used for recursive password hashing), key and database path. Included in
 * every page via mainfile.php / masterfile.php  
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */
if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

// Constants that make use of the physical path.
define("TFISH_ADMIN_PATH", TFISH_ROOT_PATH . "admin/");
define("TFISH_PUBLIC_CACHE_PATH", TFISH_ROOT_PATH . "cache/");
define("TFISH_THEMES_PATH", TFISH_ROOT_PATH . "themes/");
define("TFISH_JS_PATH", TFISH_ROOT_PATH . "js/");
define("TFISH_UPLOADS_PATH", TFISH_ROOT_PATH . "uploads/");
define("TFISH_MEDIA_PATH", TFISH_UPLOADS_PATH . "media/");
define("TFISH_IMAGE_PATH", TFISH_UPLOADS_PATH . 'image/');

// Constants that make use of the trust path (which is a derivative of the physical path).
define("TFISH_CLASS_PATH", TFISH_PATH . "class/");
define("TFISH_DATABASE_PATH", TFISH_TRUST_PATH . "database/");
define("TFISH_ERROR_LOG_PATH", TFISH_TRUST_PATH . "log/tuskfish_log.txt");
define("TFISH_FORM_PATH", TFISH_PATH . "form/");
define("TFISH_LIBRARIES_PATH", TFISH_TRUST_PATH . "libraries/");
define("TFISH_PRIVATE_CACHE_PATH", TFISH_TRUST_PATH . "cache/");

// Constants that make use of the virtual (URL) path, these refer to assets accessed by URL.
define("TFISH_ADMIN_URL", TFISH_URL . "admin/");
define("TFISH_CACHE_URL", TFISH_URL . "cache/");
define("TFISH_THEMES_URL", TFISH_URL . "themes/");
define("TFISH_JS_URL", TFISH_URL . "js/");
define("TFISH_RSS_URL", TFISH_URL . "rss.php");
define("TFISH_PERMALINK_URL", TFISH_URL);
define("TFISH_MEDIA_URL", TFISH_URL . "uploads/media/");
define("TFISH_IMAGE_URL", TFISH_URL . "uploads/image/");

// Alias of TFISH_URL (without trailing slash) for use in teaser/description fields. Use this to
// make your content portable (if you change domain, all your links will still be valid). The
// trailing slash is omitted for ease of reading in the editor.
define("TFISH_LINK", rtrim(TFISH_URL, '/'));

// RSS enclosure URL - spec requires that the URL use http protocol, as https will invalidate feed.
if (parse_url(TFISH_URL, PHP_URL_SCHEME) == 'https') {
    define("TFISH_ENCLOSURE_URL", "http://" . parse_url(TFISH_URL, PHP_URL_HOST)
            . "/enclosure.php?id=");
} else {
    define("TFISH_ENCLOSURE_URL", TFISH_URL . "enclosure.php?id=");
}

/*
 * Preferences
 */
// Language: Specify the file name of the default language file.
define("TFISH_LANGUAGE_PATH", TFISH_PATH . "language/");
define("TFISH_DEFAULT_LANGUAGE", TFISH_LANGUAGE_PATH . "english.php");

/**
 * Autoload core Tuskfish classes. spl_autoload_register() avoids namespace clashes.
 * @param string $classname Name of class to autoload. 
 */
function tfish_autoload($classname) {
    include TFISH_CLASS_PATH . $classname . '.php';
}
spl_autoload_register('tfish_autoload');

// Site salt, key and database name are appended here.