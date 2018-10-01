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

// Set target file for intra-collection pagination controls when viewing objects.
$targetFileName = 'experts';
$tfTemplate->targetFileName = $targetFileName;
$indexTemplate = 'experts';

// Validate input parameters.
$cleanId = (int) ($_REQUEST['id'] ?? 0);
$cleanStart = (int) ($_GET['start'] ?? 0);
$cleanTag = (int) ($_GET['tagId'] ?? 0);
$cleanState = (int) ($_GET['state'] ?? 0);
$cleanOp = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : '';

// Set cache parameters.
$basename = basename(__FILE__);
$cacheParameters = array('id' => $cleanId, 'start' => $cleanStart, 'tagId' => $cleanTag,
    'state' => $cleanState);

$expertHandler = $expertFactory->getExpertHandler();

// If ID is set, get single expert.
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
        $indexTemplate = 'expert';
    }
}

if (($cleanTag || $cleanState)) {
    $criteria = $tfCriteriaFactory->getCriteria();
    if ($cleanStart) $criteria->setOffset($cleanStart);
    if ($cleanState) $criteria->add($tfCriteriaFactory->getItem('country', $cleanState));
    if ($cleanTag) $criteria->setTag(array($cleanTag));
    $criteria->add($tfCriteriaFactory->getItem('online', 1));
    $resultsCount = $tfDatabase->selectCount('expert', $criteria);
    $criteria->setLimit($tfPreference->userPagination);
    $criteria->setOrder('lastName');
    $criteria->setOrderType('ASC');
    
    $tfTemplate->searchResults = $expertHandler->getObjects($criteria);
    $tfTemplate->resultsCount = $resultsCount;
    $indexTemplate = 'experts';
    
    // Pagination control.
    $extraParams = array();

    if (isset($cleanState) && !empty($cleanState)) {
        $extraParams['state'] = $cleanState;
    }

    $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
    $tfPagination->setUrl('experts');
    $tfPagination->setCount($resultsCount);
    $tfPagination->setLimit($tfPreference->userPagination);
    $tfPagination->setStart($cleanStart);
    $tfPagination->setTag($cleanTag);
    $tfPagination->setExtraParams($extraParams);
    $tfTemplate->experts_pagination = $tfPagination->renderPaginationControl();
}

// If search terms were submitted, return matching experts.
/*if (isset($_REQUEST['query']) || $_REQUEST['searchTerms']) {
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

    $start = (int) ($_REQUEST['start'] ?? 0);
    $tfTemplate->terms = $cleanTerms;

    // Proceed to search. Note that detailed validation of parameters is conducted by searchContent()
    if ($cleanTerms) {
        $searchEngine = new TfSearchExpert($tfValidator, $tfDatabase, $expertFactory, $tfPreference);
        $searchEngine->setOperator('AND');
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
            $tfPagination->setUrl('experts');
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
        
        $indexTemplate = 'experts';
    }
}*/

// Select filters.
$tagHandler = $contentFactory->getContentHandler('tag');
$tfTemplate->tagSelect = $tagHandler->getTagSelectBox($cleanTag, 'experts');
$tfTemplate->countrySelect = $expertHandler->getCountrySelectBox($cleanState, TFISH_EXPERTS_SELECT_STATE);
$tfTemplate->selectAction = 'experts.php';

// Assign template variables.
$tfTemplate->pageTitle = TFISH_EXPERTS;
$tfTemplate->tfMainContent = $tfTemplate->render($indexTemplate);

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
$tfMetadata->setTitle(TFISH_EXPERTS);
$tfMetadata->setDescription(TFISH_EXPERTS_DESCRIPTION);

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
