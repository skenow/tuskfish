<?php

/**
 * Global site search.
 * 
 * Free text search of all content objects with ALL OR EXACT options for search terms. The fields
 * searched are title, teaser, description, caption, creator, publisher.
 *
 * @copyright	Simon Wilkinson 2013+ Tuskfish CMS Project (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since		1.0
 * @package		content
 */
// Enable strict type declaration.
declare(strict_types=1);

// 1. Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";

// 2. Module header must precede Tuskfish header. This file sets module-specific paths.
require_once TFISH_MODULE_PATH . "content/tfish_content_header.php";

// 3. Main Tuskfish header. This file bootstraps Tuskfish.
require_once TFISH_PATH . "tfish_header.php";

// Specify theme set, otherwise 'default' will be used.
$tfish_template->setTheme('default');

// Specify the landing page that search results should point to. Default (blank) is index.php.
$tfish_template->target_file_name = '';

// Validate data and separate the search terms.
$clean_op = isset($_REQUEST['op']) ? $tfish_validator->trimString($_REQUEST['op']) : false;

// Search terms passed in from a pagination control link, in which case it has been previously
// i) encoded and ii) escaped. This process needs to be reversed.
if (isset($_REQUEST['query'])) {
    $terms = $tfish_validator->trimString($_REQUEST['query']);
    $terms = rawurldecode($terms);
    $clean_terms = htmlspecialchars_decode($terms, ENT_QUOTES);
} else { // Search terms entered directly into the search form.
    $clean_terms = isset($_REQUEST['search_terms'])
            ? $tfish_validator->trimString($_REQUEST['search_terms']) : false;
}

$search_type = isset($_REQUEST['search_type']) ? $tfish_validator->trimString($_REQUEST['search_type']) : false;
$start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;

// Proceed to search. Note that detailed validation of parameters is conducted by searchContent()
if ($clean_op && $clean_terms && $search_type) {
    $search_engine = new TfishSearchContent($tfish_validator, $tfish_database, $tfish_preference);
    $search_engine->setSearchTerms($clean_terms);
    $search_engine->setOperator($search_type);
    $search_engine->setOffset($start);
    $search_results = $search_engine->searchContent();

    if ($search_results && $search_results[0] > 0) {
        
        // Get a count of search results; this is used to build the pagination control.
        $results_count = (int) array_shift($search_results);
        $tfish_template->results_count = $results_count;
        $tfish_template->search_results = $search_results;

        // Prepare the pagination control, including parameters to be included in the link.
        $tfish_pagination = new TfishPaginationControl($tfish_validator, $tfish_preference);
        $tfish_pagination->setUrl('search');
        $tfish_pagination->setCount($results_count);
        $tfish_pagination->setLimit($tfish_preference->search_pagination);
        $tfish_pagination->setStart($start);
        $tfish_pagination->setTag(0);
        $query_parameters = array(
            'op' => 'search',
            'search_type' => $search_type,
            'query' => $clean_terms);
        $tfish_pagination->setExtraParams($query_parameters);
        $tfish_template->pagination = $tfish_pagination->getPaginationControl();
    } else {
        $tfish_template->search_results = false;
    }
}

// Assign template variables.
$tfish_template->page_title = TFISH_SEARCH;
$tfish_template->terms = $clean_terms;
$tfish_template->type = $search_type;
$tfish_template->form = TFISH_CONTENT_MODULE_FORM_PATH . 'search.html';
$tfish_template->tfish_main_content = $tfish_template->render('form');

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
$tfish_metadata->setTitle(TFISH_SEARCH);
$tfish_metadata->setDescription(TFISH_SEARCH_DESCRIPTION);
// $tfish_metadata->setAuthor('');
// $tfish_metadata->setCopyright('');
// $tfish_metadata->setGenerator('');
// $tfish_metadata->setSeo('');
// $tfish_metadata->setRobots('');

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";
