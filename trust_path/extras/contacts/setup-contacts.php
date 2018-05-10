<?php

/**
 * Script to setup a "contacts" database table.
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

// Create contact table.
$contact_columns = array(
    "id" => "INTEGER",
    "title" => "INTEGER",
    "firstname" => "TEXT",
    "midname" => "TEXT",
    "lastname" => "TEXT",
    "gender" => "INTEGER",
    "job" => "TEXT",
    "business_unit" => "TEXT",
    "organisation" => "TEXT",
    "address" => "TEXT",
    "city" => "TEXT",
    "state" => "TEXT",
    "country" => "INTEGER",
    "email" => "TEXT",
    "mobile" => "TEXT",
    "submission_time", "INTEGER"
    );

TfishDatabase::createTable('contact', $contact_columns, 'id');

/**
 * Override page template and metadata here (otherwise default site metadata will display).
 */
$tfish_metadata->title = 'Set up contact database';
// $tfish_metadata->description = '';
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
$tfish_metadata->robots = 'noindex,nofollow';
// Include page template and flush buffer

// Render page and shut down.
require_once TFISH_PATH . "tfish_footer.php";