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
require_once TFISH_PATH . "tfish_header.php";

// Get a generic handler.
$content_handler = new TfishContentHandler($tfish_validator, $tfish_file_handler);

/** 
 * Sitemap generation code.
 */

// Initialise.
$online_content_ids = array();
$offline_tag_ids = array();
$columns = array('id', 'seo');
$sitemap = '';

// Get the IDs of all online objects (and offline tags), but not blocks.
$criteria = new TfishCriteria($tfish_validator);
$criteria->add(new TfishCriteriaItem($tfish_validator, 'type', 'TfishBlock', '!='));
$criteria->add(new TfishCriteriaItem($tfish_validator, 'online', 1));
$criteria->setOrder('id');
$criteria->setOrderType('ASC');
$content_ids = TfishDatabase::select('content', $criteria, $columns);

// Need to do tags marked as offline, also, as these are not actually offline.
$criteria = new TfishCriteria($tfish_validator);
$criteria->add(new TfishCriteriaItem($tfish_validator, 'type', 'TfishTag'));
$criteria->add(new TfishCriteriaItem($tfish_validator, 'online', 0));
$criteria->setOrder('id');
$criteria->setOrderType('ASC');
$offline_tag_ids = $content_handler->getListOfObjectTitles($criteria);

// Combine the list
$content_ids = $content_ids + $offline_tag_ids;

// Generate the URLs using TFISH_URL as a base.
foreach ($content_ids as $value) {
	$entry = '';
	$entry = TFISH_URL . '?id=' . $value['id'];
	if ($value['seo']) {
		$entry .= '&amp;title=' . $value['seo'];
	}
	$sitemap .= $entry . '<br />';
	unset($entry);
}

// Display the output.
echo $sitemap;

// Footer.
require_once TFISH_PATH . "tfish_footer.php";