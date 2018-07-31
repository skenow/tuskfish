<?php

/**
 * Generates a sitemap for Tuskfish CMS base node IDs.
 * 
 * Outputs a simple list of URLs for user-facing, online content only. You can submit this to
 * Google or link to it in your robots.txt to guide search engines. If you have created custom 
 * static pages with unique file names you will need to add them manually, and also delete the
 * automatically generated ID-based entry for that object, to avoid duplication.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
*/
// Enable strict type declaration.
declare(strict_types=1);

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfHeader.php";
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php";

// Get a generic handler.
$contentHandler = $contentHandlerFactory->getHandler('content');

/** 
 * Sitemap generation code.
 */

// Initialise.
$onlineContentIds = array();
$offlineTagIds = array();
$columns = array('id', 'seo');
$sitemap = '';

// Get the IDs of all online objects (and offline tags), but not blocks.
$criteria = $tfCriteriaFactory->getCriteria();
$criteria->add(new TfCriteriaItem($tfValidator, 'type', 'TfBlock', '!='));
$criteria->add(new TfCriteriaItem($tfValidator, 'online', 1));
$criteria->setOrder('id');
$criteria->setOrderType('ASC');
$contentIds = $contentHandler->getListOfObjectTitles($criteria);

// Need to do tags marked as offline, also, as these are not actually offline.
$criteria = $tfCriteriaFactory->getCriteria();
$criteria->add(new TfCriteriaItem($tfValidator, 'type', 'TfTag'));
$criteria->add(new TfCriteriaItem($tfValidator, 'online', 0));
$criteria->setOrder('id');
$criteria->setOrderType('ASC');
$offlineTagIds = $contentHandler->getListOfObjectTitles($criteria);

// Combine the list
$contentIds = $contentIds + $offlineTagIds;

// Generate the URLs using TFISH_URL as a base.
foreach ($contentIds as $key => $value) {
	$entry = '';
	$entry = TFISH_URL . '?id=' . $key;
	$sitemap .= $entry . '<br />';
	unset($entry);
}

// Display the output.
echo $sitemap;

// Footer.
require_once TFISH_PATH . "tfFooter.php";