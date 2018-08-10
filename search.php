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
    $cleanTerms = htmlspecialchars_decode($terms, ENT_QUOTES);
} else { // Search terms entered directly into the search form.
    $cleanTerms = isset($_REQUEST['searchTerms'])
            ? $tfValidator->trimString($_REQUEST['searchTerms']) : false;
}

$searchType = isset($_REQUEST['searchType']) ? $tfValidator->trimString($_REQUEST['searchType']) : false;
$start = (int) ($_REQUEST['start'] ?? 0);

// Proceed to search. Note that detailed validation of parameters is conducted by searchContent()
if ($cleanOp && $cleanTerms && $searchType) {
    $searchEngine = new TfSearchContent($tfValidator, $tfDatabase, $tfPreference);
    $searchEngine->setOperator($searchType);
    $searchEngine->setSearchTerms($cleanTerms);
    $searchEngine->setOffset($start);
    $searchResults = $searchEngine->searchContent();

    if ($searchResults && $searchResults[0] > 0) {
        
        // Get a count of search results; this is used to build the pagination control.
        $resultsCount = (int) array_shift($searchResults);
        $tfTemplate->resultsCount = $resultsCount;
        $tfTemplate->searchResults = $searchResults;

        // Prepare the pagination control, including parameters to be included in the link.
        $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
        $tfPagination->setUrl('search');
        $tfPagination->setCount($resultsCount);
        $tfPagination->setLimit($tfPreference->searchPagination);
        $tfPagination->setStart($start);
        $tfPagination->setTag(0);
        $queryParameters = array(
            'op' => 'search',
            'searchType' => $searchType,
            'query' => $cleanTerms);
        $tfPagination->setExtraParams($queryParameters);
        $tfTemplate->pagination = $tfPagination->renderPaginationControl();
    } else {
        $tfTemplate->searchResults = false;
    }
}

// Assign template variables.
$tfTemplate->pageTitle = TFISH_SEARCH;
$tfTemplate->terms = $cleanTerms;
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
