<?php

/**
* Tuskfish permalink script.
* 
* Site preferences can be accessed via $tfish_preference->key.
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

/**
 * CONVENTIONS:
 * 1. Specify the class name of the handler for the object type this page will handle, eg. 'TfishArticleHandler'.
 * 2. Specify the name of the template for the index page, eg. 'articles'.
 * 3. (In type-specific pages) the name of this file (without extension) should be the same as the 
 *    value of the object's 'module' field. If you want to change the file name, change the module
 *    value in the object class as well.
 */
$content_handler = 'TfishContentHandler';

// Page title.
$tfish_template->page_title = TFISH_TYPE_PERMALINKS;

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// View single object description.
if ($clean_id) {
	$content = $content_handler::getObject($clean_id);
	if (isset($content) && is_object($content)) {
		$tfish_template->tags = $content_handler::makeTagLinks($content->tags, false);
		$tfish_template->content = $content;
		$tfish_template->tfish_main_content = $tfish_template->render($content->template);
	} else {
		$tfish_template->tfish_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
	}
} else {
	$tfish_template->tfish_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
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