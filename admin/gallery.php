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
require_once TFISH_ADMIN_PATH . "tfish_admin_header.php";

// 3. Content header sets module-specific paths and makes TfishContentHandlerFactory available.
require_once TFISH_MODULE_PATH . "content/tfish_content_header.php";

// Specify theme, otherwise 'default' will be used.
$tfish_template->setTheme('gallery');

// Configure page.
$tfish_template->page_title = TFISH_IMAGE_GALLERY;
$content_handler = $content_handler_factory->getHandler('content');
$index_template = 'admin_images';
$target_file_name = 'gallery';
$tfish_template->target_file_name = 'index';

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;
$clean_online = isset($_GET['online']) ? (int) $_GET['online'] : null;
$clean_type = isset($_GET['type']) && !empty($_GET['type']) 
        ? $tfish_validator->trimString($_GET['type']) : '';

// Select content objects where the image field is not null or empty.
$criteria = new TfishCriteria($tfish_validator);
$criteria->add(new TfishCriteriaItem($tfish_validator, 'image', '', '<>'));

// Optional selection criteria.
if ($clean_tag)
    $criteria->setTag(array($clean_tag));

if (isset($clean_online) && $tfish_validator->isInt($clean_online, 0, 1)) {
    $criteria->add(new TfishCriteriaItem($tfish_validator, 'online', $clean_online));
}

if ($clean_type) {
    if (array_key_exists($clean_type, $content_handler->getTypes())) {
        $criteria->add(new TfishCriteriaItem($tfish_validator, 'type', $clean_type));
    } else {
        trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
    }
}

// Prepare pagination control.
$extra_params = array();

if (isset($clean_online) && $tfish_validator->isInt($clean_online, 0, 1)) {
    $extra_params['online'] = $clean_online;
}

if (isset($clean_type) && !empty ($clean_type)) {
    $extra_params['type'] = $clean_type;
}

$tfish_pagination = new TfishPaginationControl($tfish_validator, $tfish_preference);          
$tfish_pagination->setUrl($target_file_name);
$tfish_pagination->setCount($content_handler->getCount($criteria));
$tfish_pagination->setLimit($tfish_preference->gallery_pagination);
$tfish_pagination->setStart($clean_start);
$tfish_pagination->setTag($clean_tag);
$tfish_pagination->setExtraParams($extra_params);
$tfish_template->pagination = $tfish_pagination->getPaginationControl();

// Set offset and limit.
if ($clean_start) $criteria->setOffset($clean_start);
$criteria->setLimit($tfish_preference->gallery_pagination);

// Prepare select filters.
$tag_handler = $content_handler_factory->getHandler('tag');
$tag_select_box = $tag_handler->getTagSelectBox($clean_tag);
$type_select_box = $content_handler->getTypeSelectBox($clean_type);
$online_select_box = $content_handler->getOnlineSelectBox($clean_online);
$tfish_template->select_action = 'gallery.php';
$tfish_template->tag_select = $tag_select_box;
$tfish_template->type_select = $type_select_box;
$tfish_template->online_select = $online_select_box;
$tfish_template->select_filters_form = $tfish_template->render('admin_select_filters');

// Retrieve content objects and assign to template.
$content_objects = $content_handler->getObjects($criteria);
$tfish_template->content_objects = $content_objects;
$tfish_template->tfish_main_content = $tfish_template->render($index_template);

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
// $tfish_metadata->setTitle('');
// $tfish_metadata->setDescription('');
// $tfish_metadata->setAuthor('');
// $tfish_metadata->setCopyright('');
// $tfish_metadata->setGenerator('');
// $tfish_metadata->setSeo('');
// $tfish_metadata->setRobots('');

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";
