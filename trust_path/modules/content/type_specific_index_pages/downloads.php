<?php

/**
 * Downloads index page script.
 *
 * User-facing controller script for presenting a list of downloadable content in teaser format. 
 * Use it for publications, software etc.
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
require_once TFISH_PATH . "tfish_header.php";

// 3. Content header sets module-specific paths and makes TfishContentHandlerFactory available.
require_once TFISH_MODULE_PATH . "content/tfish_content_header.php";

// Lock handler to downloads.
$content_handler = $content_handler_factory->getHandler('content');
$tfish_critiera_factory->getCriteria();
$criteria->add(new TfishCriteriaItem($tfish_validator, 'type', 'TfishDownload'));

// Configure page.
$tfish_template->page_title = TFISH_TYPE_DOWNLOADS;
$index_template = 'downloads';
$target_file_name = 'downloads';
$tfish_template->target_file_name = $target_file_name;
// Specify theme, otherwise 'default' will be used.
$tfish_template->setTheme('default');

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
        // Check if cached page is available.
        $tfish_cache->getCachedPage($basename, $cache_parameters);
        
        // Assign content to template. Counter is only updated when a file download is triggered.
        $tfish_template->content = $content;

        // Prepare meta information for display.
        $contentInfo = array();
        
        if ($content->creator)
            $contentInfo[] = $content->escapeForXss('creator');
        
        if ($content->date)
            $contentInfo[] = $content->escapeForXss('date');
        
        if ($content->counter) {
            switch ($content->type) {
                case "TfishDownload":
                    $contentInfo[] = $content->escapeForXss('counter') . ' ' . TFISH_DOWNLOADS;
                    break;
                default:
                    $contentInfo[] = $content->escapeForXss('counter') . ' ' . TFISH_VIEWS;
            }
        }
        if ($content->format)
            $contentInfo[] = '.' . $content->escapeForXss('format');
        
        if ($content->file_size)
            $contentInfo[] = $content->escapeForXss('file_size');
        
        // For a content type-specific page use $content->tags, $content->template.
        if ($content->tags) {
            $tags = $content_handler->makeTagLinks($content->tags, $target_file_name);
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
            $parent = $content_handler->getObject($content->parent);
            
            if (is_object($parent) && $parent->online) {
                $tfish_template->parent = $parent;
            }
        }

        // Render template.
        $tfish_template->tfish_main_content = $tfish_template->render($content->template);
    } else {
        $tfish_template->tfish_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
    }

// View index page of multiple objects (teasers).
} else {
    // Check if cached page is available.
    $tfish_cache->getCachedPage($basename, $cache_parameters);
    
    if ($clean_start)
        $criteria->setOffset($clean_start);
    
    $criteria->setLimit($tfish_preference->user_pagination);
    
    if ($clean_tag)
        $criteria->setTag(array($clean_tag));
    
    $criteria->add(new TfishCriteriaItem($tfish_validator, 'online', 1));

    // Prepare pagination control.
    $tfish_pagination = new TfishPaginationControl($tfish_validator, $tfish_preference);
    $tfish_pagination->setUrl($target_file_name);
    $tfish_pagination->setCount($content_handler->getCount($criteria));
    $tfish_pagination->setLimit($tfish_preference->user_pagination);
    $tfish_pagination->setStart($clean_start);
    $tfish_pagination->setTag($clean_tag);
    $tfish_template->pagination = $tfish_pagination->getPaginationControl();

    // Retrieve content objects and assign to template.
    $content_objects = $content_handler->getObjects($criteria);
    $tfish_template->content_objects = $content_objects;
    $tfish_template->tfish_main_content = $tfish_template->render($index_template);
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
