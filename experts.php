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
$cacheParameters = array('id' => $cleanId, 'start' => $cleanStart, 'tagId' => $cleanTag);

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
            $tags = $expertHandler->makeTagLinks($expert->tags);
            $tags = TFISH_TAGS . ': ' . implode(', ', $tags);
            $expertInfo[] = $tags;
        }

        $tfTemplate->expertInfo = implode(' | ', $expertInfo);
        if ($expert->metaTitle) $tfMetadata->setTitle($expert->metaTitle);
        if ($expert->metaDescription) $tfMetadata->setDescription($expert->metaDescription);
        $tfTemplate->tfMainContent = $tfTemplate->render($expert->template);
    }
} else {
    // Get list of experts.
    $criteria = $tfCriteriaFactory->getCriteria();
    if ($cleanStart) $criteria->setOffset($cleanStart);
    if ($cleanState) $criteria->add($tfCriteriaFactory->getItem('country', $cleanState));
    if ($cleanTag) $criteria->setTag(array($cleanTag));
    $criteria->add($tfCriteriaFactory->getItem('online', 1));
    $criteria->setLimit($tfPreference->userPagination);
    $criteria->setOrder('lastName');
    $criteria->setOrderType('ASC');
    
    $expertList = $expertHandler->getObjects($criteria);
    
    // Select filters.
    $tagHandler = $contentHandlerFactory->getHandler('tag');
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
        $extraParams['country'] = $cleanState;
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
    $tfTemplate->tfMainContent = $tfTemplate->render($indexTemplate);
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
