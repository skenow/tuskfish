<?php

/**
* Tuskfish enclosure retrieval script.
* 
* Provides an enclosure (media file) retrieval service for RSS feeds to hook into, as the actual
* files are stored outside of the web root, so direct access is not possible.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";
$clean_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($clean_id) {
	TfishFileHandler::sendDownload($clean_id);
}
exit;