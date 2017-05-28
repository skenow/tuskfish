<?php

/**
 * Admin index redirection script.
 * 
 * Redirects directory-level access calls to the main administration page, admin.php.
 *
 * @copyright	Simon Wilkinson (Crushdepth) 2013-2016
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @since		1.0
 * @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
 * @package		core
 */
// Need to start a session in order to use session variables
require_once "../mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

if (TfishSession::isAdmin()) {
    header('location: ' . TFISH_ADMIN_URL . 'admin.php');
    exit;
} else {
    TfishSession::logout();
    exit;
}
