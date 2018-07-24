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

// 1. Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";

// 2. Main Tuskfish header. This file bootstraps Tuskfish.
require_once TFISH_PATH . "tf_header.php";

// 3. Content header sets module-specific paths and makes TfContentHandlerFactory available.
require_once TFISH_MODULE_PATH . "content/tf_content_header.php";

// Specify theme, otherwise 'default' will be used.
$tf_template->setTheme('rss');
$tf_template->target_file_name = '';

// Check if a collection- or tag-specific feed has been requested. Collections take priority.
$clean_id = isset($_GET['id']) ? (int) $_GET['id'] : 0; // ID of a collection object.
$clean_tag_id = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;

if ($clean_id && $clean_tag_id) {
    $clean_tag_id = false;
}

// Initialise RSS object.
header('Content-Type: application/rss+xml');
$rss = new TfRss($tf_preference, $tf_validator);

// Get a generic content object handler.
$content_handler = $content_handler_factory->getHandler('content');

// Prepare a mimetype lookup buffer.
$mimetype_list = $content_handler->getListOfMimetypes();

// Add items to feed. The number of items is controlled by the 'RSS posts' preference, but you can
// set a different limit here if you wish.
$criteria = $tf_criteria_factory->getCriteria();
$criteria->setOrder('submission_time');
$criteria->setOrderType('DESC');
$criteria->setOffset(0);
$criteria->setLimit($tf_preference->rss_posts);

if ($clean_tag_id) {
    $criteria->setTag(array($clean_tag_id));
    $rss->setLink(TFISH_RSS_URL . '?tag_id=' . $clean_tag_id);
}

// Optionally make a feed specific to a collection object.
if ($clean_id) {
    $collection = $content_handler->getObject($clean_id);
    
    if ($collection && $tf_validator->isObject($collection)) {
        $rss->makeFeedForCollection($collection);
        $criteria->add(new TfCriteriaItem($tf_validator, 'parent', $clean_id));
    }
}

// Do not allow tags, blocks or offline content objects to show in the feed.
$criteria->add(new TfCriteriaItem($tf_validator, 'type', 'TfTag', '!='));
$criteria->add(new TfCriteriaItem($tf_validator, 'type', 'TfBlock', '!='));
$criteria->add(new TfCriteriaItem($tf_validator, 'type', 'TfStatic', '!='));
$criteria->add(new TfCriteriaItem($tf_validator, 'online', 1));
$content_objects = $content_handler->getObjects($criteria);

// Assign to template. Note that timestamps will be converted to UTC based on server timezone.
$tf_template->rss_feed = $rss;
$tf_template->items = $content_objects;
$tf_template->mimetype_list = $mimetype_list;
$tf_template->tag_id = !empty($clean_tag_id) ? '?tag_id=' . (string) $clean_tag_id : '';
$tf_template->tf_main_content = $tf_template->render('feed');

// Include page template and flush buffer
require_once TFISH_PATH . "tf_footer.php";
