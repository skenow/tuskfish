<?php

/**
 * Admin image manager script.
 * 
 * Display and filter image content, including offline content and images associated with non-image
 * content objects. Use it to locate and re-use image assets.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since		1.0
 * @package		admin
 */
// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "../mainfile.php";
require_once TFISH_ADMIN_PATH . "tfish_admin_header.php";

// Specify theme, otherwise 'default' will be used.
$tfish_template->setTheme('thumbnail_gallery');

// CONVENTIONS:
// 1. Specify the class name of the handler for the object type this page will handle,
// eg. 'TfishImageHandler'.
// 2. Specify the name of the template for the index page, eg. 'gallery'.
$content_handler = 'TfishContentHandler';
$index_template = 'admin_images';
$target_file_name = 'gallery';

// Page title.
$tfish_template->page_title = '<i class="fa fa-file-image-o" aria-hidden="true"></i> '
        . TFISH_IMAGE_GALLERY;

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;
$clean_online = isset($_GET['online']) ? (int) $_GET['online'] : null;
$clean_type = isset($_GET['type']) && !empty($_GET['type']) 
        ? TfishFilter::trimString($_GET['type']) : null;

// Select content objects where the image field is not null or empty.
$criteria = new TfishCriteria();
$criteria->add(new TfishCriteriaItem('image', '', '<>'));

// Optional selection criteria.
if ($clean_tag)
    $criteria->tag = array($clean_tag);

if (isset($clean_online) && TfishFilter::isInt($clean_online, 0, 1)) {
    $criteria->add(new TfishCriteriaItem('online', 1));
}

if ($clean_type) {
    if (array_key_exists($clean_type, TfishContentHandler::getTypes())) {
        $criteria->add(new TfishCriteriaItem('type', $clean_type));
    } else {
        trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
    }
}

// Prepare pagination control.
$count = $content_handler::getCount($criteria);
$extra_params = array();

if (isset($clean_online) && TfishFilter::isInt($clean_online, 0, 1)) {
    $extra_params['online'] = $clean_online;
}

if (isset($clean_type)) {
    $extra_params['type'] = $clean_type;
}

$tfish_template->pagination = $tfish_metadata->getPaginationControl($count, 
        $tfish_preference->gallery_pagination, $target_file_name, $clean_start, $clean_tag, 
        $extra_params);

// Set offset and limit.
if ($clean_start) $criteria->offset = $clean_start;
$criteria->limit = $tfish_preference->gallery_pagination;

// Prepare select filters.
$tag_select_box = TfishTagHandler::getTagSelectBox($clean_tag);
$type_select_box = TfishContentHandler::getTypeSelectBox($clean_type);
$online_select_box = TfishContentHandler::getOnlineSelectBox($clean_online);
$tfish_template->select_action = 'gallery.php';
$tfish_template->tag_select = $tag_select_box;
$tfish_template->type_select = $type_select_box;
$tfish_template->online_select = $online_select_box;
$tfish_template->select_filters_form = $tfish_template->render('admin_select_filters');

// Retrieve content objects and assign to template.
$content_objects = $content_handler::getObjects($criteria);
$tfish_template->content_objects = $content_objects;
$tfish_template->tfish_main_content = $tfish_template->render($index_template);

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
// $tfish_metadata->title = '';
// $tfish_metadata->description = '';
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
// $tfish_metadata->robots = '';
// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";
