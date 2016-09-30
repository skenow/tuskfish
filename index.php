<?php

/**
* Tuskfish basic page template script.
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
$content_handler = 'TfishArticleHandler';
$index_template = 'articles';

// Page title.
$tfish_template->page_title = TFISH_TYPE_ARTICLES;

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int)$_GET['tag_id'] : 0;

/**
 * Controller logic.
 */

// View single object description.
if ($clean_id) {
	$content = $content_handler::getObject($clean_id);
	if (is_object($content)) {
		// For a content type-specific page specify the file name (without extension) or use $content->template if it is the same as your file name.
		$tfish_template->tags = $content_handler::makeTagLinks($content->tags, false);
		$tfish_template->content = $content;
		$tfish_template->tfish_main_content = $tfish_template->render($content->template);
	} else {
		$tfish_template->error = TFISH_ERROR_NO_SUCH_CONTENT;
	}
	
// View index page of multiple objects (teasers).
} else {
	// Set criteria for selecting content objects.
	$criteria = new TfishCriteria();
	if ($clean_start) $criteria->offset = $clean_start;
	$criteria->limit = $tfish_preference->user_pagination;
	if ($clean_tag) $criteria->tag = array($clean_tag);
	
	// Prepare pagination control.
	$count = $content_handler::getCount($criteria);
	$tfish_template->pagination = $tfish_metadata->getPaginationControl($count, $tfish_preference->user_pagination, TFISH_URL, $clean_start, $clean_tag);

	// Retrieve content objects and assign to template.
	$content_objects = $content_handler::getObjects($criteria);
	$tfish_template->content_objects = $content_objects;
	$tfish_template->tfish_main_content = $tfish_template->render($index_template);
	
	// Prepare tag select box.
	$tfish_template->tag_select_box = TfishTagHandler::getTagSelectBox();
	
	// Prepare blocks you wish to display.
	$block_array = array();
	$criteria = new TfishCriteria();
	$block_list = new TfishBlockList('My test block');
	$block_list->build($criteria);
	$block_array[] = $block_list->render();
	
	$block_list2 = new TfishBlockList('My second test block');
	$criteria->ordertype = 'ASC';
	$block_list2->build($criteria);
	$block_array[] = $block_list2->render();	
	
	$tfish_template->centre_top_blocks = $block_array;
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