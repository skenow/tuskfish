<?php

require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

/**
 * Override page template and metadata here (otherwise default site metadata will display).
 */
$tfish_metadata->template = 'test.html';
// $tfish_metadata->title = '';
// $tfish_metadata->description = '';
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
// $tfish_metadata->robots = '';

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";
