<?php
/**
 * Tuskfish Experts module header script. Must be included on Experts module pages.
 * 
 * Includes module-specific resources such as classes, forms and language constants.
 *
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     experts
 */
// Enable strict type declaration.
declare(strict_types=1);

// Set module-specific paths.
define("TFISH_EXPERTS_MODULE_PATH", TFISH_TRUST_PATH . 'modules/experts/');
define("TFISH_EXPERTS_MODULE_FORM_PATH", TFISH_EXPERTS_MODULE_PATH . 'form/');
define("TFISH_EXPERTS_MODULE_LANGUAGE_PATH", TFISH_EXPERTS_MODULE_PATH . 'language/');

// Make module language files available.
include TFISH_EXPERTS_MODULE_LANGUAGE_PATH . 'english.php';
/**
 * Autoload Tuskfish content module classes. spl_autoload_register() avoids namespace clashes.
 * @param string $classname Name of class to autoload. 
 */
function tfExpertsModuleAutoload(string $classname) {
    if (is_file(TFISH_EXPERTS_MODULE_PATH . 'class/' . $classname . '.php')) {
        include TFISH_EXPERTS_MODULE_PATH . 'class/' . $classname . '.php';
    }
}
spl_autoload_register('tfExpertsModuleAutoload');

$taglinkHandler = new TfTaglinkHandler($tfValidator, $tfDatabase, $tfCriteriaFactory);
$expertFactory = new TfExpertFactory($tfValidator, $tfDatabase, $tfCriteriaFactory,
        $tfFileHandler, $taglinkHandler, $tfCache, $tfTemplate);
