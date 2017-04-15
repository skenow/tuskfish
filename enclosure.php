<?php

/**
* Tuskfish enclosure retrieval script.
* 
* Provides an enclosure (media file) retrieval service for RSS feeds to hook into, as the actual
* files are stored outside of the web root, so direct access is not possible. Simply supply the ID
* of a content object with a downloadable media attachment in order to retrieve the file.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";
$clean_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($clean_id) {
	TfishContentHandler::updateCounter($clean_id);
	//session_write_close();
	TfishFileHandler::sendDownload($clean_id);
}
exit;