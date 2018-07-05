<?php

/**
 * Tuskfish home page demo controller script for the Cover theme.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     content
 */
// Enable strict type declaration.
declare(strict_types=1);

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// Specify theme, otherwise 'default' will be used.
$tfish_template->setTheme('cover');

// Page title, customise it as you see fit.
$tfish_template->page_title = "Cover your page";

// Set main page content (lead).
$tfish_template->lead = 'Cover is a one-page template for building simple and beautiful home pages.'
        . ' Download, edit the text, and add your own fullscreen background photo to make it your '
        . 'own.';

// Set button text.
$tfish_template->button_text = "Learn more";

/**
 * Override page template and metadata here (otherwise default site metadata will display).
 */
// $tfish_metadata->setTitle('');
// $tfish_metadata->setDescription('');
// $tfish_metadata->setAuthor('');
// $tfish_metadata->setCopyright('');
// $tfish_metadata->setGenerator('');
// $tfish_metadata->setSeo('');
// $tfish_metadata->setRobots('');

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";