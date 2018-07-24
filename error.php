<?php

/**
 * Displays 404 error message and a search box.
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
require_once TFISH_PATH . "tf_header.php";

// Specify theme, otherwise 'default' will be used.
$tf_template->setTheme('default');

$tf_template->page_title = TFISH_ERROR;
$tf_template->error_message = TFISH_SORRY_WE_ENCOUNTERED_AN_ERROR;
$tf_template->tf_main_content = $tf_template->render('error');

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
$tf_metadata->setTitle(TFISH_ERROR);
// $tf_metadata->setDescription('');
// $tf_metadata->setAuthor('');
// $tf_metadata->setCopyright('');
// $tf_metadata->setGenerator('');
// $tf_metadata->setSeo('');
$tf_metadata->setRobots('noindex,nofollow');

// Include page template and flush buffer
require_once TFISH_PATH . "tf_footer.php";
