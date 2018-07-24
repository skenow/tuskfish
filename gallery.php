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
require_once "mainfile.php";

// 2. Main Tuskfish header. This file bootstraps Tuskfish.
require_once TFISH_PATH . "tf_header.php";

// 3. Content header sets module-specific paths and makes TfContentHandlerFactory available.
require_once TFISH_MODULE_PATH . "content/tf_content_header.php";

// Specify theme, otherwise 'default' will be used.
$tf_template->setTheme('default');

// Configure page.
$tf_template->page_title = TFISH_IMAGE_GALLERY;
$content_handler = $content_handler_factory->getHandler('content');
$index_template = 'gallery';
$target_file_name = 'gallery';
$tf_template->target_file_name = $target_file_name;

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;
$clean_type = isset($_GET['type']) && !empty($_GET['type']) 
        ? $tf_validator->trimString($_GET['type']) : '';

// Select content objects where the image field is not null or empty.
$criteria = $tf_criteria_factory->getCriteria();
$criteria->add(new TfCriteriaItem($tf_validator, 'image', '', '<>'));
$criteria->add(new TfCriteriaItem($tf_validator, 'online', 1));

// Optional selection criteria.
if ($clean_tag)
    $criteria->setTag(array($clean_tag));

if ($clean_type) {
    if (array_key_exists($clean_type, $content_handler->getTypes())) {
        $criteria->add(new TfCriteriaItem($tf_validator, 'type', $clean_type));
    } else {
        trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
    }
}

// Prepare pagination control.
$tf_pagination = new TfPaginationControl($tf_validator, $tf_preference);          
$tf_pagination->setUrl($target_file_name);
$tf_pagination->setCount($content_handler->getCount($criteria));
$tf_pagination->setLimit($tf_preference->gallery_pagination);
$tf_pagination->setStart($clean_start);
$tf_pagination->setTag($clean_tag);

if (isset($clean_type) && !empty($clean_type)) {
    $tf_pagination->setExtraParams(array(['type'] => $clean_type));
}

$tf_template->pagination = $tf_pagination->getPaginationControl();

// Set offset and limit.
if ($clean_start) $criteria->setOffset($clean_start);
$criteria->setLimit($tf_preference->gallery_pagination);

// Prepare select filters.
$tag_handler = $content_handler_factory->getHandler('tag');
$tag_select_box = $tag_handler->getTagSelectBox($clean_tag);
$tf_template->select_action = 'gallery.php';
$tf_template->tag_select = $tag_select_box;
$tf_template->select_filters_form = $tf_template->render('gallery_filters');

// Retrieve content objects and assign to template.
$content_objects = $content_handler->getObjects($criteria);
$tf_template->content_objects = $content_objects;
$tf_template->tf_main_content = $tf_template->render($index_template);

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
// $tf_metadata->setTitle('');
// $tf_metadata->setDescription('');
// $tf_metadata->setAuthor('');
// $tf_metadata->setCopyright('');
// $tf_metadata->setGenerator('');
// $tf_metadata->setSeo('');
// $tf_metadata->setRobots('');

// Include page template and flush buffer
require_once TFISH_PATH . "tf_footer.php";
