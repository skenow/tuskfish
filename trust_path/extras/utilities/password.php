<?php

/**
 * Manual password reset script.
 * 
 * So you lost the password and got locked out of your site eh? Well, you can use this script to
 * reset it, but you need to pass a couple of tests to demonstrate ownership of the site, namely:
 * 
 * 1. Control of the file system.
 * 2. Access to the database.
 * 
 * Not very convenient I know, but the traditional 'email me a reset link' is horribly insecure.
 * People just don't think about how much of their life is at stake when they choose an email
 * password. Unfortunately, good security practices are usually annoying. So let's grit our teeth
 * and do it the hard way, shall we?
 * 
 * The basic process is to enter your new password (and the existing site and user salts) in the
 * configuration section of this script, then upload it to a webserver and run it in your browser.
 * If all goes well your new password hash will be displayed on screen. Edit your account info in
 * the 'user' table of the database, replacing the old password hash with your new one, and you are
 * good to go.
 * 
 * I recommend that you run this script on a local webserver for obvious reasons. But if your site
 * has SSL then practically speaking, you'll probably get away with running it on your live
 * webserver. Just don't forget to delete this file when you're finished, right?
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */
///////////////////////////////////////////////////////
//////////////////// CONFIGURATION ////////////////////
///////////////////////////////////////////////////////

/**
 * Enter the new password you want to use. Requirements are:
 * 1. More than 15 characters long.
 * 2. At least one upper and lower case letter, number and symbol (!@#$%^& etc). 
 */
$new_password = "";

/**
 * Enter your site salt here. You will find it in the file below: 
 * trust_path/libraries/tuskfish/configuration/config.php
 */
$site_salt = "";

/**
 * Enter your user salt below. You will find it in the 'user' table in your database. You can
 * browse your database using the excelent phpLiteAdmin tool, please see the user manual for how to
 * set it up. You can get phpLiteAdmin from https://www.phpliteadmin.org/
 */
$user_salt = "";

///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
// You filled in all the fields, right?
if (empty($new_password) || empty($site_salt) || empty($user_salt)) {

    echo '<h2>Error(s)</h2>';

    if (empty($new_password)) {
        echo '<p>You forgot to enter the <i>new password</i> in the configuration section of the script.</p>';
    }

    if (empty($site_salt)) {
        echo '<p>You forgot to enter the <i>site salt</i> in the configuration section of the script.</p>';
    }

    if (empty($user_salt)) {
        echo '<p>You forgot to enter the <i>user salt</i> in the configuration section of the script.</p>';
    }

    exit;
}

// Check password strength.
$password_quality = checkPasswordStrength($new_password);
if ($password_quality['strong'] == true) {

    // Salt and iteratively hash the password 100,000 times to resist brute force attacks
    $password_hash = recursivelyHashPassword($new_password, 100000, $site_salt, $user_salt);

    echo '<h2>Here is your new password hash</h2>';
    echo '<p>' . $password_hash . '</p>';
    echo '<p>Edit the "user" table of your database and replace the "password_hash" value with this '
    . 'one; you should then be able to login with your new password.</p>';
    echo '</p>Please see the user manual for instructions on how to edit your database with phpLiteAdmin.</p>';
    echo '<p><b>DELETE this file from your webserver immediately.</b></p>';
} else {

    // Failed password check.
    echo '<h2>Sorry</h3>';
    echo '<p>Password did not meet minimum requirements. Please read the instructions inside this file and try again.</p>';
    unset($password_quality['strong']);
    echo '<ul>';
    foreach ($password_quality as $weakness) {
        echo '<li>' . $weakness . '</li>';
    }
    echo '</ul>';
}

/**
 * Check password strength
 * 
 * Tuskfish requires that passwords should require attackers to search the full keyspace and must
 * be greater than 14 characters to prevent exhaustive brute force searches.
 * 
 * @param string $password
 * @return array $evaluation Array of error messages
 */
function checkPasswordStrength($password) {
    $evaluation = array('strong' => true);

    // Length must be > 14 characters to prevent brute force search of the keyspace.
    if (mb_strlen($password, 'UTF-8') < 15) {
        $evaluation['strong'] = false;
        $evaluation[] = 'Too short. Password must be 15 characters or more.';
    }

    // Must contain at least one upper case letter.
    if (!preg_match('/[A-Z]/', $password)) {
        $evaluation['strong'] = false;
        $evaluation[] = 'Must include at least one upper case letter.';
    }

    // Must contain at least one lower case letter.
    if (!preg_match('/[a-z]/', $password)) {
        $evaluation['strong'] = false;
        $evaluation[] = 'Must include at least one lower case letter.';
    }

    // Must contain at least one number.
    if (!preg_match('/[0-9]/', $password)) {
        $evaluation['strong'] = false;
        $evaluation[] = 'Must include at least one number.';
    }

    // Must contain at least one symbol.
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $evaluation['strong'] = false;
        $evaluation[] = 'Must include at least one non-alphanumeric symbol (!@#$%^&?,;:[] etc).';
    }

    return $evaluation;
}

/**
 * Recursively hashes a salted password to harden it against dictionary attacks.
 * 
 * Recursively hashing a password a large number of times directly increases the amount of
 * effort that must be spent to brute force or even dictionary attack a hash, because each
 * attempt will consume $iterations more cycles. 
 * 
 * @param string $password
 * @param int $iterations Number of iterations to process, you want this to be a large number (100,000 or more).
 * @param string $site_salt Site salt drawn from trust_path/configuration/config.php
 * @param string $user_salt User-specific salt as drawn from the user table
 * @return string
 */
function recursivelyHashPassword($password, $iterations, $site_salt, $user_salt = '') {
    $password = $site_salt . $password;
    if ($user_salt) {
        $password .= $user_salt;
    }
    for ($i = 0; $i < $iterations; $i++) {
        $password = hash('sha256', $password);
    }
    return $password;
}
