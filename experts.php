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
$cleanStart = (int) ($_REQUEST['start'] ?? 0);
$cleanTag = (int) ($_REQUEST['tagId'] ?? 0);
$cleanState = (int) ($_REQUEST['state'] ?? 0);

if (isset($_REQUEST['alpha']) && $tfValidator->isAlpha($_REQUEST['alpha'])) {
    $cleanAlpha = $tfValidator->trimString($_REQUEST['alpha']);
} else {
    $cleanAlpha = '';
}

// Search terms passed in from a pagination control link have been i) encoded and ii) escaped.
if (isset($_REQUEST['query'])) {
    $terms = $tfValidator->trimString($_REQUEST['query']);
    $terms = rawurldecode($terms);
    $cleanTerms = htmlspecialchars_decode($terms, ENT_QUOTES);
} else { // Search terms entered directly into the search form.
    $cleanTerms = isset($_REQUEST['searchTerms']) 
            ? $tfValidator->trimString($_REQUEST['searchTerms']) : false;
}

// Set cache parameters.
$basename = basename(__FILE__);
$cacheParameters = array('id' => $cleanId, 'start' => $cleanStart, 'tagId' => $cleanTag,
    'state' => $cleanState);

$expertHandler = $expertFactory->getExpertHandler();

// Retrieve a single expert based on ID.
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

// List experts alphabetically by last name.
if ($cleanAlpha) {
    $searchEngine = new TfSearchExpert($tfValidator, $tfDatabase, $expertFactory, $tfPreference);
    $searchEngine->setOffset($cleanStart);
    $searchResults = $searchEngine->searchAlphabetically($cleanAlpha);

    if ($searchResults && $searchResults[0] > 0) {
        $resultsCount = (int) array_shift($searchResults); // Needed for pagination control.
        $tfTemplate->resultsCount = $resultsCount;
        $tfTemplate->searchResults = $searchResults;
    } else {
        $tfTemplate->searchResults = false;
    }

    $queryParameters = array('alpha' => $cleanAlpha);
    $indexTemplate = 'experts';
}

// List experts by tag or country.
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
    
    $tfTemplate->resultsCount = $resultsCount;
    $tfTemplate->searchResults = $expertHandler->getObjects($criteria);

    if ($cleanState) {
        $queryParameters = array('state' => $cleanState);
    }
    
    $indexTemplate = 'experts';
}

// Free text search.
if ($cleanTerms) {
    $searchEngine = new TfSearchExpert($tfValidator, $tfDatabase, $expertFactory, $tfPreference);
    $searchEngine->setOperator('AND');
    $searchEngine->setSearchTerms($cleanTerms);
    $searchEngine->setOffset($cleanStart);
    $searchResults = $searchEngine->searchExperts();
    $tfTemplate->terms = $cleanTerms;

    if ($searchResults && $searchResults[0] > 0) {
        $resultsCount = (int) array_shift($searchResults);
        $tfTemplate->resultsCount = $resultsCount;
        $tfTemplate->searchResults = $searchResults;
    } else {
        $tfTemplate->searchResults = false;
    }

    $queryParameters = array('query' => $cleanTerms);
    $indexTemplate = 'experts';
}

// Unified pagination control.
$tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
$tfPagination->setUrl('experts');
$tfPagination->setCount($resultsCount);
$tfPagination->setLimit($tfPreference->searchPagination);
$tfPagination->setStart($cleanStart);
$tfPagination->setTag($cleanTag);

if (isset($queryParameters) && !empty($queryParameters)) {
    $tfPagination->setExtraParams($queryParameters);
}

$tfTemplate->experts_pagination = $tfPagination->renderPaginationControl();
        
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
