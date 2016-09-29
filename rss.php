<?php

/**
* Tuskfish RSS feed generator script.
* 
* Site preferences can be accessed via $tfish_preference->key.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

header('Content-Type: application/rss+xml');
$rss = new TfishRss();
$tfish_template->rss = $rss;
$tfish_template->tfish_main_content = $tfish_template->render('rss');

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
$tfish_metadata->template = 'rss.html';

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";