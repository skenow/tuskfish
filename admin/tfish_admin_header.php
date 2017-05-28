<?php

/**
 * Tuskfish ADMIN header script, MUST be included on every ADMIN page.
 * 
 * Identical to tfish_header.php except that it conducts an admin check and denies access if false.
 *
 * @copyright	Simon Wilkinson (Crushdepth) 2013-2016
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @since		1.0
 * @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
 * @package		core
 */
// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once TFISH_PATH . "tfish_header.php";

// CRITICAL - ADMIN CHECK - DENY ACCESS UNLESS LOGGED IN.
if (!TfishSession::isAdmin()) {
    TfishSession::logout(TFISH_ADMIN_URL . "login.php");
    exit;
}