<?php

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

echo '<h1>Test</h1>';

// Testing if 'IN' works or not.
$ids = '1,3,5';
$content = array();

$criteria = new TfishCriteria();
$criteria->add(new TfishCriteriaItem('id', $ids, 'IN'));
$content = TfishContentHandler::getObjects($criteria);

echo '<p>Count: ' . count($content) . '</p>';
print_r($content);