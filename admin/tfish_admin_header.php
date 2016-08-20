<?php

/**
* Tuskfish ADMIN header script, MUST be included on every ADMIN page.
* 
* Identical to tfish_header.php except that it conducts an admin check and denies access if false.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
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

// HTMLPurifier library is used to validate the teaser and description fields of objects.
// It is only available in the admin section of the site. Note that the HTMLPurifier autoloader
// must be registered AFTER the Tfish autoloader - so the tfish_header.php must be included FIRST.
require_once TFISH_LIBRARIES_PATH . 'htmlpurifier/library/HTMLPurifier.auto.php';