<?php

/**
 * Tag index page.
 * 
 * User-facing controller script for presenting a list of tags in teaser format.
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
require_once TFISH_PATH . "tf_header.php";

// 3. Content header sets module-specific paths and makes TfContentHandlerFactory available.
require_once TFISH_MODULE_PATH . "content/tf_content_header.php";

// Configure page.
$tf_template->page_title = TFISH_TYPE_TAGS;
$content_handler = $content_handler_factory->getHandler('content');
$index_template = 'tags';
$target_file_name = 'tags';
$tf_template->target_file_name = $target_file_name;
// $tf_template->setTheme('jumbotron');

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;

// Set cache parameters.
$basename = basename(__FILE__);
$cache_parameters = array('id' => $clean_id, 'start' => $clean_start, 'tag_id' => $clean_tag);

// View single object description.
if ($clean_id) {
    $content = $content_handler->getObject($clean_id);
    
    if (is_object($content) && $content->online) {
        // Update view counter and assign object to template.
        $content->counter += 1;
        $content_handler->updateCounter($clean_id);
        
        // Check if cached page is available.
        $tf_cache->getCachedPage($basename, $cache_parameters);
        
        // Assign content to template.
        $tf_template->content = $content;

        // Prepare meta information for display.
        if ($content->meta_title)
            $tf_metadata->setTitle($content->meta_title);
        
        if ($content->meta_description)
            $tf_metadata->setDescription($content->meta_description);

        // Render template.
        $tf_template->tf_main_content = $tf_template->render($content->template);
    } else {
        $tf_template->tf_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
    }

// View index page of multiple objects (teasers).
} else {
    // Check if cached page is available.
    $tf_cache->getCachedPage($basename, $cache_parameters);
    
    // Set criteria for selecting content objects.
    $criteria = $tf_criteria_factory->getCriteria();
    
    if ($clean_start)
        $criteria->setOffset($clean_start);
    
    $criteria->setLimit($tf_preference->user_pagination);
    $criteria->add(new TfCriteriaItem($tf_validator, 'online', 1));

    // Prepare pagination control.
    $tf_pagination = new TfPaginationControl($tf_validator, $tf_preference);
    $tf_pagination->setUrl($target_file_name);
    $tf_pagination->setCount($content_handler->getCount($criteria));
    $tf_pagination->setLimit($tf_preference->user_pagination);
    $tf_pagination->setStart($clean_start);
    $tf_template->pagination = $tf_pagination->getPaginationControl();

    // Retrieve content objects and assign to template.
    $content_objects = $content_handler->getObjects($criteria);
    $tf_template->content_objects = $content_objects;
    $tf_template->tf_main_content = $tf_template->render($index_template);
}

/**
 * Override page template and metadata here (otherwise default site metadata will display).
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
