<?php

/**
 * Front end controller script for the Experts module.
 * 
 * @copyright   Simon Wilkinson 2018+ (https://isengard.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     experts
 */
// Enable strict type declaration.
declare(strict_types=1);

// Boot! Set file paths, preferences and connect to database.
require_once "mainfile.php";
require_once TFISH_PATH . "tfHeader.php";
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php";
require_once TFISH_MODULE_PATH . "experts/tfExpertsHeader.php";

// Specify the theme you want to use.
$tfTemplate->setTheme('default');
$indexTemplate = 'experts';

// Set target file for intra-collection pagination controls when viewing objects.
$targetFileName = 'experts';
$tfTemplate->targetFileName = $targetFileName;

// Validate input parameters.
$cleanId = (int) ($_REQUEST['id'] ?? 0);
$cleanStart = (int) ($_GET['start'] ?? 0);
$cleanTag = (int) ($_GET['tagId'] ?? 0);
$cleanState = (int) ($_GET['state'] ?? 0);

// Set cache parameters.
$basename = basename(__FILE__);
$cacheParameters = array('id' => $cleanId, 'start' => $cleanStart, 'tagId' => $cleanTag,
    'state' => $cleanState);

$expertHandler = $expertFactory->getExpertHandler();

// Get single expert.
if ($cleanId) {
    $expert = $expertHandler->getObject($cleanId);

    if (isset($expert) && is_a($expert, 'TfExpert') && $expert->online) {
        $expertHandler->updateCounter($cleanId);
        $tfCache->getCachedPage($basename, $cacheParameters);
        $tfTemplate->expert = $expert;
        
        $expertInfo = array();

        if ($expert->tags) {
            $tags = $expertHandler->makeTagLinks($expert->tags, 'experts');
            $tags = TFISH_TAGS . ': ' . implode(', ', $tags);
            $expertInfo[] = $tags;
        }

        $tfTemplate->expertInfo = implode(' | ', $expertInfo);
        if ($expert->metaTitle) $tfMetadata->setTitle($expert->metaTitle);
        if ($expert->metaDescription) $tfMetadata->setDescription($expert->metaDescription);
        $tfTemplate->tfMainContent = $tfTemplate->render($expert->template);
    }
} else {
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
        $searchEngine = new TfSearchExpert($tfValidator, $tfDatabase, $expertFactory, $tfPreference);
        $searchEngine->setOperator($searchType);
        $searchEngine->setSearchTerms($cleanTerms);
        $searchEngine->setOffset($start);
        $searchResults = $searchEngine->searchExperts();

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

    // Get list of experts.
    /**$criteria = $tfCriteriaFactory->getCriteria();
    if ($cleanStart) $criteria->setOffset($cleanStart);
    if ($cleanState) $criteria->add($tfCriteriaFactory->getItem('country', $cleanState));
    if ($cleanTag) $criteria->setTag(array($cleanTag));
    $criteria->add($tfCriteriaFactory->getItem('online', 1));
    $criteria->setLimit($tfPreference->userPagination);
    $criteria->setOrder('lastName');
    $criteria->setOrderType('ASC');
    
    $expertList = $expertHandler->getObjects($criteria);
    
    // Select filters.
    $tagHandler = $contentFactory->getContentHandler('tag');
    $tfTemplate->tagSelect = $tagHandler->getTagSelectBox($cleanTag, 'experts');

    // Country select filter.
    $tfTemplate->countrySelect = $expertHandler->getCountrySelectBox($cleanState, TFISH_EXPERTS_SELECT_STATE);

    $tfTemplate->selectAction = 'experts.php';
    $tfTemplate->selectFiltersForm = $tfTemplate->render('expertFilters');

    // Pagination control.
    $extraParams = array();
    if (isset($cleanOnline) && $tfValidator->isInt($cleanOnline, 0, 1)) {
        $extraParams['online'] = $cleanOnline;
    }
    
    if (isset($cleanState) && !empty($cleanState)) {
        $extraParams['state'] = $cleanState;
    }
    
    $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
    $tfPagination->setUrl('experts');
    $tfPagination->setCount($tfDatabase->selectCount('expert', $criteria));
    $tfPagination->setLimit($tfPreference->userPagination);
    $tfPagination->setStart($cleanStart);
    $tfPagination->setTag($cleanTag);
    $tfPagination->setExtraParams($extraParams);
    $tfTemplate->pagination = $tfPagination->renderPaginationControl();
    
    // Render index template.
    $tfTemplate->pageTitle = TFISH_EXPERTS;
    $tfTemplate->expertList = $expertList;
    $tfTemplate->tfMainContent = $tfTemplate->render($indexTemplate);*/
    
    // Assign template variables.
    $tfTemplate->pageTitle = TFISH_EXPERTS;
    $tfTemplate->terms = $cleanTerms;
    $tfTemplate->type = $searchType;
    $tfTemplate->form = TFISH_EXPERTS_MODULE_FORM_PATH . 'search.html';
    $tfTemplate->tfMainContent = $tfTemplate->render('form');

    /**
     * Override page metadata here (otherwise default site metadata will display).
     */
    $tfMetadata->setTitle(TFISH_EXPERTS);
    $tfMetadata->setDescription(TFISH_EXPERTS_DESCRIPTION);
}

/**
 * Override page template here (otherwise default site metadata will display).
 */
// $tfMetadata->setTitle('');
// $tfMetadata->setDescription('');
// $tfMetadata->setAuthor('');
// $tfMetadata->setCopyright('');
// $tfMetadata->setGenerator('');
// $tfMetadata->setSeo('');
$tfMetadata->setRobots('noindex,nofollow');

// Include page template and flush buffer
require_once TFISH_PATH . "tfFooter.php";
