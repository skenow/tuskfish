<?php

/**
* Generates a sitemap for Tuskfish CMS base node IDs. User-facing, online pages only.
* If you have created custom static pages, you need to add them manually, and also delete the
* automatically generated entry for that base ID.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
*/

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// Get a generic handler.
$content_handler = 'TfishContentHandler';

/**
 * Sitemap generation code.
 */

// Initialise.
$online_content_ids = array();
$offline_tag_ids = array();
$columns = array('id', 'seo');
$sitemap = '';

// Get the IDs of all online objects (and offline tags), but not blocks.
$criteria = new TfishCriteria();
$criteria->add(new TfishCriteriaItem('type', 'TfishBlock', '!='));
$criteria->add(new TfishCriteriaItem('online', 1));
$criteria->order = 'id';
$criteria->ordertype = 'ASC';
$content_ids = TfishDatabase::select('content', $criteria, $columns);

// Need to do tags marked as offline, also, as these are not actually offline.
$criteria = new TfishCriteria();
$criteria->add(new TfishCriteriaItem('type', 'TfishTag'));
$criteria->add(new TfishCriteriaItem('online', 0));
$criteria->order = 'id';
$criteria->ordertype = 'ASC';
$offline_tag_ids = $content_handler::getList($criteria);

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