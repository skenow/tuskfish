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
require_once TFISH_PATH . "tf_header.php";

// 3. Content header sets module-specific paths and makes TfContentHandlerFactory available.
require_once TFISH_MODULE_PATH . "content/tf_content_header.php";

// Lock handler to static pages.
$content_handler = $content_handler_factory->getHandler('content');
$tf_critiera_factory->getCriteria();
$criteria->add(new TfCriteriaItem($tf_validator, 'type', 'TfStatic'));

////////// CONFIGURATION //////////
// 1. Enter the ID of the content object you want to display on this page.
$id = 10;

// 2. Enter the name of the page you want headings and tags to link back to, without extension.
$target_file_name = 'index';
$tf_template->target_file_name = $target_file_name;

// 3. Set the page title.
$tf_template->page_title = TFISH_TYPE_STATIC_PAGES;

// 4. Specify theme set, otherwise 'default' will be used.
// $tf_template->setTheme('jumbotron');
////////// END CONFIGURATION //////////

// Validate input parameters.
$clean_id = (int) $id;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;

// Set cache parameters.
$basename = basename(__FILE__);
$cache_parameters = array('id' => $clean_id, 'start' => $clean_start, 'tag_id' => $clean_tag);

if ($clean_id) {
    
    $content = $content_handler->getObject($clean_id);
    
    if (is_object($content) && $content->online) {
        // Update view counter and assign object to template.
        $content->counter += 1;
        $content_handler->updateCounter($clean_id);
        
        // Check if cached page is available.
        $tf_cache->getCachedPage($basename, $cache_parameters);
        
        $tf_template->content = $content;

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
            $tags = $content_handler->makeTagLinks($content->tags, $target_file_name);
            $tags = TFISH_TAGS . ': ' . implode(', ', $tags);
            $contentInfo[] = $tags;
        }
        $tf_template->contentInfo = implode(' | ', $contentInfo);
        
        if ($content->meta_title)
            $tf_metadata->setTitle($content->meta_title);
        
        if ($content->meta_description)
            $tf_metadata->setDescription($content->meta_description);

        // Check if has a parental object; if so display a thumbnail and teaser / link.
        if (!empty($content->parent)) {
            $parent = $content_handler->getObject($content->parent);
            
            if (is_object($parent) && $parent->online) {
                $tf_template->parent = $parent;
            }
        }
        
        // Initialise criteria object.
        $tf_critiera_factory->getCriteria();
        $criteria->setOrder('date');
        $criteria->setOrderType('DESC');
        $criteria->setSecondaryOrder('submission_time');
        $criteria->setSecondaryOrderType('DESC');

        // If object is a collection check if has child objects; if so display teasers / links.
        if ($content->type === 'TfCollection') {
            $criteria->add(new TfCriteriaItem($tf_validator, 'parent', $content->id));
            $criteria->add(new TfCriteriaItem($tf_validator, 'online', 1));
            
            if ($clean_start) $criteria->setOffset($clean_start);
            
            $criteria->setLimit($tf_preference->user_pagination);
        }

        // If object is a tag, then a different method is required to call the related content.
        if ($content->type === 'TfTag') {
            if ($clean_start) $criteria->setOffset($clean_start);
            
            $criteria->setLimit($tf_preference->user_pagination);
            $criteria->setTag(array($content->id));
            $criteria->add(new TfCriteriaItem($tf_validator, 'type', 'TfBlock', '!='));
            $criteria->add(new TfCriteriaItem($tf_validator, 'online', 1));
        }
        
        // Prepare pagination control.
        if ($content->type === 'TfCollection' || $content->type === 'TfTag') {
            $tf_pagination = new TfPaginationControl($tf_validator, $tf_preference);
            $tf_pagination->setUrl($target_file_name);
            $tf_pagination->setCount($content_handler->getCount($criteria));
            $tf_pagination->setLimit($tf_preference->user_pagination);
            $tf_pagination->setStart($clean_start);
            $tf_pagination->setTag(0);
            $tf_pagination->setExtraParams(array('id' => $clean_id));
            $tf_template->collection_pagination = $tf_pagination->getPaginationControl();

            // Retrieve content objects and assign to template.
            $first_children = $content_handler->getObjects($criteria);
            
            if (!empty($first_children)) {
                $tf_template->first_children = $first_children;
            }
        }

        // Render template.
        $tf_template->tf_main_content = $tf_template->render($content->template);
    } else {
        $tf_template->tf_main_content = TFISH_ERROR_NEED_TO_CONFIGURE_STATIC_PAGE;
    }
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
