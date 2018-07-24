<?php

/**
 * Permalink script.
 * 
 * Provides a permalink lookup service for all content objects. Simply supply the ID of the content.
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
require_once TFISH_PATH . "tf_header.php";

// Specify theme, otherwise 'default' will be used.
$tf_template->setTheme('default');

// Configure page.
$tf_template->page_title = TFISH_TYPE_PERMALINKS;
$content_handler = $content_handler_factory->getHandler('content');
$target_file_name = 'permalink';

// Validate input parameters.
$clean_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$clean_start = isset($_GET['start']) ? (int) $_GET['start'] : 0;

// View single object description.
if ($clean_id) {
    $content = $content_handler->getObject($clean_id);
    if (is_object($content) && $content->online) {

        // Update view counter and assign object to template. Only increment counter for non-downloadable objects.
        if ($content->type != 'TfDownload') {
            $content->counter += 1;
            $content_handler->updateCounter($clean_id);
        }
        $tf_template->content = $content;

        // Prepare meta information for display.
        $contentInfo = array();
        if ($content->creator)
            $contentInfo[] = $content->escapeForXss('creator');
        if ($content->date)
            $contentInfo[] = $content->escapeForXss('date');
        if ($content->counter)
            $contentInfo[] = $content->escapeForXss('counter') . ' ' . TFISH_VIEWS;
        if ($content->format)
            $contentInfo[] = '.' . $content->escapeForXss('format');
        if ($content->file_size)
            $contentInfo[] = $content->escapeForXss('file_size');
        if ($content->tags) {
            $tags = $content_handler->makeTagLinks($content->tags); // For a content type-specific page use $content->tags, $content->template
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

        // Check if has child objects; if so display thumbnails and teasers / links.
        $criteria = $tf_criteria_factory->getCriteria();
        $criteria->add(new TfCriteriaItem($tf_validator, 'parent', $content->id));
        $criteria->add(new TfCriteriaItem($tf_validator, 'online', 1));
        if ($clean_start) {
            $criteria->setOffset($clean_start);
        }
        $criteria->setLimit($tf_preference->user_pagination);
        $criteria->setOrder('date');
        $criteria->setOrderType('DESC');
        $criteria->setSecondaryOrder('submission_time');
        $criteria->setSecondaryOrderType('DESC');

        // Prepare pagination control.
        $tf_pagination = new TfPaginationControl($tf_validator, $tf_preference);
        $tf_pagination->setUrl($target_file_name);
        $tf_pagination->setCount($content_handler->getCount($criteria));
        $tf_pagination->setLimit($tf_preference->user_pagination);
        $tf_pagination->setStart($clean_start);
        $tf_pagination->setTag(0);
        $tf_template->setExtraParams(array('id' => $clean_id));
        $tf_template->pagination = $tf_pagination->getPaginationControl();

        // Retrieve content objects and assign to template.
        $first_children = $content_handler->getObjects($criteria);
        if (!empty($first_children)) {
            $tf_template->first_children = $first_children;
        }

        // Render template.
        $tf_template->tf_main_content = $tf_template->render($content->template);
    } else {
        $tf_template->tf_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
    }
} else {
    $tf_template->tf_main_content = TFISH_ERROR_NO_SUCH_CONTENT;
}

/**
 * Override page metadata here (otherwise default site metadata will display).
 */
// $tf_metadata->setTitle('');
// $tf_metadata->setDescription('');
// $tf_metadata->setAuthor('');
// $tf_metadata->setCopyright('');
// $tf_metadata->setGenerator('');
// $tf_metadata->setSeo('');
$tf_metadata->setRobots('noindex,nofollow'); // Don't want search engines indexing duplicate content.

// Include page template and flush buffer
require_once TFISH_PATH . "tf_footer.php";
