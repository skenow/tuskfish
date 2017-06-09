<?php

/**
 * Static page template script.
 * 
 * User-facing controller script for presenting a single static content page. Simply make a copy of
 * this file with whatever name you want and set the id of the content you want to display in the
 * configuration section. 
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since		1.0
 * @package		core
 */
// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// Specify template set, otherwise 'default' will be used.
// $tfish_template->setTemplate('jumbotron');

////////// CONFIGURATION //////////
// 1. Enter the ID of the content object you want to display on this page.
$id = 10;

// 2. Enter the name of the page you want tags to link back to, without extension.
$target_file_name = 'index';

// 3. Set the page title.
$tfish_template->page_title = TFISH_TYPE_STATIC_PAGES;
////////// END CONFIGURATION //////////

// View single object description.
$clean_id = (int) $id;
if ($clean_id) {
    $content = TfishStaticHandler::getObject($clean_id);
    if (is_object($content) && $content->online == true) {

        // Update view counter and assign object to template.
        $content->counter += 1;
        TfishStaticHandler::updateCounter($clean_id);
        $tfish_template->content = $content;

        // Prepare meta information for display.
        $contentInfo = array();
        if ($content->creator)
            $contentInfo[] = $content->escape('creator');
        if ($content->date)
            $contentInfo[] = $content->escape('date');
        if ($content->counter)
            $contentInfo[] = $content->escape('counter') . ' ' . TFISH_VIEWS;
        if ($content->tags) {
            $tags = TfishStaticHandler::makeTagLinks($content->tags, $target_file_name); // For a content type-specific page use $content->tags, $content->template
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
            $parent = TfishStaticHandler::getObject($content->parent);
            if (is_object($parent) && $parent->online) {
                $tfish_template->parent = $parent;
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
