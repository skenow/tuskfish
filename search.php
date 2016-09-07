<?php

/**
* Tuskfish search script
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
// Site preferences can be accessed via $tfish_preference->key;
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// Assign template variables.
$page_title = 'Search';
// $pagination = $tfish_metadata->getPaginationControl($count, $tfish_preference->search_pagination, TFISH_URL);

// Include an advanced search form
$tfish_form = TFISH_FORM_PATH . 'search.html';

// So...where should the search functionality reside? Handler?

/**
 * Override page template and metadata here (otherwise default site metadata will display).
 */
$tfish_metadata->title = TFISH_SEARCH;
$tfish_metadata->description = TFISH_SEARCH_DESCRIPTION;
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
// $tfish_metadata->robots = '';
// $tfish_metadata->template = '';

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";