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
require_once TFISH_ADMIN_PATH . "tfAdminHeader.php";

// Specify theme set, otherwise 'default' will be used.
$tfTemplate->setTheme('admin');

// Validate input parameters. Note that passwords are not sanitised in any way.
$op = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : false;
$dirtyPassword = $_POST['password'] ?? false;
$dirtyConfirmation = $_POST['confirmPassword'] ?? false;
$cleanToken = isset($_POST['token']) ? $tfValidator->trimString($_POST['token']) : '';

// Display a passord reset form, or the results of a submission.
if (in_array($op, array('submit', false), true)) {
    switch ($op) {
        case "submit":
            TfSession::validateToken($cleanToken); // CSRF check.
            $error = [];
            $passwordQuality = [];

            // Get the admin user details.
            $userId = (int) $_SESSION['userId'];
            $statement = $tfDatabase->preparedStatement("SELECT * FROM `user` WHERE `id` = :id");
            $statement->bindParam(':id', $userId, PDO::PARAM_INT);
            $statement->execute();
            $user = $statement->fetch(PDO::FETCH_ASSOC);

            // Check both password and confirmation submitted.
            if (empty($dirtyPassword) || empty($dirtyConfirmation)) {
                $error[] = TFISH_ENTER_PASSWORD_TWICE;
            }

            // Check that password and confirmation match.
            if ($dirtyPassword !== $dirtyConfirmation) {
                $error[] = TFISH_PASSWORDS_DO_NOT_MATCH;
            }

            // Check that password meets minimum length requirements.
            $passwordQuality = TfUtils::checkPasswordStrength($dirtyPassword);
            
            if ($passwordQuality['strong'] === false) {
                unset($passwordQuality['strong']);
                foreach ($passwordQuality as $key => $problem) {
                    $error[] = $problem;
                }
            }

            // Display errors.
            if (!empty($error)) {
                $tfTemplate->report = $error;
                $tfTemplate->form = TFISH_FORM_PATH . "changePassword.html";
                $tfTemplate->tfMainContent = $tfTemplate->render('form');
            }

            /**
             * All good: Calculate the password hash and update the user table.
             */
            if (empty($error)) {
                $passwordHash = '';
                $passwordHash = TfSession::hashPassword($dirtyPassword);
                $tfTemplate->backUrl = 'admin.php';
                    $tfTemplate->form = TFISH_FORM_PATH . "response.html";

                if ($passwordHash) {
                    $result = $tfDatabase->update('user', $userId, 
                            array('passwordHash' => $passwordHash));
                    
                    // Display response.
                    $tfTemplate->backUrl = 'admin.php';
                    $tfTemplate->form = TFISH_FORM_PATH . "response.html";
                    
                    if ($result) {
                        $tfTemplate->pageTitle = TFISH_SUCCESS;
                        $tfTemplate->alertClass = 'alert-success';
                        $tfTemplate->message = TFISH_PASSWORD_CHANGED_SUCCESSFULLY;
                        
                    } else {
                        $tfTemplate->pageTitle = TFISH_FAILED;
                        $tfTemplate->alertClass = 'alert-danger';
                        $tfTemplate->message = TFISH_PASSWORD_CHANGE_FAILED;
                    }
                    
                    $tfTemplate->tfMainContent = $tfTemplate->render('form');
                }
            }
            break;

        default:
            $tfTemplate->form = TFISH_FORM_PATH . "changePassword.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            break;
    }
}

// Assign to template.
$tfTemplate->pageTitle = TFISH_CHANGE_PASSWORD;
$tfMetadata->setRobots('noindex,nofollow');

// Include page template and flush buffer
require_once TFISH_PATH . "tfFooter.php";
