<?php

/**
 * Admin controller script for sensors in the Machines module.
 * 
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     machines
 */
// Enable strict type declaration.
declare(strict_types=1);

// Boot! Set file paths, preferences and connect to database.
require_once "../mainfile.php";
require_once TFISH_ADMIN_PATH . "tfAdminHeader.php";
require_once TFISH_MODULE_PATH . "machines/tfMachinesHeader.php";

// Specify the admin theme you want to use.
$tfTemplate->setTheme('admin');

// Permitted options.
$op = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : false;
$optionsWhitelist = array('', 'add');

if (!in_array($op, $optionsWhitelist)) {
    exit;
}

// Cross-site request forgery check.
/*if (!in_array($op, $optionsWhitelist, true)) {
    TfSession::validateToken($cleanToken);
}*/

$sensorHandler = new TfSensorHandler($tfValidator, $tfDatabase, $tfCriteriaFactory, $tfFileHandler);

switch ($op) {
    case "add":
        $tfTemplate->sensorTypes = $sensorHandler->getSensorTypes();
        $tfTemplate->protocols = $sensorHandler->getDataProtocols();
        $tfTemplate->parentSelectOptions = array('---');
        $tfTemplate->pageTitle = TFISH_SENSORS;
        $tfTemplate->form = TFISH_MACHINES_MODULE_FORM_PATH . "sensorEntry.html";
        $tfTemplate->tfMainContent = $tfTemplate->render('form');
        break;
    
    default:
        $tfTemplate->pageTitle = TFISH_SENSORS;
        $tfTemplate->form = TFISH_MACHINES_MODULE_FORM_PATH . "sensorTable.html";
        $tfTemplate->tfMainContent = $tfTemplate->render('form');
        break;
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