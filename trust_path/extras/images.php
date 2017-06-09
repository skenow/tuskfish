<?php

/**
 * Image index page script.
 *
 * User-facing controller script for presenting a list of image content as a grid of thumbnails.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since		1.0
 * @package		core
 */
// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
// Site preferences can be accessed via $tfish_preference->key;
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// Specify template set, otherwise 'default' will be used.
// $tfish_template->setTemplate('jumbotron');

/**
 * CONVENTIONS:
 * 1. Specify the class name of the handler for the object type this page will handle, eg. 'TfishArticleHandler'.
 * 2. Specify the name of the template for the index page, eg. 'articles'.
 */
$content_handler = 'TfishImageHandler';
$index_template = 'images';
$target_file_name = 'images';

// Page title.
$tfish_template->page_title = TFISH_TYPE_IMAGES;

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;

/**
 * Controller logic.
 */
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
        if ($content->creator)
            $contentInfo[] = $content->escape('creator');
        if ($content->date)
            $contentInfo[] = $content->escape('date');
        if ($content->counter)
            $contentInfo[] = $content->escape('counter') . ' ' . TFISH_VIEWS;
        if ($content->tags) {
            $tags = $content_handler::makeTagLinks($content->tags, $target_file_name); // For a content type-specific page use $content->tags, $content->template
            $tags = TFISH_TAGS . ': ' . implode(', ', $tags);
            $contentInfo[] = $tags;
        }
        $tfish_template->contentInfo = implode(' | ', $contentInfo);
        if ($content->meta_title)
            $tfish_metadata->title = $content->meta_title;
        if ($content->meta_description)
            $tfish_metadata->description = $content->meta_description;

        // Check if has a parental object; if so display a thumbnail and teaser / link.
        if (!empty($content->parent)) {
            $parent = $content_handler::getObject($content->parent);
            if (is_object($parent) && $parent->online) {
                $tfish_template->parent = $parent;
            }
        }

        // Render template.
        $tfish_template->tfish_main_content = $tfish_template->render($content->template);
    } else {
        $tfish_template->tfish_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
    }

// View index page of multiple objects (teasers).
} else {
    // Set criteria for selecting content objects.
    $criteria = new TfishCriteria();
    if ($clean_start)
        $criteria->offset = $clean_start;
    $criteria->limit = $tfish_preference->user_pagination;
    if ($clean_tag)
        $criteria->tag = array($clean_tag);
    $criteria->add(new TfishCriteriaItem('online', 1));

    // Prepare pagination control.
    $count = $content_handler::getCount($criteria);
    $tfish_template->pagination = $tfish_metadata->getPaginationControl($count, $tfish_preference->user_pagination, $target_file_name, $clean_start, $clean_tag);

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
// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";
