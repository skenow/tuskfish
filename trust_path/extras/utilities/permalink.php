<?php

/**
 * Permalink script.
 * 
 * Provides a permalink lookup service for all content objects. Simply supply the ID of the content.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */
// Enable strict type declaration.
declare(strict_types=1);

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfHeader.php";
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php";

// Specify theme, otherwise 'default' will be used.
$tfTemplate->setTheme('default');

// Configure page.
$tfTemplate->pageTitle = TFISH_TYPE_PERMALINKS;
$contentHandler = $contentHandlerFactory->getHandler('content');
$targetFileName = 'permalink';
$tfTemplate->targetFileName = $targetFileName;

// Validate input parameters.
$cleanId = (int) ($_GET['id'] ?? 0);
$cleanStart = (int) ($_GET['start'] ?? 0);

// View single object description.
if ($cleanId) {
    $content = $contentHandler->getObject($cleanId);
    if (is_object($content) && $content->online) {

        // Update view counter and assign object to template. Only increment counter for non-downloadable objects.
        if ($content->type != 'TfDownload') {
            $content->counter += 1;
            $contentHandler->updateCounter($cleanId);
        }
        $tfTemplate->content = $content;

        // Prepare meta information for display.
        $contentInfo = array();
        if ($content->creator)
            $contentInfo[] = $content->escapeForXss('creator');
        if ($content->date)
            $contentInfo[] = $content->escapeForXss('date');
        if ($content->counter)
            $contentInfo[] = $content->escapeForXss('counter') . ' ' . TFISH_VIEWS;
        if ($content->format)
            $contentInfo[] = '.' . $content->escapeForXss('format');
        if ($content->fileSize)
            $contentInfo[] = $content->escapeForXss('fileSize');
        if ($content->tags) {
            $tags = $contentHandler->makeTagLinks($content->tags); // For a content type-specific page use $content->tags, $content->template
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

        // Check if has child objects; if so display thumbnails and teasers / links.
        $criteria = $tfCriteriaFactory->getCriteria();
        $criteria->add($tfCriteriaFactory->getItem('parent', $content->id));
        $criteria->add($tfCriteriaFactory->getItem('online', 1));
        if ($cleanStart) {
            $criteria->setOffset($cleanStart);
        }
        $criteria->setLimit($tfPreference->userPagination);
        $criteria->setOrder('date');
        $criteria->setOrderType('DESC');
        $criteria->setSecondaryOrder('submissionTime');
        $criteria->setSecondaryOrderType('DESC');

        // Prepare pagination control.
        $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
        $tfPagination->setUrl($targetFileName);
        $tfPagination->setCount($contentHandler->getCount($criteria));
        $tfPagination->setLimit($tfPreference->userPagination);
        $tfPagination->setStart($cleanStart);
        $tfPagination->setTag(0);
        $tfPagination->setExtraParams(array('id' => $cleanId));
        $tfTemplate->pagination = $tfPagination->renderPaginationControl();

        // Retrieve content objects and assign to template.
        $firstChildren = $contentHandler->getObjects($criteria);
        if (!empty($firstChildren)) {
            $tfTemplate->firstChildren = $firstChildren;
        }

        // Render template.
        $tfTemplate->tfMainContent = $tfTemplate->render($content->template);
    } else {
        $tfTemplate->tfMainContent = TFISH_ERROR_NO_SUCH_CONTENT;
    }
} else {
    $tfTemplate->tfMainContent = TFISH_ERROR_NO_SUCH_CONTENT;
}

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
// $tfMetadata->setTitle('');
// $tfMetadata->setDescription('');
// $tfMetadata->setAuthor('');
// $tfMetadata->setCopyright('');
// $tfMetadata->setGenerator('');
// $tfMetadata->setSeo('');
$tfMetadata->setRobots('noindex,nofollow'); // Don't want search engines indexing duplicate content.

// Include page template and flush buffer
require_once TFISH_PATH . "tfFooter.php";
