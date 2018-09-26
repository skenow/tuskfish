<?php
/**
 * Script to setup an "expert" database table.
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

 // Create experts table.
$expertColumns = array(
    "id" => "INTEGER",
    "type" => "TEXT",
    "salutation" => "INTEGER",
    "firstName" => "TEXT",
    "midName" => "TEXT",
    "lastName" => "TEXT",
    "gender" => "INTEGER",
    "job" => "TEXT",
    "experience" => "TEXT",
    "projects" => "TEXT",
    "publications" => "TEXT",
    "businessUnit" => "TEXT",
    "organisation" => "TEXT",
    "address" => "TEXT",
    "country" => "INTEGER",
    "email" => "TEXT",
    "mobile" => "TEXT",
    "fax" => "TEXT",
    "profileLink" => "TEXT",
    "submissionTime" => "INTEGER",
    "lastUpdated" => "INTEGER",
    "expiresOn" => "INTEGER",
    "image" => "TEXT",
    "online" => "INTEGER",
    "counter" => "INTEGER",
    "metaTitle" => "TEXT",
    "metaDescription" => "TEXT",
    "seo" => "TEXT"
    );

$tfDatabase->createTable('expert', $expertColumns, 'id');

/** Override page template and metadata here (otherwise default site metadata will display). */
$tfMetadata->setTitle('Set up experts database');
// $tfMetadata->setDescription();
// $tfMetadata->setAuthor('');
// $tfMetadata->setCopyright('');
// $tfMetadata->setGenerator('');
// $tfMetadata->setSeo('');
$tfMetadata->setRobots('noindex,nofollow');
// Include page template and flush buffer
// Render page and shut down.
require_once TFISH_PATH . "tfFooter.php";