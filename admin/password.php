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
require_once TFISH_ADMIN_PATH . "tf_admin_header.php";

// Specify theme set, otherwise 'default' will be used.
$tf_template->setTheme('admin');

// Validate input parameters. Note that passwords are not sanitised in any way.
$op = isset($_REQUEST['op']) ? $tf_validator->trimString($_REQUEST['op']) : false;
$dirty_password = isset($_POST['password']) ? $_POST['password'] : false;
$dirty_confirmation = isset($_POST['confirmpassword']) ? $_POST['confirmpassword'] : false;
$clean_token = isset($_POST['token']) ? $tf_validator->trimString($_POST['token']) : '';

// Display a passord reset form, or the results of a submission.
if (in_array($op, array('submit', false), true)) {
    switch ($op) {
        case "submit":
            TfSession::validateToken($clean_token); // CSRF check.
            $error = [];
            $password_quality = [];

            // Get the admin user details.
            $user_id = (int) $_SESSION['user_id'];
            $statement = $tf_database->preparedStatement("SELECT * FROM `user` WHERE `id` = :id");
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
            $security_utility = new TfSecurityUtility();
            $password_quality = $security_utility->checkPasswordStrength($dirty_password);
            
            if ($password_quality['strong'] === false) {
                unset($password_quality['strong']);
                foreach ($password_quality as $key => $problem) {
                    $error[] = $problem;
                }
            }

            // Display errors.
            if (!empty($error)) {
                $tf_template->report = $error;
                $tf_template->form = TFISH_FORM_PATH . "change_password.html";
                $tf_template->tf_main_content = $tf_template->render('form');
            }

            /**
             * All good: Calculate the password hash and update the user table.
             */
            if (empty($error)) {
                $password_hash = '';
                $password_hash = TfSession::recursivelyHashPassword($dirty_password, 
                        100000, TFISH_SITE_SALT, $user['user_salt']);
                $tf_template->back_url = 'admin.php';
                    $tf_template->form = TFISH_FORM_PATH . "response.html";

                if ($password_hash) {
                    $result = $tf_database->update('user', $user_id, 
                            array('password_hash' => $password_hash));
                    
                    // Display response.
                    $tf_template->back_url = 'admin.php';
                    $tf_template->form = TFISH_FORM_PATH . "response.html";
                    
                    if ($result) {
                        $tf_template->page_title = TFISH_SUCCESS;
                        $tf_template->alert_class = 'alert-success';
                        $tf_template->message = TFISH_PASSWORD_CHANGED_SUCCESSFULLY;
                        
                    } else {
                        $tf_template->page_title = TFISH_FAILED;
                        $tf_template->alert_class = 'alert-danger';
                        $tf_template->message = TFISH_PASSWORD_CHANGE_FAILED;
                    }
                    
                    $tf_template->tf_main_content = $tf_template->render('form');
                }
            }
            break;

        default:
            $tf_template->form = TFISH_FORM_PATH . "change_password.html";
            $tf_template->tf_main_content = $tf_template->render('form');
            break;
    }
}

// Assign to template.
$tf_template->page_title = TFISH_CHANGE_PASSWORD;
$tf_metadata->setRobots('noindex,nofollow');

// Include page template and flush buffer
require_once TFISH_PATH . "tf_footer.php";
