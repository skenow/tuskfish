<?php

/**
 * Static page template script.
 * 
 * User-facing controller script for presenting a single static content page. Simply make a copy of
 * this file with whatever name you want and set the id of the content you want to display in the
 * configuration section. 
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

// Lock handler to static pages.
$contentHandler = $contentHandlerFactory->getHandler('content');
$criteria = $tfCriteriaFactory->getCriteria();
$criteria->add(new TfCriteriaItem($tfValidator, 'type', 'TfStatic'));

////////// CONFIGURATION //////////
// 1. Enter the ID of the content object you want to display on this page.
$id = 10;

// 2. Enter the name of the page you want headings and tags to link back to, without extension.
$targetFileName = 'index';
$tfTemplate->targetFileName = $targetFileName;

// 3. Set the page title.
$tfTemplate->pageTitle = TFISH_TYPE_STATIC_PAGES;

// 4. Specify theme set, otherwise 'default' will be used.
// $tfTemplate->setTheme('jumbotron');
////////// END CONFIGURATION //////////

// Validate input parameters.
$cleanId = (int) $id;
$cleanStart = (int) ($_GET['start'] ?? 0);
$cleanTag = (int) ($_GET['tagId'] ?? 0);

// Set cache parameters.
$basename = basename(__FILE__);
$cacheParameters = array('id' => $cleanId, 'start' => $cleanStart, 'tagId' => $cleanTag);

if ($cleanId) {
    
    $content = $contentHandler->getObject($cleanId);
    
    if (is_object($content) && $content->online) {
        // Update view counter and assign object to template.
        $content->counter += 1;
        $contentHandler->updateCounter($cleanId);
        
        // Check if cached page is available.
        $tfCache->getCachedPage($basename, $cacheParameters);
        
        $tfTemplate->content = $content;

        // Prepare meta information for display.
        $contentInfo = array();
        
        if ($content->creator)
            $contentInfo[] = $content->escapeForXss('creator');
        
        if ($content->date)
            $contentInfo[] = $content->escapeForXss('date');
        
        if ($content->counter)
            $contentInfo[] = $content->escapeForXss('counter') . ' ' . TFISH_VIEWS;
        
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
            $tfPagination->setTag(0);
            $tfPagination->setExtraParams(array('id' => $cleanId));
            $tfTemplate->collectionPagination = $tfPagination->renderPaginationControl();

            // Retrieve content objects and assign to template.
            $firstChildren = $contentHandler->getObjects($criteria);
            
            if (!empty($firstChildren)) {
                $tfTemplate->firstChildren = $firstChildren;
            }
        }

        // Render template.
        $tfTemplate->tfMainContent = $tfTemplate->render($content->template);
    } else {
        $tfTemplate->tfMainContent = TFISH_ERROR_NEED_TO_CONFIGURE_STATIC_PAGE;
    }
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
