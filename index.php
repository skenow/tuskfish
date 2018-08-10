<?php

/**
 * Tuskfish home page controller script.
 * 
 * Displays a single stream of mixed content (teasers), excluding tags and static content objects.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     content
 */
// Enable strict type declaration.
declare(strict_types=1);

// 1. Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";

// 2. Main Tuskfish header. This file bootstraps Tuskfish.
require_once TFISH_PATH . "tfHeader.php";

// 3. Content header sets module-specific paths and makes TfContentHandlerFactory available.
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php";

// Get a content handler.
$contentHandler = $contentHandlerFactory->getHandler('content');

// Specify theme, otherwise 'default' will be used.
$tfTemplate->setTheme('default');
$indexTemplate = 'singleStream';
$targetFileName = 'index';
$tfTemplate->targetFileName = $targetFileName;

// Validate input parameters.
$cleanId = (int) ($_GET['id'] ?? 0);
$cleanStart = (int) ($_GET['start'] ?? 0);
$cleanTag = (int) ($_GET['tagId'] ?? 0);

// Set cache parameters.
$basename = basename(__FILE__);
$cacheParameters = array('id' => $cleanId, 'start' => $cleanStart, 'tagId' => $cleanTag);

$rssUrl = !empty($cleanTag) ? TFISH_RSS_URL . '?tagId=' . $cleanTag : TFISH_RSS_URL;

