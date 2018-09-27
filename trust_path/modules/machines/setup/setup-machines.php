<?php
/**
 * Script to setup a database tables for the Machines module.
 *
 * @copyright   Simon Wilkinson 2018 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     machines
 */
declare(strict_types=1);
require_once "mainfile.php";
require_once TFISH_PATH . "tfHeader.php";

// Create machine table.
$machineColumns = array(
    "id" => "INTEGER", 
    "title" => "TEXT", 
    "teaser" => "TEXT", 
    "description" => "TEXT", 
    "latitude" => "REAL", 
    "longitude" => "REAL",
    "online" => "INTEGER", 
    "submissionTime" => "INTEGER", 
    "lastUpdated" => "INTEGER",
    "expiresOn" => "INTEGER",
    "counter" => "INTEGER", 
    "key" => "TEXT", 
    "metaTitle" => "TEXT", 
    "metaDescription" => "TEXT", 
    "seo" => "TEXT",
    );

$tfDatabase->createTable('machine', $machineColumns, 'id');

// Create sensor table.
$sensorColumns = array(
    "id" => "INTEGER",    
    "type" => "TEXT",
    "protocol" => "TEXT",
    "title" => "TEXT",
    "teaser" => "TEXT",
    "description" => "TEXT",
    "parent" => "INTEGER",
    "online" => "INTEGER",
    "submissionTime" => "INTEGER",
    "lastUpdated" => "INTEGER",
    "expiresOn" => "INTEGER",
    "counter" => "INTEGER",
    "metaTitle" => "TEXT",
    "metaDescription" => "TEXT",
    "seo" => "TEXT",
    );

$tfDatabase->createTable('sensor', $sensorColumns, 'id');

// Need to echo success message.

/**
 * Override page template and metadata here (otherwise default site metadata will display).
 */
$tfMetadata->setTitle('Set up the Machines module');
// $tfish_metadata->description = '';
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
$tfMetadata->setRobots('noindex,nofollow');
// Include page template and flush buffer
// Render page and shut down.
require_once TFISH_PATH . "tfFooter.php";
