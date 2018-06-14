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
require_once TFISH_PATH . "tfish_header.php";

// Create biography table.
$biography_columns = array(
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
    "business_unit" => "TEXT",
    "organisation" => "TEXT",
    "address" => "TEXT",
    "city" => "TEXT",
    "state" => "TEXT",
    "country" => "INTEGER",
    "mobile" => "TEXT",
    "fax" => "TEXT",
    "profile" => "TEXT",
    "submission_time" => "INTEGER",
    "media" => "TEXT",
    "format" => "TEXT",
    "file_size" => "INTEGER",
    "image" => "TEXT",
    "parent" => "INTEGER",
    "online" => "INTEGER",
    "counter" => "INTEGER",
    "meta_title" => "TEXT",
    "meta_description" => "TEXT",
    "seo" => "TEXT"
    );

TfishDatabase::createTable('biography', $biography_columns, 'id');

// Create a biography-specific taglinks table.

$taglink_columns = array(
    "id" => "INTEGER",
    "tag_id" => "INTEGER",
    "content_type" => "TEXT",
    "content_id" => "INTEGER");
TfishDatabase::createTable('biolink', $taglink_columns, 'id');

/**
 * Override page template and metadata here (otherwise default site metadata will display).
 */
$tfish_metadata->title = 'Set up biographies database';
// $tfish_metadata->description = '';
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
$tfish_metadata->robots = 'noindex,nofollow';
// Include page template and flush buffer
// Render page and shut down.
require_once TFISH_PATH . "tfish_footer.php";