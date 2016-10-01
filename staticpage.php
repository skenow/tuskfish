<?php

/**
* Tuskfish static page template script.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// CONFIGURATION: Enter the object ID and page title you want to display on this static page.
$id = 23;
$tfish_template->page_title = TFISH_TYPE_STATIC_PAGES;

// View single object description.
$clean_id = (int)$id;
if ($clean_id) {
	$content = TfishContentHandler::getObject($clean_id);
	if (is_object($content) && $content->online) {
		$tfish_template->tags = TfishContentHandler::makeTagLinks($content->tags, false);
		$tfish_template->content = $content;
		$tfish_template->tfish_main_content = $tfish_template->render($content->template);
	} else {
		$tfish_template->tfish_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
	}
}

/**
 * Override page template and metadata here (otherwise default site metadata will display).
 */
// $tfish_metadata->title = '';
// $tfish_metadata->description = '';
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
// $tfish_metadata->robots = '';
// $tfish_metadata->template = 'jumbotron.html';

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";