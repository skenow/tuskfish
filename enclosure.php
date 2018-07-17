<?php

/**
 * Outputs content object media enclosures.
 * 
 * Provides an enclosure (media file) retrieval service for content objects and RSS feeds. Simply
 * supply the ID of a content object with a downloadable media attachment in order to retrieve the
 * file.
 *
 * @copyright	Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since		1.0
 * @package		content
 */
// Enable strict type declaration.
declare(strict_types=1);

// 1. Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";

// 2. Module header must precede Tuskfish header. This file sets module-specific paths.
require_once TFISH_MODULE_PATH . "content/tfish_content_header.php";

// 3. Main Tuskfish header. This file bootstraps Tuskfish.
require_once TFISH_PATH . "tfish_header.php";

$clean_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($clean_id) {
    $content_handler = new TfishContentHandler($tfish_validator, $tfish_file_handler);
    $content_handler->updateCounter($clean_id);
    $content_handler->streamDownloadToBrowser($clean_id);
}

exit;