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

// 2. Module header must precede Tuskfish header. This file sets module-specific paths.
require_once TFISH_MODULE_PATH . "content/tfish_content_header.php";

// 3. Main Tuskfish header. This file bootstraps Tuskfish.
require_once TFISH_ADMIN_PATH . "tfish_admin_header.php";

// Specify theme, otherwise 'default' will be used.
$tfish_template->setTheme('gallery');

// Configure page.
$tfish_template->page_title = TFISH_IMAGE_GALLERY;
$content_handler = new TfishContentHandler();
$index_template = 'admin_images';
$target_file_name = 'gallery';
$tfish_template->target_file_name = 'index';

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;
$clean_online = isset($_GET['online']) ? (int) $_GET['online'] : null;
$clean_type = isset($_GET['type']) && !empty($_GET['type']) 
        ? TfishDataValidator::trimString($_GET['type']) : '';

// Select content objects where the image field is not null or empty.
$criteria = new TfishCriteria();
$criteria->add(new TfishCriteriaItem('image', '', '<>'));

// Optional selection criteria.
if ($clean_tag)
    $criteria->setTag(array($clean_tag));

if (isset($clean_online) && TfishDataValidator::isInt($clean_online, 0, 1)) {
    $criteria->add(new TfishCriteriaItem('online', 1));
}

if ($clean_type) {
    if (array_key_exists($clean_type, $content_handler->getTypes())) {
        $criteria->add(new TfishCriteriaItem('type', $clean_type));
    } else {
        trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
    }
}

// Prepare pagination control.
$count = $content_handler->getCount($criteria);
$extra_params = array();

if (isset($clean_online) && TfishDataValidator::isInt($clean_online, 0, 1)) {
    $extra_params['online'] = $clean_online;
}

if (isset($clean_type)) {
    $extra_params['type'] = $clean_type;
}

$tfish_pagination = new TfishPaginationControl($tfish_preference);
$tfish_template->pagination = $tfish_pagination->getPaginationControl($count, 
        $tfish_preference->gallery_pagination, $target_file_name, $clean_start, $clean_tag, 
        $extra_params);

// Set offset and limit.
if ($clean_start) $criteria->setOffset($clean_start);
$criteria->setLimit($tfish_preference->gallery_pagination);

// Prepare select filters.
$tag_handler = new TfishTagHandler();
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
