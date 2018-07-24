<?php

/**
 * Front end controller script for SOMEMODULE.
 *
 * Extended description of script goes here.
 * 
 * @copyright   Your name 2018+ (https://yoursite.com)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Your name <you@email.com>
 * @since       1.0
 * @package     SOMEMODULE
 */
// Enable strict type declaration.
declare(strict_types=1);

// Boot! Set file paths, preferences and connect to database.
require_once "../mainfile.php";
require_once TFISH_ADMIN_PATH . "tf_admin_header.php";
require_once TFISH_MODULE_PATH . "content/tf_content_header.php";

// Specify the admin theme you want to use.
$tf_template->setTheme('admin');

/**
 * Validate input parameters here.
 **/

// Permitted options.
$op = isset($_REQUEST['op']) ? $tf_validator->trimString($_REQUEST['op']) : false;
$options_whitelist = array();

if (in_array($op, $options_whitelist, true)) {
    exit;
}
    
// Cross-site request forgery check.
if (!in_array($op, $options_whitelist, true)) {
    TfSession::validateToken($clean_token);
}

// Business logic goes here.
switch ($op) {
    // Various cases.
}

/**
 * Override page template here (otherwise default site metadata will display).
 */
// $tf_metadata->setTitle('');
// $tf_metadata->setDescription('');
// $tf_metadata->setAuthor('');
// $tf_metadata->setCopyright('');
// $tf_metadata->setGenerator('');
// $tf_metadata->setSeo('');
$tf_metadata->setRobots('noindex,nofollow');

// Include page template and flush buffer
require_once TFISH_PATH . "tf_footer.php";