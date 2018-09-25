<?php

/**
 * Video index page script.
 *
 * User-facing controller script for presenting a list of video content in teaser format.
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
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php";

// Lock handler to videos.
$contentHandler = $contentHandlerFactory->getHandler('content');
$criteria = $tfCriteriaFactory->getCriteria();
$criteria->add($tfCriteriaFactory->getItem('type', 'TfVideo'));

// Configure page.
$tfTemplate->pageTitle = TFISH_TYPE_VIDEOS;
$indexTemplate = 'videos';
$targetFileName = 'videos';
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
        $contentInfo = array();
        
        if ($content->creator)
            $contentInfo[] = $content->getCounter();
        
        if ($content->date)
            $contentInfo[] = $content->getDate();
        
        if ($content->counter)
            $contentInfo[] = $content->getCounter() . ' ' . TFISH_VIEWS;
        
        if ($content->format)
            $contentInfo[] = '.' . $content->getFormat();
        
        if ($content->fileSize)
            $contentInfo[] = $content->getFileSize();
        
        // For a content type-specific page use $content->tags, $content->template.
        if ($content->tags) {
            $tags = $contentHandler->makeTagLinks($content->tags, $targetFileName);
            $tags = TFISH_TAGS . ': ' . implode(', ', $tags);
            $contentInfo[] = $tags;
        }
        $tfTemplate->contentInfo = implode(' | ', $contentInfo);
        if ($content->metaTitle)
            $tfMetadata->setTitle($content->metaTitle);
        
        if ($content->metaDescription)
            $tfMetadata->setDescription($content->metaDescription);

        // Check if has a parental object; if so display a thumbnail and teaser / link.
        if (!empty($content->parent)) {
            $parent = $contentHandler->getObject($content->parent);
            
            if (is_object($parent) && $parent->online) {
                $tfTemplate->parent = $parent;
            }
        }

        // Render template.
        $tfTemplate->tfMainContent = $tfTemplate->render($content->template);
    } else {
        $tfTemplate->tfMainContent = TFISH_ERROR_NO_SUCH_CONTENT;
    }

// View index page of multiple objects (teasers).
} else {
    // Check if cached page is available.
    $tfCache->getCachedPage($basename, $cacheParameters);
    
    if ($cleanStart)
        $criteria->setOffset($cleanStart);
    
    $criteria->setLimit($tfPreference->userPagination);
    
    if ($cleanTag)
        $criteria->setTag(array($cleanTag));

    // Prepare pagination control.
    $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
    $tfPagination->setUrl($targetFileName);
    $tfPagination->setCount($contentHandler->getCount($criteria));
    $tfPagination->setLimit($tfPreference->userPagination);
    $tfPagination->setStart($cleanStart);
    $tfPagination->setTag($cleanTag);
    $tfTemplate->pagination = $tfPagination->renderPaginationControl();

    // Retrieve content objects and assign to template.
    $contentObjects = $contentHandler->getObjects($criteria);
    $tfTemplate->contentObjects = $contentObjects;
    $tfTemplate->tfMainContent = $tfTemplate->render($indexTemplate);
    
    // Prepare tag select box.
    $tfTemplate->selectAction = $targetFileName . '.php';
    $tagHandler = $contentHandlerFactory->getHandler('tag');
    $tfTemplate->selectFilters = $tagHandler->getTagSelectBox($cleanTag, 'TfVideo');
    $tfTemplate->selectFiltersForm = $tfTemplate->render('selectFilters');
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
