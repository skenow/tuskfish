<?php

/**
 * Displays a warning message that an invalid token has been encountered.
 * 
 * Form requests that fail a cross-site request forgery check redirect here. In most cases the
 * failure will be caused by session expiry.
 *
 * @copyright	Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since		1.0
 * @package		content
 */
// Enable strict type declaration.
declare(strict_types=1);

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfHeader.php";

// Specify theme, otherwise 'default' will be used.
$tfTemplate->setTheme('default');

$tfTemplate->pageTitle = TFISH_INVALID_TOKEN;
$tfTemplate->errorMessage = TFISH_SORRY_INVALID_TOKEN;
$tfTemplate->tfMainContent = $tfTemplate->render('error');

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
$tfMetadata->setTitle(TFISH_INVALID_TOKEN);
// $tfMetadata->setDescription('');
// $tfMetadata->setAuthor('');
// $tfMetadata->setCopyright('');
// $tfMetadata->setGenerator('');
// $tfMetadata->setSeo('');
// $tfMetadata->setRobots('');
        
// Include page template and flush buffer
require_once TFISH_PATH . "tfFooter.php";
