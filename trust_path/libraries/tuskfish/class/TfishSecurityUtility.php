<?php

/**
 * TfishSecurityUtility class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
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
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
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
     * Due to revision of advice on best practices most requirements have been relaxed, as user
     * behaviour tends to be counter-productive. Basically, it's up to you, the admin, to choose
     * a sane password.
     * 
     * @param string $password Input password.
     * @return array Array of evaluation warnings as strings.
     */
    public function checkPasswordStrength(string $password)
    {
        $evaluation = array('strong' => true);

        // Length must be > 15 characters to prevent brute force search of the keyspace.
        if (mb_strlen($password, 'UTF-8') < 15) {
            $evaluation['strong'] = false;
            $evaluation[] = TFISH_PASSWORD_MINIMUM_LENGTH_WEAKNESS;
        }

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
    public function generateSalt(int $length = 64)
    {        
        $salt = base64_encode(random_bytes($length));
        
        return $salt;
    }
    
}
