<?php

/**
 * Password reset script.
 *
 * Allows the administrative password to be changed.
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
require_once "../mainfile.php";
require_once TFISH_ADMIN_PATH . "tfish_admin_header.php";

// Specify theme set, otherwise 'default' will be used.
$tfish_template->setTheme('admin');

// Validate input parameters. Note that passwords are not sanitised in any way.
$op = isset($_REQUEST['op']) ? TfishDataValidator::trimString($_REQUEST['op']) : false;
$dirty_password = isset($_POST['password']) ? $_POST['password'] : false;
$dirty_confirmation = isset($_POST['confirmpassword']) ? $_POST['confirmpassword'] : false;
$clean_token = isset($_POST['token']) ? TfishDataValidator::trimString($_POST['token']) : '';

// Display a passord reset form, or the results of a submission.
if (in_array($op, array('submit', false))) {
    switch ($op) {
        case "submit":
            TfishSession::validateToken($clean_token); // CSRF check.
            $error = [];
            $password_quality = [];

            // Get the admin user details.
            $user_id = (int) $_SESSION['user_id'];
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
            
            if ($password_quality['strong'] === false) {
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
                $password_hash = TfishSecurityUtility::recursivelyHashPassword($dirty_password, 
                        100000, TFISH_SITE_SALT, $user['user_salt']);
                $tfish_template->back_url = 'admin.php';
                    $tfish_template->form = TFISH_FORM_PATH . "response.html";

                if ($password_hash) {
                    $result = TfishDatabase::update('user', $user_id, 
                            array('password_hash' => $password_hash));
                    
                    // Display response.
                    $tfish_template->back_url = 'admin.php';
                    $tfish_template->form = TFISH_FORM_PATH . "response.html";
                    
                    if ($result) {
                        $tfish_template->page_title = TFISH_SUCCESS;
                        $tfish_template->alert_class = 'alert-success';
                        $tfish_template->message = TFISH_PASSWORD_CHANGED_SUCCESSFULLY;
                        
                    } else {
                        $tfish_template->page_title = TFISH_FAILED;
                        $tfish_template->alert_class = 'alert-danger';
                        $tfish_template->message = TFISH_PASSWORD_CHANGE_FAILED;
                    }
                    
                    $tfish_template->tfish_main_content = $tfish_template->render('form');
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
