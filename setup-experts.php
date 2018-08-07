<?php
/**
 * Script to setup a "biographies" database table.
 *
 * @copyright   Simon Wilkinson 2018 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     content
 */
// Enable strict type declaration.
declare(strict_types=1);

 // Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfHeader.php";

 // Create experts biography table.
$biographyColumns = array(
    "id" => "INTEGER",
    "type" => "TEXT",
    "salutation" => "INTEGER",
    "firstname" => "TEXT",
    "midname" => "TEXT",
    "lastname" => "TEXT",
    "gender" => "INTEGER",
    "job" => "TEXT",
    "experience" => "TEXT",
    "projects" => "TEXT",
    "publications" => "TEXT",
    "businessUnit" => "TEXT",
    "organisation" => "TEXT",
    "address" => "TEXT",
    "city" => "TEXT",
    "state" => "TEXT",
    "country" => "INTEGER",
    "mobile" => "TEXT",
    "fax" => "TEXT",
    "profile" => "TEXT",
    "lastUpdated" => "INTEGER",
    "media" => "TEXT",
    "format" => "TEXT",
    "fileSize" => "INTEGER",
    "image" => "TEXT",
    "parent" => "INTEGER",
    "online" => "INTEGER",
    "counter" => "INTEGER",
    "metaTitle" => "TEXT",
    "metaDescription" => "TEXT",
    "seo" => "TEXT"
    );

$tfDatabase->createTable('biography', $biographyColumns, 'id');

// Create a experts-specific taglinks table.
$taglinkColumns = array(
    "id" => "INTEGER",
    "tagId" => "INTEGER",
    "contentType" => "TEXT",
    "contentId" => "INTEGER");

$tfDatabase->createTable('biolink', $taglinkColumns, 'id');

/** Override page template and metadata here (otherwise default site metadata will display). */
$tfMetadata->title = 'Set up experts database';
// $tfMetadata->description = '';
// $tfMetadata->author = '';
// $tfMetadata->copyright = '';
// $tfMetadata->generator = '';
// $tfMetadata->seo = '';
$tfMetadata->robots = 'noindex,nofollow';
// Include page template and flush buffer
// Render page and shut down.
require_once TFISH_PATH . "tfFooter.php";