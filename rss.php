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
require_once TFISH_PATH . "tfHeader.php";

// 3. Content header sets module-specific paths and makes TfContentHandler available.
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php";

// Specify theme, otherwise 'default' will be used.
$tfTemplate->setTheme('rss');
$tfTemplate->targetFileName = '';

// Check if a collection- or tag-specific feed has been requested. Collections take priority.
$cleanId = (int) ($_GET['id'] ?? 0); // ID of a collection object.
$cleanTagId = (int) ($_GET['tagId'] ?? 0);

if ($cleanId && $cleanTagId) {
    $cleanTagId = false;
}

// Initialise RSS object.
header('Content-Type: application/rss+xml');
$rss = new TfRss($tfValidator, $tfPreference);

// Get a generic content object handler.
$contentHandler = $contentFactory->getContentHandler('content');

// Prepare a mimetype lookup buffer.
$mimetypeList = $contentHandler->getListOfMimetypes();

// Add items to feed. The number of items is controlled by the 'RSS posts' preference, but you can
// set a different limit here if you wish.
$criteria = $tfCriteriaFactory->getCriteria();
$criteria->setOrder('submissionTime');
$criteria->setOrderType('DESC');
$criteria->setOffset(0);
$criteria->setLimit($tfPreference->rssPosts);

if ($cleanTagId) {
    $criteria->setTag(array($cleanTagId));
    $rss->setLink(TFISH_RSS_URL . '?tagId=' . $cleanTagId);
}

// Optionally make a feed specific to a collection object.
if ($cleanId) {
    $collection = $contentHandler->getObject($cleanId);
    
    if ($collection && $tfValidator->isObject($collection)) {
        $rss->makeFeedForCollection($collection);
        $criteria->add($tfCriteriaFactory->getItem('parent', $cleanId));
    }
}

// Do not allow tags, blocks or offline content objects to show in the feed.
$criteria->add($tfCriteriaFactory->getItem('type', 'TfTag', '!='));
$criteria->add($tfCriteriaFactory->getItem('type', 'TfBlock', '!='));
$criteria->add($tfCriteriaFactory->getItem('type', 'TfStatic', '!='));
$criteria->add($tfCriteriaFactory->getItem('online', 1));
$contentObjects = $contentHandler->getObjects($criteria);

// Assign to template. Note that timestamps will be converted to UTC based on server timezone.
$tfTemplate->rssFeed = $rss;
$tfTemplate->items = $contentObjects;
$tfTemplate->mimetypeList = $mimetypeList;
$tfTemplate->tagId = !empty($cleanTagId) ? '?tagId=' . (string) $cleanTagId : '';
$tfTemplate->tfMainContent = $tfTemplate->render('feed');

// Include page template and flush buffer
require_once TFISH_PATH . "tfFooter.php";
