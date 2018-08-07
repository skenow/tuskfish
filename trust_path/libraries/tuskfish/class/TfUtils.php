<?php

/**
 * TfUtils class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     utilities
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Provides a variety of utility functions used by the core.
 * 
 * Holds utility functions that return lists of mimetypes, timezone offsets and similar necessary
 * trivia.
 *
 * @copyright   The ImpressCMS Project http://www.impresscms.org/
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @author      marcan <marcan@impresscms.org>
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     utilities
 */
class TfUtils
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

    /**
     * Provide a list of timezone offsets.
     * 
     * @return array Array of timezone offsets.
     */
    public static function getListOfTimezones()
    {
        return array(
            '-12' => 'UTC-12:00',
            '-11' => 'UTC-11:00',
            '-10' => 'UTC-10:00',
            '-9.5' => 'UTC-9:30',
            '-9' => 'UTC-9:00',
            '-8' => 'UTC-8:00',
            '-7' => 'UTC-7:00',
            '-6' => 'UTC-6:00',
            '-5' => 'UTC-5:00',
            '-4' => 'UTC-4:00',
            '-3.5' => 'UTC-3:30',
            '-3' => 'UTC-3:00',
            '-2' => 'UTC-2:00',
            '-1' => 'UTC-1:00',
            '0' => 'UTC',
            '1' => 'UTC+1:00',
            '2' => 'UTC+2:00',
            '3' => 'UTC+3:00',
            '3.5' => 'UTC+3:30',
            '4' => 'UTC+4:00',
            '4.5' => 'UTC+4:30',
            '5' => 'UTC+5:00',
            '5.5' => 'UTC+5:30',
            '5.75' => 'UTC+5:45',
            '6' => 'UTC+6:00',
            '6.5' => 'UTC+6:30',
            '7' => 'UTC+7:00',
            '8' => 'UTC+8:00',
            '8.75' => 'UTC+8:45',
            '9' => 'UTC+9:00',
            '9.5' => 'UTC+9:30',
            '10' => 'UTC+10:00',
            '10.5' => 'UTC+10:30',
            '11' => 'UTC+11:00',
            '12' => 'UTC+12:00',
        );
    }

}
