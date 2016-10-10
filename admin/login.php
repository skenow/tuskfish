<?php

/**
* Tuskfish login script.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

require_once "../mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// Page title.
$tfish_template->page_title = TFISH_LOGIN;

// Initialise and whitelist allowed parameters
$clean_op = $clean_email = $dirty_password = false;
$allowed_options = array("login", "logout", "");

// Collect and sanitise parameters. Note that password is NOT sanitised and therefore it is dangerous.
if (!empty($_POST['op'])) {
	$op = TfishFilter::trimString($_POST['op']);
	$clean_op = TfishFilter::isAlpha($op) ? $op : false;
} elseif (!empty($_GET['op'])) {
	$op = TfishFilter::trimString($_GET['op']);
	$clean_op = TfishFilter::isAlpha($op) ? $op : false;
}
if (isset($_POST['email'])) {
	$email = TfishFilter::trimString($_POST['email']);
	$clean_email = TfishFilter::isEmail($email) ? $email : false;
}
$dirty_password = isset($_POST['password']) ? $_POST['password'] : false;

if (isset($clean_op) && in_array($clean_op, $allowed_options)) {
	switch ($clean_op) {
		case "login":
			TfishSession::login($clean_email, $dirty_password);
		break;

		case "logout":
			TfishSession::logout(TFISH_ADMIN_URL . 'login.php');
		break;

		// Display the login form or a logout link, depending on whether the user is signed in or not
		default:
			$tfish_template->tfish_main_content = $tfish_template->render('login');
		break;
	}
} else {
	// Bad input, do nothing
	exit;
}

/**
 * Override page template and metadata here (otherwise default site metadata will display).
 */
$tfish_metadata->title = TFISH_LOGIN;
$tfish_metadata->description = TFISH_LOGIN_DESCRIPTION;
// $tfish_metadata->author = '';
// $tfish_metadata->copyright = '';
// $tfish_metadata->generator = '';
// $tfish_metadata->seo = '';
$tfish_metadata->robots = 'noindex,nofollow';
//$tfish_metadata->template = 'admin.html';

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";