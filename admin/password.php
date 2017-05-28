<?php

/**
* Tuskfish content management script. Add, edit or delete content objects as required.
*
* This is the core of the administrative system.
* 
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "../mainfile.php";
require_once TFISH_ADMIN_PATH . "tfish_admin_header.php";

// Specify template set, otherwise 'default' will be used.
$tfish_template->setTemplate('admin');

// Validate input parameters. Note that passwords are not sanitised in any way.
$op = isset($_REQUEST['op']) ? TfishFilter::trimString($_REQUEST['op']) : false;
$dirty_password = isset($_POST['password']) ? $_POST['password'] : false;
$dirty_confirmation = isset($_POST['confirmpassword']) ? $_POST['confirmpassword'] : false;

// Display a passord reset form, or the results of a submission.
if (in_array($op, array('submit', false))) {

	switch ($op) {
		case "submit":
			
			$error = [];
			$password_quality = [];
			
			// Get the admin user details.
			$user_id = (int)$_SESSION['user_id'];
			$statement = TfishDatabase::preparedStatement("SELECT * FROM `user` WHERE `id` = :id");
			$statement->bindParam(':id', $user_id, PDO::PARAM_INT);
			$statement->execute();
			$user = $statement->fetch(PDO::FETCH_ASSOC);
			
			// Make sure that the user salt is available otherwise the hash will be weak.
			if (empty($user) || empty($user['user_salt'])) {
				$error[] = TFISH_USER_SALT_UNAVAILABLE;
			}
			
			// Check both password and confirmation submitted.
			if (empty($dirty_password) || empty($dirty_confirmation)) {
				$error[] = TFISH_ENTER_PASSWORD_TWICE;
			}
			
			// Check that password and confirmation match.
			if ($dirty_password !== $dirty_confirmation) {
				$error[] = TFISH_PASSWORDS_DO_NOT_MATCH;
			}
			
			// Check that password meets minimum strength requirements.
			$password_quality = TfishSecurityUtility::checkPasswordStrength($dirty_password);
			if ($password_quality['strong'] == false) {
				unset($password_quality['strong']);
				foreach ($password_quality as $key => $problem) {
					$error[] = $problem;
				}
			}
			
			// Display errors.
			if (!empty($error)) {			
				$tfish_template->report = $error;
				$tfish_template->form = TFISH_FORM_PATH . "change_password.html";
				$tfish_template->tfish_main_content = $tfish_template->render('form'); 
			}
			
			/**
			 * All good: Calculate the password hash and update the user table.
			 */
			
			if (empty($error)) {
				$password_hash = '';
				$password_hash = TfishSecurityUtility::recursivelyHashPassword($dirty_password, 100000,
						TFISH_SITE_SALT, $user['user_salt']);

				if ($password_hash) {
					$result = TfishDatabase::update('user', $user_id, array('password_hash' => $password_hash));
					if ($result) {
						$tfish_template->tfish_main_content = '<p>' . TFISH_PASSWORD_CHANGED_SUCCESSFULLY . '</p>';
					} else {
						$tfish_template->tfish_main_content = '<p>' . TFISH_PASSWORD_CHANGE_FAILED . '</p>';
					}
				}
			}
		break;
	
		default:
			$tfish_template->form = TFISH_FORM_PATH . "change_password.html";
			$tfish_template->tfish_main_content = $tfish_template->render('form'); 
		break;
	}
}

// Assign to template.
$tfish_template->page_title = TFISH_CHANGE_PASSWORD;
$tfish_metadata->robots = 'noindex,nofollow';

// Include page template and flush buffer
require_once TFISH_PATH . "tfish_footer.php";