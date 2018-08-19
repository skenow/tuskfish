<?php

/**
 * Admin controller script for the Machines module.
 * 
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     machines
 */
// Enable strict type declaration.
declare(strict_types=1);

// Boot! Set file paths, preferences and connect to database.
require_once "../mainfile.php";
require_once TFISH_ADMIN_PATH . "tfAdminHeader.php";
require_once TFISH_MODULE_PATH . "machines/tfMachinesHeader.php";

// Specify the admin theme you want to use.
$tfTemplate->setTheme('admin');

/**
 * Validate input parameters here.
 **/

// Permitted options.
$op = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : false;
$optionsWhitelist = array();

if (in_array($op, $optionsWhitelist, true)) {
    exit;
}
    
// Cross-site request forgery check.
if (!in_array($op, $optionsWhitelist, true)) {
    TfSession::validateToken($cleanToken);
}

// Business logic goes here.
switch ($op) {
    default:
        $criteria = $tfCriteriaFactory->getCriteria();

        if ($cleanTag) $criteria->setTag(array($cleanTag));

        if ($tfValidator->isInt($cleanOnline, 0, 1)) {
            $criteria->add($tfCriteriaFactory->getItem('online', $cleanOnline));
        }

        /*if ($cleanType) {
            if (array_key_exists($cleanType, $contentHandler->getTypes())) {
                $criteria->add($tfCriteriaFactory->getItem('type', $cleanType));
            } else {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            }
        }*/

        // Other criteria.
        $criteria->setOffset($cleanStart);
        $criteria->setLimit($tfPreference->adminPagination);
        $criteria->setOrder('submissionTime');
        $criteria->setOrderType('DESC');
        $columns = array('id', 'type', 'title', 'submissionTime', 'counter', 'online');
        $result = $tfDatabase->select('content', $criteria, $columns);

        if ($result) {
            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }

        foreach ($rows as &$row) {
            $row['submissionTime']
                    = date($tfPreference->dateFormat, (int) $row['submissionTime']);
        }

        $typelist = $machineHandler->getTypes();

        // Pagination control.
        $extraParams = array();
        if (isset($cleanOnline) && $tfValidator->isInt($cleanOnline, 0, 1)) {
            $extraParams['online'] = $cleanOnline;
        }
        if (isset($cleanType) && !empty($cleanType)) {
            $extraParams['type'] = $cleanType;
        }

        $tfPagination = new TfPaginationControl($tfValidator, $tfPreference);
        $tfPagination->setUrl('machine');
        $tfPagination->setCount($tfDatabase->selectCount('machine', $criteria));
        $tfPagination->setLimit($tfPreference->adminPagination);
        $tfPagination->setStart($cleanStart);
        $tfPagination->setTag($cleanTag);
        $tfPagination->setExtraParams($extraParams);
        $tfTemplate->pagination = $tfPagination->renderPaginationControl();

        // Prepare select filters.
        $tagHandler = $contentHandlerFactory->getHandler('tag');
        $tagSelectBox = $tagHandler->getTagSelectBox($cleanTag);
        $typeSelectBox = $contentHandler->getTypeSelectBox($cleanType);
        $onlineSelectBox = $contentHandler->getOnlineSelectBox($cleanOnline);
        $tfTemplate->selectAction = 'admin.php';
        $tfTemplate->tagSelect = $tagSelectBox;
        $tfTemplate->typeSelect = $typeSelectBox;
        $tfTemplate->onlineSelect = $onlineSelectBox;
        $tfTemplate->selectFiltersForm = $tfTemplate->render('adminSelectFilters');

        // Assign to template.
        $tfTemplate->pageTitle = TFISH_CURRENT_CONTENT;
        $tfTemplate->rows = $rows;
        $tfTemplate->typelist = $contentHandler->getTypes();
        $tfTemplate->form = TFISH_CONTENT_MODULE_FORM_PATH . "contentTable.html";
        $tfTemplate->tfMainContent = $tfTemplate->render('form');
        break;
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