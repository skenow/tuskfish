<?php

/**
* Tuskfish default index page script.
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

// Specify template set, otherwise 'default' will be used.
$tfish_template->template_set = 'default'; // Specify the template subdirectory for this template set.

$content_handler = 'TfishContentHandler';
$index_template = 'single_stream';

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int)$_GET['tag_id'] : 0;

$rss_url = !empty($clean_tag) ? TFISH_RSS_URL . '?tag_id=' . $clean_tag : TFISH_RSS_URL;

// Page title.
$tfish_template->page_title = '<a href="' . $rss_url . '"><i class="fa fa-rss" aria-hidden="true"></i></a> ' . TFISH_LATEST_POSTS;
	
// View index page of multiple objects (teasers).
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

// Prepare tag select box.
$tfish_template->select_action = 'index.php';
$tfish_template->select_filters =  TfishTagHandler::getTagSelectBox($clean_tag);
$tfish_template->select_filters_form = $tfish_template->render('select_filters');

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