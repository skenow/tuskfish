<?php

/**
* Tuskfish index page script for the marketing theme.
* 
* User-facing controller script for presenting all content objects other than tags and static content.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
*/

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// Get the relevant handler.
$content_handler = 'TfishContentHandler';

// Specify template set, otherwise 'default' will be used.
$tfish_template->template_set = 'default';
$target_file_name = 'index';
$index_template = 'single_stream';

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int)$_GET['tag_id'] : 0;

$rss_url = !empty($clean_tag) ? TFISH_RSS_URL . '?tag_id=' . $clean_tag : TFISH_RSS_URL;

if ($clean_id) {
    
    // Retrieve target object.
    $content = $content_handler::getObject($clean_id);
    if (is_object($content) && $content->online == true) {

        // Update view counter and assign object to template. Only increment counter for non-downloadable objects.
        if ($content->type != 'TfishDownload') {
                $content->counter += 1;
                $content_handler::updateCounter($clean_id);
        }
        $tfish_template->content = $content;

        // Prepare meta information for display.
        $contentInfo = array();
        if ($content->creator) $contentInfo[] = $content->escape('creator');
        if ($content->date) $contentInfo[] = $content->escape('date');
        if ($content->counter) $contentInfo[] = $content->escape('counter') . ' ' . TFISH_VIEWS;
        if ($content->format) $contentInfo[] = '.' . $content->escape('format');
        if ($content->file_size) $contentInfo[] = $content->escape('file_size');
        if ($content->tags) {
                $tags = $content_handler::makeTagLinks($content->tags, false); // For a content type-specific page use $content->tags, $content->template
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
        
        // Initialise criteria object.
        $criteria = new TfishCriteria();
        $criteria->order = 'date';
        $criteria->ordertype = 'DESC';

        // If object is a collection check if has child objects; if so display thumbnails and teasers / links.
        if ($content->type == 'TfishCollection') {
            $criteria->add(new TfishCriteriaItem('parent', $content->id));
            $criteria->add(new TfishCriteriaItem('online', 1));
            if ($clean_start) $criteria->offset = $clean_start;
            $criteria->limit = $tfish_preference->user_pagination;
        }
        
        // If object is a tag, then a different method is required to call the related content.
        if ($content->type == 'TfishTag') {
            if ($clean_start) $criteria->offset = $clean_start;
            $criteria->limit = $tfish_preference->user_pagination;
            $criteria->tag = array($content->id);
            $criteria->add(new TfishCriteriaItem('online', 1));
        }
        
        // Prepare pagination control.
		if ($content->type == 'TfishCollection' || $content->type == 'TfishTag') {
			$first_child_count = TfishContentHandler::getCount($criteria);
			$tfish_template->collection_pagination = $tfish_metadata->getPaginationControl($first_child_count,
				$tfish_preference->user_pagination, $target_file_name, $clean_start, 0, array('id' => $clean_id));

			// Retrieve content objects and assign to template.
			$first_children = TfishContentHandler::getObjects($criteria);
			if (!empty($first_children)) {
					$tfish_template->first_children = $first_children;
			}
		}

        // Render template.
        $tfish_template->tfish_main_content = $tfish_template->render($content->template);
    } else {
        $tfish_template->tfish_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
    }
} else {
    // Page title
    //$tfish_template->page_title = TFISH_NEWS;
	
    // View index page of multiple objects (teasers). Static pages and tags are excluded.
    $criteria = new TfishCriteria();
    if ($clean_start) $criteria->offset = $clean_start;
    $criteria->limit = $tfish_preference->user_pagination;
    if ($clean_tag) $criteria->tag = array($clean_tag);
    $criteria->add(new TfishCriteriaItem('type', 'TfishTag', '!='));
    $criteria->add(new TfishCriteriaItem('type', 'TfishStatic', '!='));
    $criteria->add(new TfishCriteriaItem('online', 1));

    // Prepare pagination control.
    $count = $content_handler::getCount($criteria);
    $tfish_template->pagination = $tfish_metadata->getPaginationControl($count, $tfish_preference->user_pagination, TFISH_URL, $clean_start, $clean_tag);

    // Retrieve content objects and assign to template.
    $criteria->order = 'date';
    $criteria->ordertype = 'DESC';
    $content_objects = $content_handler::getObjects($criteria);
    $tfish_template->content_objects = $content_objects;
    $tfish_template->tfish_main_content = $tfish_template->render($index_template);

    // Prepare tag select box. In this case, I am using a hard coded ID to retrieve a specific
	// collection of tags (you can group tags into collections by creating collection objects and
	// assigning tags via their 'parent' field.
    $tfish_template->select_action = 'index.php';
    $tfish_template->select_filters =  TfishTagHandler::TagSelectBox($clean_tag);
    $tfish_template->select_filters_form = $tfish_template->render('select_filters');
}

/**
//Prepare new $criteria for blocks. Let's try dynamic tagging.
$criteria = new TfishCriteria();
if ($clean_tag) $criteria->tag = array($clean_tag);
$criteria->add(new TfishCriteriaItem('online', 1));

// Prepare blocks for centre-top-zone.
$centre_top_blocks = array();	
$block_list = new TfishBlockList('Top left block');
$block_list->build($criteria);
$centre_top_blocks[] = $block_list->render();

$block_list2 = new TfishBlockList('Top centre block');
$criteria->ordertype = 'ASC';
$block_list2->build($criteria);
$centre_top_blocks[] = $block_list2->render();

$block_list3 = new TfishBlockList('Top right block');
$block_list3->build($criteria);
$centre_top_blocks[] = $block_list3->render();	

$tfish_template->centre_top_blocks = $centre_top_blocks;

// Prepare blocks for centre-bottom-zone.	
$centre_bottom_blocks = array();
$block_list = new TfishBlockList('Bottom left block');
$block_list->build($criteria);
$centre_bottom_blocks[] = $block_list->render();

$block_list2 = new TfishBlockList('Bottom centre block');
$criteria->ordertype = 'ASC';
$block_list2->build($criteria);
$centre_bottom_blocks[] = $block_list2->render();

$block_list3 = new TfishBlockList('Bottom right block');
$block_list3->build($criteria);
$centre_bottom_blocks[] = $block_list3->render();

$tfish_template->centre_bottom_blocks = $centre_bottom_blocks;
*/

require_once TFISH_PATH . "tfish_footer.php";