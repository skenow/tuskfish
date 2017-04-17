<?php

require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

$tfish_template->setTemplate('marketing');

$content_handler = 'TfishDownloadHandler';
$index_template = 'test';
$target_file_name = 'test';

// Render.
$tfish_template->tfish_main_content = $tfish_template->render($index_template);

require_once TFISH_PATH . "tfish_footer.php";