// Retrieve a single object if an ID is set.
if ($cleanId) {

    // Retrieve target object.
    $content = $contentHandler->getObject($cleanId);

    if (is_object($content) && $content->online && $content->type !== 'TfBlock') {

        // Update view counter and assign object to template. Only increment counter for
        // non-downloadable objects.
        if ($content->type != 'TfDownload' && !($content->type === 'TfCollection' 
                && $content->media)) {
            $content->counter += 1;
            $contentHandler->updateCounter($cleanId);
        }

        // Check if cached page is available.
        $tfCache->getCachedPage($basename, $cacheParameters);

        // Assign content object to template.
        $tfTemplate->content = $content;

        // Prepare meta information for display.
        $contentInfo = array();
        
        if ($content->creator) $contentInfo[] = $content->escapeForXss('creator');
        
        if ($content->date) $contentInfo[] = $content->escapeForXss('date');
        
        if ($content->counter) {
            switch ($content->type) {
                case "TfDownload":
                    $contentInfo[] = $content->escapeForXss('counter') . ' ' . TFISH_DOWNLOADS;
                    break;
                default:
                    $contentInfo[] = $content->escapeForXss('counter') . ' ' . TFISH_VIEWS;
            }
        }
        
        if ($content->format) $contentInfo[] = '.' . $content->escapeForXss('format');
        
        if ($content->fileSize) $contentInfo[] = $content->escapeForXss('fileSize');
        
        // For a content type-specific page use $content->tags, $content->template
        if ($content->tags) {
            $tags = $contentHandler->makeTagLinks($content->tags);
            $tags = TFISH_TAGS . ': ' . implode(', ', $tags);
            $contentInfo[] = $tags;
        }
        
        $tfTemplate->contentInfo = implode(' | ', $contentInfo);
        
        if ($content->metaTitle) $tfMetadata->setTitle($content->metaTitle);
        
        if ($content->metaDescription) $tfMetadata->setDescription($content->metaDescription);

        // Check if has a parental object; if so display a thumbnail and teaser / link.
        if (!empty($content->parent)) {
            $parent = $contentHandler->getObject($content->parent);
            
            if (is_object($parent) && $parent->online) {
                $tfTemplate->parent = $parent;
            }
        }

        // Initialise criteria object.
        $criteria = $tfCriteriaFactory->getCriteria();
        $criteria->setOrder('date');
        $criteria->setOrderType('DESC');
        $criteria->setSecondaryOrder('submissionTime');
        $criteria->setSecondaryOrderType('DESC');

        // If object is a collection check if has child objects; if so display teasers / links.
        if ($content->type === 'TfCollection') {
            $criteria->add(new TfCriteriaItem($tfValidator, 'parent', $content->id));
            $criteria->add(new TfCriteriaItem($tfValidator, 'online', 1));
            
            if ($cleanStart) $criteria->setOffset($cleanStart);
            
            $criteria->setLimit($tfPreference->userPagination);
        }

        // If object is a tag, then a different method is required to call the related content.
        if ($content->type === 'TfTag') {
            if ($cleanStart) $criteria->setOffset($cleanStart);
            
            $criteria->setLimit($tfPreference->userPagination);
            $criteria->setTag(array($content->id));
            $criteria->add(new TfCriteriaItem($tfValidator, 'type', 'TfBlock', '!='));
            $criteria->add(new TfCriteriaItem($tfValidator, 'online', 1));
        }

        // Prepare pagination control.        
        if ($content->type === 'TfCollection' || $content->type === 'TfTag') {
            $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
            $tfPagination->setUrl($targetFileName);
            $tfPagination->setCount($contentHandler->getCount($criteria));
            $tfPagination->setLimit($tfPreference->userPagination);
            $tfPagination->setStart($cleanStart);
            $tfPagination->setTag($cleanTag);
            $tfPagination->setExtraParams(array('id' => $cleanId));
            $tfTemplate->collectionPagination = $tfPagination->getPaginationControl();

            // Retrieve content objects and assign to template.
            $firstChildren = $contentHandler->getObjects($criteria);
            
            if (!empty($firstChildren)) {
                $tfTemplate->firstChildren = $firstChildren;
            }
        }

        // Render template.
        $tfTemplate->tfMainContent = $tfTemplate->render($content->template);
    } else {
        $tfTemplate->tfMainContent = TFISH_ERROR_NO_SUCH_CONTENT;
    }
// Otherwise retrieve an index page list of teasers.
} else {

    // Check if cached page is available.
    $tfCache->getCachedPage($basename, $cacheParameters);

    // Page title, customise it as you see fit.
    $tfTemplate->pageTitle = TFISH_LATEST_POSTS;

    // Exclude static pages, tags and blocks from the index page.
    $criteria = $tfCriteriaFactory->getCriteria();
    
    if ($cleanStart) $criteria->setOffset($cleanStart);
    
    $criteria->setLimit($tfPreference->userPagination);
    
    if ($cleanTag) $criteria->setTag(array($cleanTag));
    
    $criteria->add(new TfCriteriaItem($tfValidator, 'type', 'TfTag', '!='));
    $criteria->add(new TfCriteriaItem($tfValidator, 'type', 'TfStatic', '!='));
    $criteria->add(new TfCriteriaItem($tfValidator, 'type', 'TfBlock', '!='));
    $criteria->add(new TfCriteriaItem($tfValidator, 'online', 1));

    // Prepare pagination control.
    $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
    $tfPagination->setCount($contentHandler->getCount($criteria));
    $tfPagination->setLimit($tfPreference->userPagination);
    $tfPagination->setStart($cleanStart);
    $tfPagination->setTag($cleanTag);
    $tfTemplate->pagination = $tfPagination->getPaginationControl();
    
    // Retrieve content objects and assign to template.
    $criteria->setOrder('date');
    $criteria->setOrderType('DESC');
    $criteria->setSecondaryOrder('submissionTime');
    $criteria->setSecondaryOrderType('DESC');
    $contentObjects = $contentHandler->getObjects($criteria);
    $tfTemplate->contentObjects = $contentObjects;
    $tfTemplate->tfMainContent = $tfTemplate->render($indexTemplate);

    // Prepare tag select box.
    $tfTemplate->selectAction = 'index.php';
    $tagHandler = $contentHandlerFactory->getHandler('tag');
    $tfTemplate->selectFilters = $tagHandler->getTagSelectBox($cleanTag);
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

// Include the main theme.html template and flush buffer
require_once TFISH_PATH . "tfFooter.php";
