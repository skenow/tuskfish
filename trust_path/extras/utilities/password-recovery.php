<?php

/**
 * Manual password reset script.
 * 
 * So you lost the password and got locked out of your site eh? Well, you can use this script to
 * reset it, but you need to pass a couple of tests to demonstrate ownership of the site, namely,
 * that you have out of band access to the database.
 * 
 * Not very convenient I know, but the traditional 'email me a reset link' is horribly insecure.
 * People just don't think about how much of their life is at stake when they choose an email
 * password. Unfortunately, good security practices are usually annoying. So let's grit our teeth
 * and do it the hard way, shall we?
 * 
 * The basic process is to enter your new password then upload it to a webserver and run it in your
 * browser. If all goes well your new password hash will be displayed on screen. Edit your account
 * info in the 'user' table of the database, replacing the old password hash with your new one, and
 * you are good to go.
 * 
 * I recommend that you run this script on a local webserver for obvious reasons. But if your site
 * has SSL then practically speaking, you'll probably get away with running it on your live
 * webserver. Just don't forget to delete this file when you're finished, right?
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */

// Enable strict type declaration.
declare(strict_types=1);

///////////////////////////////////////////////////////
//////////////////// CONFIGURATION ////////////////////
///////////////////////////////////////////////////////

/**
 * Enter the new password you want to use. The only requirement is that the password is more than
 * 15 characters long.
 */
$newPassword = "";

///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
// You filled in all the fields, right?
if (empty($newPassword)) {

    echo '<h2>Error</h2>';
    echo '<p>You forgot to enter the <i>new password</i> in the configuration section of the script.</p>';
    exit;
}

// Check password strength.
$passwordQuality = checkPasswordStrength($newPassword);
if ($passwordQuality['strong'] === true) {

    // Salt and iteratively hash the password.
    $passwordHash = hashPassword($newPassword);

    echo '<h2>Here is your new password hash</h2>';
    echo '<p>' . $passwordHash . '</p>';
    echo '<p>Edit the "user" table of your database and replace the "passwordHash" value with this '
    . 'one; you should then be able to login with your new password.</p>';
    echo '</p>Please see the user manual for instructions on how to edit your database with phpLiteAdmin.</p>';
    echo '<p><b>DELETE this file from your webserver immediately.</b></p>';
} else {

    // Failed password check.
    echo '<h2>Sorry</h3>';
    echo '<p>Password did not meet minimum requirements. Please read the instructions inside this file and try again.</p>';
    unset($passwordQuality['strong']);
    echo '<ul>';
    foreach ($passwordQuality as $weakness) {
        echo '<li>' . $weakness . '</li>';
    }
    echo '</ul>';
}

/**
 * Check password strength
 * 
 * Tuskfish requires that passwords be minimum length of 15 characters to prevent exhaustive brute 
 * force searches.
 * 
 * @param string $password Password to be evaluated.
 * @return array $evaluation Array of error messages.
 */
function checkPasswordStrength(string $password) {
    $evaluation = array('strong' => true);

    // Length must be > 14 characters to prevent brute force search of the keyspace.
    if (mb_strlen($password, 'UTF-8') < 15) {
        $evaluation['strong'] = false;
        $evaluation[] = 'Too short. Password must be 15 characters or more.';
    }

    return $evaluation;
}

/**
 * Hashes and salts a password to harden it against cracking.
 * 
 * @param string $password Password to be hashed.
 * @return string
 */
function hashPassword(string $password)
{
    $options = array('cost' => 11);        
    $password = password_hash($password, PASSWORD_DEFAULT, $options);

    return $password;
}
