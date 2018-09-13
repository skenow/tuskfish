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
define("TFISH_MACHINES_MODULE_PATH", TFISH_TRUST_PATH . 'modules/machines/');
define("TFISH_MACHINES_MODULE_FORM_PATH", TFISH_MACHINES_MODULE_PATH . 'form/');
define("TFISH_MACHINES_MODULE_LANGUAGE_PATH", TFISH_MACHINES_MODULE_PATH . 'language/');

// Make module language files available.
include TFISH_MACHINES_MODULE_LANGUAGE_PATH . 'english.php';
/**
 * Autoload Tuskfish content module classes. spl_autoload_register() avoids namespace clashes.
 * @param string $classname Name of class to autoload. 
 */
function tfMachinesModuleAutoload(string $classname) {
    if (is_file(TFISH_MACHINES_MODULE_PATH . 'class/' . $classname . '.php')) {
        include TFISH_MACHINES_MODULE_PATH . 'class/' . $classname . '.php';
    }
}
spl_autoload_register('tfMachinesModuleAutoload');

$machineFactory = new TfMachineFactory($tfValidator, $tfDatabase, $tfCriteriaFactory,
        $tfFileHandler, $tfCache, $tfTemplate);
$sensorFactory = new TfSensorFactory($tfValidator, $tfDatabase, $tfCriteriaFactory,
        $tfFileHandler, $tfCache, $tfTemplate);
