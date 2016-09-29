<?php

/**
* Tuskfish video script.
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
 * CONFIGURATION:
 * 1. Specify the class name of the handler for the object type this page will handle, eg. 'TfishVideoHandler'.
 * 2. Specify the name of the template for the index page, eg. 'videos'.
 * 3. The name of this file (without extension) should be the same as the value of the object's 'module' field.
 *    If you want to change the file name, change the module value in the object class as well.
 */
$content_handler = 'TfishVideoHandler';
$index_template = 'videos';
$target_file_name = 'videos';

// Page title.
$tfish_template->page_title = TFISH_TYPE_VIDEOS;

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
		$tfish_template->tags = $content_handler::makeTagLinks($content->tags, $content->module);
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
	$tfish_template->pagination = $tfish_metadata->getPaginationControl($count, 
			$tfish_preference->user_pagination, $target_file_name, $clean_start, $clean_tag);
	
	// Retrieve content objects and assign to template.
	$content_objects = $content_handler::getObjects($criteria);
	$tfish_template->content_objects = $content_objects;
	$tfish_template->tfish_main_content = $tfish_template->render($index_template);
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