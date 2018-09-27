<?php

/**
 * Admin image manager script.
 * 
 * Display and filter image content, including offline content and images associated with non-image
 * content objects. Use it to locate and re-use image assets.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     admin
 */
// Enable strict type declaration.
declare(strict_types=1);

// 1. Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "../mainfile.php";

// 2. Main Tuskfish header. This file bootstraps Tuskfish.
require_once TFISH_ADMIN_PATH . "tfAdminHeader.php";

// 3. Content header sets module-specific paths and makes TfContentHandler available.
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php";

// Specify theme, otherwise 'default' will be used.
$tfTemplate->setTheme('gallery');

// Configure page.
$tfTemplate->pageTitle = TFISH_IMAGE_GALLERY;
$contentHandler = $contentFactory->getContentHandler('content');
$indexTemplate = 'adminImages';
$targetFileName = 'gallery';
$tfTemplate->targetFileName = 'index';

// Validate input parameters.
$cleanId = (int) ($_GET['id'] ?? 0);
$cleanStart = (int) ($_GET['start'] ?? 0);
$cleanTag = (int) ($_GET['tagId'] ?? 0);
$cleanOnline = isset($_GET['online']) ? (int) $_GET['online'] : null;
$cleanType = isset($_GET['type']) && !empty($_GET['type']) 
        ? $tfValidator->trimString($_GET['type']) : '';

// Select content objects where the image field is not null or empty.
$criteria = $tfCriteriaFactory->getCriteria();
$criteria->add($tfCriteriaFactory->getItem('image', '', '<>'));

// Optional selection criteria.
if ($cleanTag)
    $criteria->setTag(array($cleanTag));

if (isset($cleanOnline) && $tfValidator->isInt($cleanOnline, 0, 1)) {
    $criteria->add($tfCriteriaFactory->getItem('online', $cleanOnline));
}

if ($cleanType) {
    if (array_key_exists($cleanType, $contentHandler->getTypes())) {
        $criteria->add($tfCriteriaFactory->getItem('type', $cleanType));
    } else {
        trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
    }
}

// Prepare pagination control.
$extraParams = array();

if (isset($cleanOnline) && $tfValidator->isInt($cleanOnline, 0, 1)) {
    $extraParams['online'] = $cleanOnline;
}

if (isset($cleanType) && !empty ($cleanType)) {
    $extraParams['type'] = $cleanType;
}

$tfPagination = new TfPaginationControl($tfValidator, $tfPreference);          
$tfPagination->setUrl($targetFileName);
$tfPagination->setCount($contentHandler->getCount($criteria));
$tfPagination->setLimit($tfPreference->galleryPagination);
$tfPagination->setStart($cleanStart);
$tfPagination->setTag($cleanTag);
$tfPagination->setExtraParams($extraParams);
$tfTemplate->pagination = $tfPagination->renderPaginationControl();

// Set offset and limit.
if ($cleanStart) $criteria->setOffset($cleanStart);
$criteria->setLimit($tfPreference->galleryPagination);

// Prepare select filters.
$tagHandler = $contentFactory->getContentHandler('tag');
$tagSelectBox = $tagHandler->getTagSelectBox($cleanTag, 'content');
$typeSelectBox = $contentHandler->getTypeSelectBox($cleanType);
$onlineSelectBox = $contentHandler->getOnlineSelectBox($cleanOnline);
$tfTemplate->selectAction = 'gallery.php';
$tfTemplate->tagSelect = $tagSelectBox;
$tfTemplate->typeSelect = $typeSelectBox;
$tfTemplate->onlineSelect = $onlineSelectBox;
$tfTemplate->selectFiltersForm = $tfTemplate->render('adminSelectFilters');

// Retrieve content objects and assign to template.
$contentObjects = $contentHandler->getObjects($criteria);
$tfTemplate->contentObjects = $contentObjects;
$tfTemplate->tfMainContent = $tfTemplate->render($indexTemplate);

/**
 * Override page metadata here (otherwise default site metadata will display).
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
