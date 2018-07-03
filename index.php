<?php

/**
 * Tuskfish home page controller script.
 * 
 * Displays a single stream of mixed content (teasers), excluding tags and static content objects.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     content
 */
// Enable strict type declaration.
declare(strict_types=1);

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// Get the relevant handler.
$content_handler = new TfishContentHandler();

// Specify theme, otherwise 'default' will be used.
$tfish_template->setTheme('default');
$index_template = 'single_stream';
$target_file_name = 'index';
$tfish_template->target_file_name = $target_file_name;

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$clean_tag = isset($_GET['tag_id']) ? (int) $_GET['tag_id'] : 0;

// Set cache parameters.
$basename = basename(__FILE__);
$cache_parameters = array('id' => $clean_id, 'start' => $clean_start, 'tag_id' => $clean_tag);

$rss_url = !empty($clean_tag) ? TFISH_RSS_URL . '?tag_id=' . $clean_tag : TFISH_RSS_URL;

// Retrieve a single object if an ID is set.
if ($clean_id) {

    // Retrieve target object.
    $content = $content_handler->getObject($clean_id);
    
    if (is_object($content) && $content->online && $content->type !== 'TfishBlock') {

        // Update view counter and assign object to template. Only increment counter for
        // non-downloadable objects.
        if ($content->type != 'TfishDownload' && !($content->type === 'TfishCollection' 
                && $content->media)) {
            $content->counter += 1;
            $content_handler->updateCounter($clean_id);
        }

        // Check if cached page is available.
        $tfish_cache->checkCache($tfish_preference, $basename, $cache_parameters);

        // Assign content object to template.
        $tfish_template->content = $content;

        // Prepare meta information for display.
        $contentInfo = array();
        
        if ($content->creator) $contentInfo[] = $content->escapeForXss('creator');
        
        if ($content->date) $contentInfo[] = $content->escapeForXss('date');
        
        if ($content->counter) {
            switch ($content->type) {
                case "TfishDownload":
                    $contentInfo[] = $content->escapeForXss('counter') . ' ' . TFISH_DOWNLOADS;
                    break;
                default:
                    $contentInfo[] = $content->escapeForXss('counter') . ' ' . TFISH_VIEWS;
            }
        }
        
        if ($content->format) $contentInfo[] = '.' . $content->escapeForXss('format');
        
        if ($content->file_size) $contentInfo[] = $content->escapeForXss('file_size');
        
        // For a content type-specific page use $content->tags, $content->template
        if ($content->tags) {
            $tags = $content_handler->makeTagLinks($content->tags);
            $tags = TFISH_TAGS . ': ' . implode(', ', $tags);
            $contentInfo[] = $tags;
        }
        
        $tfish_template->contentInfo = implode(' | ', $contentInfo);
        
        if ($content->meta_title) $tfish_metadata->title = $content->escapeForXss('meta_title');
        
        if ($content->meta_description) $tfish_metadata->description = $content->escapeForXss('meta_description');

        // Check if has a parental object; if so display a thumbnail and teaser / link.
        if (!empty($content->parent)) {
            $parent = $content_handler->getObject($content->parent);
            
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
        $tfish_template->tfish_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
    }
// Otherwise retrieve an index page list of teasers.
} else {

    // Check if cached page is available.
    $tfish_cache->checkCache($tfish_preference, $basename, $cache_parameters);

    // Page title, customise it as you see fit.
    $tfish_template->page_title = TFISH_LATEST_POSTS;

    // Exclude static pages, tags and blocks from the index page.
    $criteria = new TfishCriteria();
    
    if ($clean_start) $criteria->offset = $clean_start;
    
    $criteria->limit = $tfish_preference->user_pagination;
    
    if ($clean_tag) $criteria->tag = array($clean_tag);
    
    $criteria->add(new TfishCriteriaItem('type', 'TfishTag', '!='));
    $criteria->add(new TfishCriteriaItem('type', 'TfishStatic', '!='));
    $criteria->add(new TfishCriteriaItem('type', 'TfishBlock', '!='));
    $criteria->add(new TfishCriteriaItem('online', 1));

    // Prepare pagination control.
    $tfish_pagination = new TfishPaginationControl($tfish_preference);
    $count = $content_handler->getCount($criteria);
    $tfish_template->pagination = $tfish_pagination->getPaginationControl($count,
            $tfish_preference->user_pagination, TFISH_URL, $clean_start, $clean_tag);

    // Retrieve content objects and assign to template.
    $criteria->order = 'date';
    $criteria->ordertype = 'DESC';
    $content_objects = $content_handler->getObjects($criteria);
    $tfish_template->content_objects = $content_objects;
    $tfish_template->tfish_main_content = $tfish_template->render($index_template);

    // Prepare tag select box.
    $tfish_template->select_action = 'index.php';
    $tag_handler = new TfishTagHandler();
    $tfish_template->select_filters = $tag_handler->getTagSelectBox($clean_tag);
    $tfish_template->select_filters_form = $tfish_template->render('select_filters');
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
