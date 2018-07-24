<?php
/**
 * Tuskfish someModule header script. Must be included on someModule pages.
 * 
 * Includes module-specific resources such as classes, forms and language constants.
 *
 * @copyright   Your name 2018+ (https://yoursite.com)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Your name <you@email.com>
 * @since       1.0
 * @package     core
 */
// Enable strict type declaration.
declare(strict_types=1);

// Set module-specific paths.
define("TFISH_SOME_MODULE_PATH", TFISH_TRUST_PATH . 'modules/someModule/');
define("TFISH_SOME_MODULE_FORM_PATH", TFISH_SOME_MODULE_PATH . 'form/');
define("TFISH_SOME_MODULE_LANGUAGE_PATH", TFISH_SOME_MODULE_PATH . 'language/');

// Make module language files available.
include TFISH_SOME_MODULE_LANGUAGE_PATH . 'english.php';
/**
 * Autoload Tuskfish content module classes. spl_autoload_register() avoids namespace clashes.
 * @param string $classname Name of class to autoload. 
 */
function tf_some_module_autoload(string $classname) {
    if (is_file(TFISH_SOME_MODULE_PATH . 'class/' . $classname . '.php')) {
        include TFISH_SOME_MODULE_PATH . 'class/' . $classname . '.php';
    }
}
spl_autoload_register('tf_some_module_autoload');
