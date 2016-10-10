<?php

/**
* Tuskfish security utilities class.
* 
* Provides methods to conduct basic security operations such as validating login, hashing passwords etc.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishSecurityUtility
{
	public static function checkLogin($user_object, $password)
	{
		// Check the password word against the user's stored password hash
		// If bad password, increment password attempts, warn and redirect to source page
		// If match, regenerate session ID, set session login flag (and admin flag, if relevant)
		// Allow passing of session IDs via cookies only
		ini_set('session.use_only_cookies', true);
				
		// Generate an additional session token that is passed via URLs
	}
	
	/**
	 * Evaluates the strength of a password to resist brute force cracking
	 * 
	 * @param string $password
	 * @return array 
	 */
	public static function checkPasswordStrength($password)
	{
		$evaluation = array('strong' => true);
		
		// Length must be > 14 characters to prevent brute force search of the keyspace.
		if (mb_strlen($password, 'UTF-8') < 14) {
			$evaluation['strong'] = false;
			$evaluation[] = TFISH_PASSWORD_MINIMUM_LENGTH_WEAKNESS;
		}
		
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
		if (!preg_match('/[0-9]/', $password))	{
			$evaluation['strong'] = false;
			$evaluation[] = TFISH_PASSWORD_NUMBERIC_WEAKNESS;
		}
		
		// Must contain at least one symbol.
		if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
			$evaluation['strong'] = false;
			$evaluation[] = TFISH_PASSWORD_SYMBOLIC_WEAKNESS;
		}
		
		return $evaluation;
	}
	
	/**
	 * Generate a psuedo-random salt of arbitrary length
	 * 
	 * @param type $length
	 * @return string $salt
	 */
	public static function generateSalt($length = 64)
	{
		$salt = mb_substr(base64_encode(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM)), 0, $length, 'UTF-8');
		return $salt;
	}
	
	/**
	 * Recursively hashes a salted password to harden it against dictionary attacks.
	 * 
	 * @param string $password
	 * @param int $iterations
	 * @param string $site_salt
	 * @param string $user_salt (optional)
	 * @return string
	 */
	public static function recursivelyHashPassword($password, $iterations, $site_salt, $user_salt = '')
	{
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