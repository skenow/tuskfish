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

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// Lock handler to static pages.
$content_handler = new TfishContentHandler($tfish_validator, $tfish_database, $tfish_file_handler);
$criteria = new TfishCriteria($tfish_validator);
$criteria->add(new TfishCriteriaItem($tfish_validator, 'type', 'TfishStatic'));

////////// CONFIGURATION //////////
// 1. Enter the ID of the content object you want to display on this page.
$id = 10;

// 2. Enter the name of the page you want headings and tags to link back to, without extension.
$target_file_name = 'index';
$tfish_template->target_file_name = $target_file_name;

// 3. Set the page title.
$tfish_template->page_title = TFISH_TYPE_STATIC_PAGES;

// 4. Specify theme set, otherwise 'default' will be used.
// $tfish_template->setTheme('jumbotron');
////////// END CONFIGURATION //////////

// Validate input parameters.
$clean_id = (int) $id;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;

// Set cache parameters.
$basename = basename(__FILE__);
$cache_parameters = array('id' => $clean_id, 'start' => $clean_start, 'tag_id' => $clean_tag);

if ($clean_id) {
    
    $content = $static_handler->getObject($clean_id);
    
    if (is_object($content) && $content->online) {
        // Update view counter and assign object to template.
        $content->counter += 1;
        $static_handler->updateCounter($clean_id);
        
        // Check if cached page is available.
        $tfish_cache->getCachedPage($tfish_preference, $basename, $cache_parameters);
        
        $tfish_template->content = $content;

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
            $tags = $static_handler->makeTagLinks($content->tags, $target_file_name);
            $tags = TFISH_TAGS . ': ' . implode(', ', $tags);
            $contentInfo[] = $tags;
        }
        $tfish_template->contentInfo = implode(' | ', $contentInfo);
        
        if ($content->meta_title)
            $tfish_metadata->setTitle($content->meta_title);
        
        if ($content->meta_description)
            $tfish_metadata->setDescription($content->meta_description);

        // Check if has a parental object; if so display a thumbnail and teaser / link.
        if (!empty($content->parent)) {
            $parent = $static_handler->getObject($content->parent);
            
            if (is_object($parent) && $parent->online) {
                $tfish_template->parent = $parent;
            }
        }
        
        // Initialise criteria object.
        $criteria = new TfishCriteria($tfish_validator);
        $criteria->setOrder('date');
        $criteria->setOrderType('DESC');
        $criteria->setSecondaryOrder('submission_time');
        $criteria->setSecondaryOrderType('DESC');

        // If object is a collection check if has child objects; if so display teasers / links.
        if ($content->type === 'TfishCollection') {
            $criteria->add(new TfishCriteriaItem($tfish_validator, 'parent', $content->id));
            $criteria->add(new TfishCriteriaItem($tfish_validator, 'online', 1));
            
            if ($clean_start) $criteria->setOffset($clean_start);
            
            $criteria->setLimit($tfish_preference->user_pagination);
        }

        // If object is a tag, then a different method is required to call the related content.
        if ($content->type === 'TfishTag') {
            if ($clean_start) $criteria->setOffset($clean_start);
            
            $criteria->setLimit($tfish_preference->user_pagination);
            $criteria->setTag(array($content->id));
            $criteria->add(new TfishCriteriaItem($tfish_validator, 'type', 'TfishBlock', '!='));
            $criteria->add(new TfishCriteriaItem($tfish_validator, 'online', 1));
        }
        
        // Prepare pagination control.
        if ($content->type === 'TfishCollection' || $content->type === 'TfishTag') {
            $tfish_pagination = new TfishPaginationControl($tfish_validator, $tfish_preference);
            $tfish_pagination->setUrl($target_file_name);
            $tfish_pagination->setCount($content_handler->getCount($criteria));
            $tfish_pagination->setLimit($tfish_preference->user_pagination);
            $tfish_pagination->setStart($clean_start);
            $tfish_pagination->setTag(0);
            $tfish_pagination->setExtraParams(array('id' => $clean_id));
            $tfish_template->collection_pagination = $tfish_pagination->getPaginationControl();

            // Retrieve content objects and assign to template.
            $first_children = $content_handler->getObjects($criteria);
            
            if (!empty($first_children)) {
                $tfish_template->first_children = $first_children;
            }
        }

        // Render template.
        $tfish_template->tfish_main_content = $tfish_template->render($content->template);
    } else {
        $tfish_template->tfish_main_content = TFISH_ERROR_NEED_TO_CONFIGURE_STATIC_PAGE;
    }
}

/**
 * Override page template and metadata here (otherwise default site metadata will display).
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
