<?php

/**
* Tuskfish permalink script.
* 
* Provides a permalink lookup service for all content objects. Simply supply the ID of the content.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// Specify template set, otherwise 'default' will be used.
// $tfish_template->setTemplate('jumbotron');

/**
 * CONVENTIONS:
 * 1. Specify the class name of the handler for the object type this page will handle, eg. 'TfishArticleHandler'.
 * 2. Specify the name of the template for the index page, eg. 'articles'.
 * 3. (In type-specific pages) the name of this file (without extension) should be the same as the 
 *    value of the object's 'module' field. If you want to change the file name, change the module
 *    value in the object class as well.
 */
$content_handler = 'TfishContentHandler';
$target_file_name = 'permalink';

// Page title.
$tfish_template->page_title = TFISH_TYPE_PERMALINKS;

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int)$_GET['start'] : 0;

// View single object description.
if ($clean_id) {
	$content = $content_handler::getObject($clean_id);
	if (is_object($content) && $content->online == true) {
		
		// Update view counter and assign object to template.
		$content->counter += 1;
		$content_handler::updateCounter($clean_id);
		$tfish_template->content = $content;
		
		// Prepare meta information for display.
		$contentInfo = array();
		if ($content->creator) $contentInfo[] = $content->escape('creator');
		if ($content->date) $contentInfo[] = $content->escape('date');
		if ($content->counter) $contentInfo[] = $content->escape('counter') . ' ' . TFISH_VIEWS;
		if ($content->format) $contentInfo[] = '.' . $content->escape('format');
		if ($content->file_size) $contentInfo[] = $content->escape('file_size');
		if ($content->tags) {
			$tags = $content_handler::makeTagLinks($content->tags, $content->module); // For a content type-specific page use $content->tags, $content->template
			$tags = TFISH_TAGS . ': ' . implode(', ', $tags);
			$contentInfo[] = $tags;
		}
		$tfish_template->contentInfo = implode(' | ', $contentInfo);
		if ($content->meta_title) $tfish_metadata->title = $content->meta_title;
		if ($content->meta_description) $tfish_metadata->description = $content->meta_description;
		
		// Check if has a parental object; if so display a thumbnail and teaser / link.
		if (!empty($content->parent)) {
			$parent = $content_handler::getObject($content->parent);
			if (is_object($parent) && $parent->online) {
				$tfish_template->parent = $parent;
			}
		}
		
		// Check if has child objects; if so display thumbnails and teasers / links.
		$criteria = new TfishCriteria();
		$criteria->add(new TfishCriteriaItem('parent', $content->id));
		$criteria->add(new TfishCriteriaItem('online', 1));
		if ($clean_start) {
			$criteria->offset = $clean_start;
		}
		$criteria->limit = $tfish_preference->user_pagination;
		$criteria->order = 'date';
		$criteria->ordertype = 'DESC';
		
		// Prepare pagination control.
		$first_child_count = TfishContentHandler::getCount($criteria);
		$tfish_template->collection_pagination = $tfish_metadata->getPaginationControl($first_child_count,
			$tfish_preference->user_pagination, $target_file_name, $clean_start, 0, array('id' => $clean_id));
		
		// Retrieve content objects and assign to template.
		$first_children = TfishContentHandler::getObjects($criteria);
		if (!empty($first_children)) {
			$tfish_template->first_children = $first_children;
		}
		
		// Render template.
		$tfish_template->tfish_main_content = $tfish_template->render($content->template);
		
	} else {
		$tfish_template->tfish_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
	}
} else {
	$tfish_template->tfish_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
}

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
$tfish_metadata->title = TFISH_TYPE_PERMALINKS;
// $tfish_metadata->description = '';
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
$tfish_metadata->robots = 'noindex,nofollow'; // Don't want search engines indexing duplicate content.

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";