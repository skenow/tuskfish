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
    $static_handler = new TfishStaticHandler();
    
    $content = $static_handler->getObject($clean_id);
    
    if (is_object($content) && $content->online) {
        // Update view counter and assign object to template.
        $content->counter += 1;
        $static_handler->updateCounter($clean_id);
        
        // Check if cached page is available.
        $tfish_cache->getFromCache($tfish_preference, $basename, $cache_parameters);
        
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
            $tfish_metadata->title = $content->meta_title;
        
        if ($content->meta_description)
            $tfish_metadata->description = $content->meta_description;

        // Check if has a parental object; if so display a thumbnail and teaser / link.
        if (!empty($content->parent)) {
            $parent = $static_handler->getObject($content->parent);
            
            if (is_object($parent) && $parent->online) {
                $tfish_template->parent = $parent;
            }
        }
        
        // Initialise criteria object.
        $criteria = new TfishCriteria();
        $criteria->order = 'date';
        $criteria->ordertype = 'DESC';

        // If object is a collection check if has child objects; if so display teasers / links.
        if ($content->type === 'TfishCollection') {
            $criteria->add(new TfishCriteriaItem('parent', $content->id));
            $criteria->add(new TfishCriteriaItem('online', 1));
            
            if ($clean_start) $criteria->offset = $clean_start;
            
            $criteria->limit = $tfish_preference->user_pagination;
        }

        // If object is a tag, then a different method is required to call the related content.
        if ($content->type === 'TfishTag') {
            if ($clean_start) $criteria->offset = $clean_start;
            
            $criteria->limit = $tfish_preference->user_pagination;
            $criteria->tag = array($content->id);
            $criteria->add(new TfishCriteriaItem('type', 'TfishBlock', '!='));
            $criteria->add(new TfishCriteriaItem('online', 1));
        }
        
        // Prepare pagination control.
        $tfish_pagination = new TfishPaginationControl($tfish_preference);
        
        if ($content->type === 'TfishCollection' || $content->type === 'TfishTag') {
            $content_handler = new TfishContentHandler();
            $first_child_count = $content_handler->getCount($criteria);
            $tfish_template->collection_pagination = $tfish_pagination->getPaginationControl(
                    $first_child_count, $tfish_preference->user_pagination, $target_file_name,
                    $clean_start, 0, array('id' => $clean_id));

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
// $tfish_metadata->title = '';
// $tfish_metadata->description = '';
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
// $tfish_metadata->robots = '';
// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";
