<?php

/**
 * Tuskfish default index page script.
 * 
 * User-facing controller script for presenting all content objects other than tags and static content.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since		1.0
 * @package		core
 */
// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// Specify template set, otherwise 'default' will be used.
$tfish_template->template_set = 'marketing'; // Specify the template subdirectory for this template set.

$tfish_template->page_title = TFISH_ERROR;
$tfish_template->error_message = TFISH_SORRY_WE_ENCOUNTERED_AN_ERROR;
$tfish_template->tfish_main_content = $tfish_template->render('error');

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
$tfish_metadata->title = TFISH_ERROR;
// $tfish_metadata->description = '';
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
// $tfish_metadata->robots = '';
// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";
