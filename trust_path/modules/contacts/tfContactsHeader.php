<?php
/**
 * Tuskfish Contacts module header script. Must be included on Contacts module pages.
 * 
 * Includes module-specific resources such as classes, forms and language constants.
 *
 * @copyright   Simon Wilkinson 2018+ (https://yoursite.com)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     contacts
 */
// Enable strict type declaration.
declare(strict_types=1);

// Set module-specific paths.
define("TFISH_CONTACTS_MODULE_PATH", TFISH_TRUST_PATH . 'modules/contacts/');
define("TFISH_CONTACTS_MODULE_FORM_PATH", TFISH_CONTACTS_MODULE_PATH . 'form/');
define("TFISH_CONTACTS_MODULE_LANGUAGE_PATH", TFISH_CONTACTS_MODULE_PATH . 'language/');

// Make module language files available.
include TFISH_CONTACTS_MODULE_LANGUAGE_PATH . 'english.php';

/**
 * Autoload Tuskfish content module classes. spl_autoload_register() avoids namespace clashes.
 * @param string $classname Name of class to autoload. 
 */
function tfContactsModuleAutoload(string $classname) {
    if (is_file(TFISH_CONTACTS_MODULE_PATH . 'class/' . $classname . '.php')) {
        include TFISH_CONTACTS_MODULE_PATH . 'class/' . $classname . '.php';
    }
}
spl_autoload_register('tfContactsModuleAutoload');

// Instantiate factories for contacts and contact handler.
$contactFactory = new TfContactFactory($tfValidator);
$contactHandlerFactory = new TfContactHandlerFactory($tfValidator, $tfDatabase, $tfCriteriaFactory,
        $tfCriteriaItemFactory, $contactFactory);
