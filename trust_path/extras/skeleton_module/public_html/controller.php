<?php

/**
 * Front end controller script for SOMEMODULE.
 *
 * Extended description of script goes here.
 * 
 * @copyright   Your name 2018+ (https://yoursite.com)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Your name <you@email.com>
 * @since       1.0
 * @package     SOMEMODULE
 */
// Enable strict type declaration.
declare(strict_types=1);

// Boot! Set file paths, preferences and connect to database.
require_once "mainfile.php";
require_once TFISH_PATH . "tfHeader.php";
require_once TFISH_MODULE_PATH . "someModule/tfSomeModuleHeader.php";

// Specify the theme you want to use.
$tfTemplate->setTheme('default');

/**
 * Validate input parameters here.
 **/

// Permitted options.
$op = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : false;
$optionsWhitelist = array();

if (!in_array($op, $optionsWhitelist, true)) {
    exit;
}
    
// Cross-site request forgery check.
if (!in_array($op, $optionsWhitelist, true)) {
    TfSession::validateToken($cleanToken);
}

// Business logic goes here.
switch ($op) {
    // Various cases.
}

/**
 * Override page template here (otherwise default site metadata will display).
 */
// $tfMetadata->setTitle('');
// $tfMetadata->setDescription('');
// $tfMetadata->setAuthor('');
// $tfMetadata->setCopyright('');
// $tfMetadata->setGenerator('');
// $tfMetadata->setSeo('');
$tfMetadata->setRobots('noindex,nofollow');

// Include page template and flush buffer
require_once TFISH_PATH . "tfFooter.php";
