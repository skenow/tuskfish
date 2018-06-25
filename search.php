<?php

/**
 * Global site search.
 * 
 * Free text search of all content objects with ALL OR EXACT options for search terms. The fields
 * searched are title, teaser, description, caption, creator, publisher.
 *
 * @copyright	Simon Wilkinson 2013-2017 Tuskfish CMS Project (https://tuskfish.biz)
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

// Specify theme set, otherwise 'default' will be used.
$tfish_template->setTheme('default');

// Specify the landing page that search results should point to. Default (blank) is index.php.
$tfish_template->target_file_name = '';

// Validate data and separate the search terms.
$clean_op = isset($_REQUEST['op']) ? TfishDataValidator::trimString($_REQUEST['op']) : false;

// Search terms passed in from a pagination control link, in which case it has been previously
// i) encoded and ii) escaped. This process needs to be reversed.
if (isset($_REQUEST['query'])) {
    $terms = TfishDataValidator::trimString($_REQUEST['query']);
    $terms = rawurldecode($terms);
    $clean_terms = htmlspecialchars_decode($terms, ENT_QUOTES);
} else { // Search terms entered directly into the search form.
    $clean_terms = isset($_REQUEST['search_terms'])
            ? TfishDataValidator::trimString($_REQUEST['search_terms']) : false;
}

$type = isset($_REQUEST['search_type']) ? TfishDataValidator::trimString($_REQUEST['search_type']) : false;
$start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;

// Proceed to search. Note that detailed validation of parameters is conducted by searchContent()
if ($clean_op && $clean_terms && $type) {
    $content_handler = new TfishContentHandler();
    $search_results = $content_handler->searchContent($tfish_preference, $clean_terms, $type,
            $tfish_preference->search_pagination, $start);
    
    if ($search_results && $search_results[0] > 0) {
        
        // Get a count of search results; this is used to build the pagination control.
        $results_count = (int) array_shift($search_results);
        $tfish_template->results_count = $results_count;
        $tfish_template->search_results = $search_results;

        // Prepare the pagination control, including parameters to be included in the link.
        $query_parameters = array(
            'op' => 'search',
            'search_type' => $type,
            'query' => $clean_terms);
        $tfish_template->pagination = $tfish_metadata->getPaginationControl($results_count,
                $tfish_preference->search_pagination, 'search', $start, 0, $query_parameters);
    } else {
        $tfish_template->search_results = false;
    }
}

// Assign template variables.
$tfish_template->page_title = TFISH_SEARCH;
$tfish_template->terms = $clean_terms;
$tfish_template->type = $type;
$tfish_template->form = TFISH_FORM_PATH . 'search.html';
$tfish_template->tfish_main_content = $tfish_template->render('form');

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
$tfish_metadata->title = TFISH_SEARCH;
$tfish_metadata->description = TFISH_SEARCH_DESCRIPTION;
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
// $tfish_metadata->robots = '';
// Include page template and flush buffer

require_once TFISH_PATH . "tfish_footer.php";
