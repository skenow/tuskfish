<?php

/**
* Tuskfish basic page template script
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

$clean_start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$article_handler = new TfishArticleHandler();
$articles = $article_handler->getObjects();
$tfish_content['output'] = '<ul>';
foreach ($articles as $article) {
	$tfish_content['output'] .= '<li>' . $article->title . '</li>';
}
$tfish_content['output'] .= '<ul>';
$count = count($articles);

// Assign template variables.
$page_title = 'Articles';
$pagination = $tfish_metadata->getPaginationControl($count, $tfish_preference->user_pagination, TFISH_URL);

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
// $tfish_metadata->template = '';

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";