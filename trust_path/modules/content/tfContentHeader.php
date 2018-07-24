<?php
/**
 * Tuskfish content module header script. Must be included on content module pages.
 * 
 * Includes module-specific resources such as classes, forms and language constants.
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */
// Enable strict type declaration.
declare(strict_types=1);

// Set module-specific paths.
define("TFISH_CONTENT_MODULE_PATH", TFISH_TRUST_PATH . 'modules/content/');
define("TFISH_CONTENT_MODULE_FORM_PATH", TFISH_CONTENT_MODULE_PATH . 'form/');
define("TFISH_CONTENT_MODULE_LANGUAGE_PATH", TFISH_CONTENT_MODULE_PATH . 'language/');

// Make module language files available.
include TFISH_CONTENT_MODULE_LANGUAGE_PATH . 'english.php';
/**
 * Autoload Tuskfish content module classes. spl_autoload_register() avoids namespace clashes.
 * @param string $classname Name of class to autoload. 
 */
function tfContentModuleAutoload(string $classname) {
    if (is_file(TFISH_CONTENT_MODULE_PATH . 'class/' . $classname . '.php')) {
        include TFISH_CONTENT_MODULE_PATH . 'class/' . $classname . '.php';
    }
}
spl_autoload_register('tfContentModuleAutoload');

// Make the content handler factory available.
$contentHandlerFactory = new TfContentHandlerFactory($tfValidator, $tfDatabase,
            $tfCriteriaFactory, $tfCriteriaItemFactory, $tfFileHandler);


