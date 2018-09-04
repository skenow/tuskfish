<?php

/**
 * Tuskfish admin header script. Must be included on every admin page.
 * 
 * Identical to tfHeader.php except that it conducts an admin check and denies access if false.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     admin
 */
// Enable strict type declaration.
declare(strict_types=1);

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once TFISH_PATH . "tfHeader.php";

/** CRITICAL - ADMIN CHECK - DENY ACCESS UNLESS LOGGED IN. */
if (!TfSession::isAdmin()) {
    TfSession::logout(TFISH_ADMIN_URL . "login.php");
    exit;
}
