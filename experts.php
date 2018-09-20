<?php

/**
 * Front end controller script for the Experts module.
 * 
 * @copyright   Simon Wilkinson 2018+ (https://isengard.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     experts
 */
// Enable strict type declaration.
declare(strict_types=1);

// Boot! Set file paths, preferences and connect to database.
require_once "mainfile.php";
require_once TFISH_PATH . "tfHeader.php";
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php";
require_once TFISH_MODULE_PATH . "experts/tfExpertsHeader.php";

// Specify the theme you want to use.
$tfTemplate->setTheme('default');
$indexTemplate = 'experts';

// Set target file for intra-collection pagination controls when viewing objects.
$targetFileName = 'experts';
$tfTemplate->targetFileName = $targetFileName;

// Validate input parameters.
$cleanId = (int) ($_REQUEST['id'] ?? 0);
$cleanStart = (int) ($_GET['start'] ?? 0);
$cleanTag = (int) ($_GET['tagId'] ?? 0);

$expertHandler = $expertFactory->getExpertHandler();

switch ($op) {
    default:
        
        $expertList = $expertHandler->getObjects();
        $tfTemplate->pageTitle = TFISH_EXPERTS;
        $tfTemplate->expertList = $expertList;
        break;
}

$tfTemplate->tfMainContent = $tfTemplate->render($indexTemplate);

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