<?php

/**
* Tuskfish search script
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
// Site preferences can be accessed via $tfish_preference->key;
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// Validate data and separate the search terms.
$clean_op = isset($_REQUEST['op']) ? TfishFilter::trimString($_REQUEST['op']) : false;
$terms = isset($_REQUEST['search_terms']) ? TfishFilter::trimString($_REQUEST['search_terms']) : false;
$type = isset($_REQUEST['search_type']) ? TfishFilter::trimString($_REQUEST['search_type']) : false;
$start = isset($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;

// Proceed to search. Note that detailed validation of parameters is conducted by searchContent()
if ($clean_op && $terms && $type) {
	$content_handler = new TfishContentHandler();
	//$results = $content_handler->searchContent($terms, $type, $tfish_preference->search_pagination, $start);
	if ($results) {
		echo TFISH_SEARCH_RESULTS_FOUND;
	} else {
		echo TFISH_SEARCH_NO_RESULTS;
	}
}

// Assign template variables.
$page_title = TFISH_SEARCH;
$tfish_form = TFISH_FORM_PATH . 'search.html';
// $pagination = $tfish_metadata->getPaginationControl($count, $tfish_preference->search_pagination, TFISH_URL);

/**
 * Override page template and metadata here (otherwise default site metadata will display).
 */
$pagetitle = TFISH_SEARCH;
$tfish_metadata->title = TFISH_SEARCH;
$tfish_metadata->description = TFISH_SEARCH_DESCRIPTION;
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
// $tfish_metadata->robots = '';
// $tfish_metadata->template = '';

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";