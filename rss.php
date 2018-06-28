<?php

/**
 * RSS feed generator.
 * 
 * Generates a valid RSS feed for the site, optionally for a specific tag or collection object.
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
require_once TFISH_PATH . "tfish_header.php";

// Specify theme, otherwise 'default' will be used.
$tfish_template->setTheme('rss');
$tfish_template->target_file_name = '';

// Check if a collection- or tag-specific feed has been requested. Collections take priority.
$clean_id = isset($_GET['id']) ? (int) $_GET['id'] : 0; // ID of a collection object.
$clean_tag_id = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;

if ($clean_id && $clean_tag_id) {
    $clean_tag_id = false;
}

// Initialise RSS object.
header('Content-Type: application/rss+xml');
$rss = new TfishRss($tfish_preference);

// Prepare a mimetype lookup buffer.
$mimetype_list = TfishUtils::getMimetypes();

// Add items to feed. The number of items is controlled by the 'RSS posts' preference, but you can
// set a different limit here if you wish.
$criteria = new TfishCriteria();
$criteria->order = 'submission_time';
$criteria->ordertype = 'DESC';
$criteria->offset = 0;
$criteria->limit = $tfish_preference->rss_posts;

if ($clean_tag_id) {
    $criteria->tag = array($clean_tag_id);
    $rss->link .= '?tag_id=' . $clean_tag_id;
}

// Optionally make a feed specific to a collection object.
if ($clean_id) {
    $collection = TfishContentHandler::getObject($clean_id);
    
    if ($collection && TfishDataValidator::isObject($collection)) {
        $rss->makeFeedForCollection($collection);
        $criteria->add(new TfishCriteriaItem('parent', $clean_id));
    }
}

// Do not allow tags, blocks or offline content objects to show in the feed.
$criteria->add(new TfishCriteriaItem('type', 'TfishTag', '!='));
$criteria->add(new TfishCriteriaItem('type', 'TfishBlock', '!='));
$criteria->add(new TfishCriteriaItem('type', 'TfishStatic', '!='));
$criteria->add(new TfishCriteriaItem('online', 1));
$content_objects = TfishContentHandler::getObjects($criteria);

// Assign to template. Note that timestamps will be converted to UTC based on server timezone.
$tfish_template->rss_feed = $rss;
$tfish_template->items = $content_objects;
$tfish_template->mimetype_list = $mimetype_list;
$tfish_template->tag_id = !empty($clean_tag_id) ? '?tag_id=' . (string) $clean_tag_id : '';
$tfish_template->tfish_main_content = $tfish_template->render('feed');

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";
