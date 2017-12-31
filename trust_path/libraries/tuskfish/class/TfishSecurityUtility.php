<?php

/**
 * TfishSecurityUtility class file.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     security
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Security utilities class.
 * 
 * Provides methods to conduct basic security operations such as generating salts and hashing
 * passwords etc.
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     security
 */
class TfishSecurityUtility
{

    /**
     * Evaluates the strength of a password to resist brute force cracking.
     * 
     * Issues warnings if deficiencies are found. Requires a minimum length of 15 characters.
     * 
     * @param string $password Input password.
     * @return array Array of evaluation warnings as strings.
     */
    public static function checkPasswordStrength(string $password)
    {
        $evaluation = array('strong' => true);

        // Length must be > 15 characters to prevent brute force search of the keyspace.
        if (mb_strlen($password, 'UTF-8') < 15) {
            $evaluation['strong'] = false;
            $evaluation[] = TFISH_PASSWORD_MINIMUM_LENGTH_WEAKNESS;
        }
        /**
        // Must contain at least one upper case letter.
        if (!preg_match('/[A-Z]/', $password)) {
            $evaluation['strong'] = false;
            $evaluation[] = TFISH_PASSWORD_UPPER_CASE_WEAKNESS;
        }

        // Must contain at least one lower case letter.
        if (!preg_match('/[a-z]/', $password)) {
            $evaluation['strong'] = false;
            $evaluation[] = TFISH_PASSWORD_LOWER_CASE_WEAKNESS;
        }

        // Must contain at least one number.
        if (!preg_match('/[0-9]/', $password)) {
            $evaluation['strong'] = false;
            $evaluation[] = TFISH_PASSWORD_NUMBERIC_WEAKNESS;
        }

        // Must contain at least one symbol.
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $evaluation['strong'] = false;
            $evaluation[] = TFISH_PASSWORD_SYMBOLIC_WEAKNESS;
        }
         */

        return $evaluation;
    }

    /**
     * Generate a psuedo-random salt of arbitrary length.
     * 
     * This is used to salt user passwords, to make them more difficult to brute force crack.
     * 
     * @param int $length Length of required salt.
     * @return string $salt
     */
    public static function generateSalt(int $length = 64)
    {
        /**
         * mcrypt was Deprecated in PHP 7.2.
         *
         * $salt = mb_substr(base64_encode(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM)), 0, $length,
         *         'UTF-8');
         */
        
        $salt = base64_encode(random_bytes($length));
        
        return $salt;
    }

    /**
     * Recursively hashes a salted password to harden it against dictionary attacks.
     * 
     * Recursively hashing a password a large number of times directly increases the amount of
     * effort that must be spent to brute force or even dictionary attack a hash, because each
     * attempt will consume $iterations more cycles. 
     * 
     * @param string $password Input password.
     * @param int $iterations Number of iterations to run, you want this to be a large number
     * (100,000 or more).
     * @param string $site_salt The Tuskfish site salt, found in the configuration file.
     * @param string $user_salt The user-specific salt for this user, found in the user database
     * table.
     * @return string Password hash.
     */
    public static function recursivelyHashPassword(string $password, int $iterations,
            string $site_salt, string $user_salt = '')
    {

        $iterations = (int) $iterations;

        // Force a minimum number of iterations (1).
        $iterations = $iterations > 0 ? $iterations : 1;

        $password = $site_salt . $password;
        
        if ($user_salt) {
            $password .= $user_salt;
        }
        
        for ($i = 0; $i < $iterations; $i++) {
            $password = hash('sha256', $password);
        }
        return $password;
    }

}
