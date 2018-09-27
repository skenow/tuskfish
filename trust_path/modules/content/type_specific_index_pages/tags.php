<?php

/**
 * Tag index page.
 * 
 * User-facing controller script for presenting a list of tags in teaser format.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */
// Enable strict type declaration.
declare(strict_types=1);

// 1. Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";

// 2. Main Tuskfish header. This file bootstraps Tuskfish.
require_once TFISH_PATH . "tfHeader.php";

// 3. Content header sets module-specific paths and makes TfContentHandler available.
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php";

// Configure page.
$tfTemplate->pageTitle = TFISH_TYPE_TAGS;
$contentHandler = $contentFactory->getContentHandler('tag');
$indexTemplate = 'tags';
$targetFileName = 'tags';
$tfTemplate->targetFileName = $targetFileName;
// $tfTemplate->setTheme('jumbotron');

// Validate input parameters.
$cleanId = (int) ($_GET['id'] ?? 0);
$cleanStart = (int) ($_GET['start'] ?? 0);
$cleanTag = (int) ($_GET['tagId'] ?? 0);

// Set cache parameters.
$basename = basename(__FILE__);
$cacheParameters = array('id' => $cleanId, 'start' => $cleanStart, 'tagId' => $cleanTag);

// View single object description.
if ($cleanId) {
    $content = $contentHandler->getObject($cleanId);
    
    if (is_object($content) && $content->online) {
        // Update view counter and assign object to template.
        $content->setCounter($content->counter + 1);
        $contentHandler->updateCounter($cleanId);
        
        // Check if cached page is available.
        $tfCache->getCachedPage($basename, $cacheParameters);
        
        // Assign content to template.
        $tfTemplate->content = $content;

        // Prepare meta information for display.
        if ($content->metaTitle)
            $tfMetadata->setTitle($content->metaTitle);
        
        if ($content->metaDescription)
            $tfMetadata->setDescription($content->metaDescription);

        // Render template.
        $tfTemplate->tfMainContent = $tfTemplate->render($content->template);
    } else {
        $tfTemplate->tfMainContent = TFISH_ERROR_NO_SUCH_CONTENT;
    }

// View index page of multiple objects (teasers).
} else {
    // Check if cached page is available.
    $tfCache->getCachedPage($basename, $cacheParameters);
    
    // Set criteria for selecting content objects.
    $criteria = $tfCriteriaFactory->getCriteria();
    
    if ($cleanStart)
        $criteria->setOffset($cleanStart);
    
    $criteria->setLimit($tfPreference->userPagination);
    $criteria->add($tfCriteriaFactory->getItem('online', 1));

    // Prepare pagination control.
    $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
    $tfPagination->setUrl($targetFileName);
    $tfPagination->setCount($contentHandler->getCount($criteria));
    $tfPagination->setLimit($tfPreference->userPagination);
    $tfPagination->setStart($cleanStart);
    $tfTemplate->pagination = $tfPagination->renderPaginationControl();

    // Retrieve content objects and assign to template.
    $contentObjects = $contentHandler->getObjects($criteria);
    $tfTemplate->contentObjects = $contentObjects;
    $tfTemplate->tfMainContent = $tfTemplate->render($indexTemplate);
}

/**
 * Override page template and metadata here (otherwise default site metadata will display).
 */
// $tfMetadata->setTitle('');
// $tfMetadata->setDescription('');
// $tfMetadata->setAuthor('');
// $tfMetadata->setCopyright('');
// $tfMetadata->setGenerator('');
// $tfMetadata->setSeo('');
// $tfMetadata->setRobots('');
 
// Include page template and flush buffer
require_once TFISH_PATH . "tfFooter.php";
