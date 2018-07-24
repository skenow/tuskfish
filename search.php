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

// 2. Main Tuskfish header. This file bootstraps Tuskfish.
require_once TFISH_PATH . "tfHeader.php";

// 3. Content header sets module-specific paths and makes TfContentHandlerFactory available.
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php";

// Specify theme set, otherwise 'default' will be used.
$tfTemplate->setTheme('default');

// Specify the landing page that search results should point to. Default (blank) is index.php.
$tfTemplate->targetFileName = '';

// Validate data and separate the search terms.
$cleanOp = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : false;

// Search terms passed in from a pagination control link, in which case it has been previously
// i) encoded and ii) escaped. This process needs to be reversed.
if (isset($_REQUEST['query'])) {
    $terms = $tfValidator->trimString($_REQUEST['query']);
    $terms = rawurldecode($terms);
    $clean_terms = htmlspecialchars_decode($terms, ENT_QUOTES);
} else { // Search terms entered directly into the search form.
    $clean_terms = isset($_REQUEST['search_terms'])
            ? $tfValidator->trimString($_REQUEST['search_terms']) : false;
}

$searchType = isset($_REQUEST['searchType']) ? $tfValidator->trimString($_REQUEST['searchType']) : false;
$start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;

// Proceed to search. Note that detailed validation of parameters is conducted by searchContent()
if ($cleanOp && $clean_terms && $searchType) {
    $search_engine = new TfSearchContent($tfValidator, $tfDatabase, $tfPreference);
    $search_engine->setSearchTerms($clean_terms);
    $search_engine->setOperator($searchType);
    $search_engine->setOffset($start);
    $search_results = $search_engine->searchContent();

    if ($search_results && $search_results[0] > 0) {
        
        // Get a count of search results; this is used to build the pagination control.
        $results_count = (int) array_shift($search_results);
        $tfTemplate->results_count = $results_count;
        $tfTemplate->search_results = $search_results;

        // Prepare the pagination control, including parameters to be included in the link.
        $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
        $tfPagination->setUrl('search');
        $tfPagination->setCount($results_count);
        $tfPagination->setLimit($tfPreference->searchPagination);
        $tfPagination->setStart($start);
        $tfPagination->setTag(0);
        $query_parameters = array(
            'op' => 'search',
            'searchType' => $searchType,
            'query' => $clean_terms);
        $tfPagination->setExtraParams($query_parameters);
        $tfTemplate->pagination = $tfPagination->getPaginationControl();
    } else {
        $tfTemplate->search_results = false;
    }
}

// Assign template variables.
$tfTemplate->pageTitle = TFISH_SEARCH;
$tfTemplate->terms = $clean_terms;
$tfTemplate->type = $searchType;
$tfTemplate->form = TFISH_CONTENT_MODULE_FORM_PATH . 'search.html';
$tfTemplate->tfMainContent = $tfTemplate->render('form');

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
$tfMetadata->setTitle(TFISH_SEARCH);
$tfMetadata->setDescription(TFISH_SEARCH_DESCRIPTION);
// $tfMetadata->setAuthor('');
// $tfMetadata->setCopyright('');
// $tfMetadata->setGenerator('');
// $tfMetadata->setSeo('');
// $tfMetadata->setRobots('');

// Include page template and flush buffer
require_once TFISH_PATH . "tfFooter.php";
