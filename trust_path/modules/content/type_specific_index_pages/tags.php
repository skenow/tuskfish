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

// 3. Content header sets module-specific paths and makes TfContentHandlerFactory available.
require_once TFISH_MODULE_PATH . "content/tf_content_header.php";

// Configure page.
$tfTemplate->pageTitle = TFISH_TYPE_TAGS;
$contentHandler = $contentHandlerFactory->getHandler('content');
$index_template = 'tags';
$targetFileName = 'tags';
$tfTemplate->targetFileName = $targetFileName;
// $tfTemplate->setTheme('jumbotron');

// Validate input parameters.
$cleanId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tagId']) ? (int) $_GET['tagId'] : 0;

// Set cache parameters.
$basename = basename(__FILE__);
$cache_parameters = array('id' => $cleanId, 'start' => $clean_start, 'tagId' => $clean_tag);

// View single object description.
if ($cleanId) {
    $content = $contentHandler->getObject($cleanId);
    
    if (is_object($content) && $content->online) {
        // Update view counter and assign object to template.
        $content->counter += 1;
        $contentHandler->updateCounter($cleanId);
        
        // Check if cached page is available.
        $tfCache->getCachedPage($basename, $cache_parameters);
        
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
    $tfCache->getCachedPage($basename, $cache_parameters);
    
    // Set criteria for selecting content objects.
    $criteria = $tfCriteriaFactory->getCriteria();
    
    if ($clean_start)
        $criteria->setOffset($clean_start);
    
    $criteria->setLimit($tfPreference->userPagination);
    $criteria->add(new TfCriteriaItem($tfValidator, 'online', 1));

    // Prepare pagination control.
    $tf_pagination = new TfPaginationControl($tfValidator, $tfPreference);
    $tf_pagination->setUrl($targetFileName);
    $tf_pagination->setCount($contentHandler->getCount($criteria));
    $tf_pagination->setLimit($tfPreference->userPagination);
    $tf_pagination->setStart($clean_start);
    $tfTemplate->pagination = $tf_pagination->getPaginationControl();

    // Retrieve content objects and assign to template.
    $contentObjects = $contentHandler->getObjects($criteria);
    $tfTemplate->contentObjects = $contentObjects;
    $tfTemplate->tfMainContent = $tfTemplate->render($index_template);
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
