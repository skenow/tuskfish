<?php

/**
 * TfishMachineHandler class file.
 * 
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		machine
 */

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Handler class for machine objects.
 * 
 * This class is not yet operational.
 * 
 * Handles interaction with remote machines and IOT devices, including authentication and integrity
 * checks via HMAC.
 * 
 * Commands are authenticated via calculation of SHA256 HMAC with preshared, per-device keys. 
 * This allows the integrity and authenticity of the request to be validated, but the request is not
 * encrypted (ie. it is not private). Replay attacks are guarded against by inclusion of a counter
 * (tracked by the server), timestamp and a random string of crap (RSOC) in the the request, which 
 * ensures that each request has a unique HMAC fingerprint.
 * 
 * However, some devices (eg. Arduino) may not be capable of supplying all of these credentials due
 * to resource limitations, so the challenge tests are arranged in a semi-independent manner; 
 * you can comment out particular tests if your remote device can't handle them. Of course, this 
 * reduces security. As a minimum: Use the client_id, HMAC and counter or timestamp. The random 
 * text is highly desirable. If your client device can't generate adequate randomness, consider 
 * pre-generating a large chunk of it it on a more capable device and storing it on the client (in
 * this case, you MUST discard random data as you use it. NEVER re-use it).
 * 
 * Key length: Use a 256 byte key for optimum security. Shorter keys weaken security. There is 
 * no evidence that longer keys improve it. 256 is what the algorithm uses internally.
 * 
 * Randomness: Get your key from a high quality random source, such as GRC's perfect passwords page.
 * Be aware that random number generation on small devices (notably Arduino) is often of poor
 * quality and unacceptable for security purposes. https://www.grc.com/passwords.htm
 * 
 * Data length: The overall length of device-supplied data used to calculate the HMAC should be at 
 * least 256 characters for security reasons. So you may want to adjust the length of your RSOC
 * accordingly.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		machine
 */
class TfishMachineHandler
{

    // Check the authenticity and integrity of a signal.
    protected static function authenticate($dirty_signal)
    {
		$this->validateData();
		$this->checkAuthorised();
		$this->checkExpired();
		$this->checkCounter();
		$this->checkHmac();
		$this->authenticated = $this->check_sanity();
		if ($this->authenticated == true) {
			$this->executeCommand($clean_command);
		}
    }

    // Check if a machine is authorised to execute a particular command.
    protected static function checkAuthorised($id, $command)
    {
        
    }
    
    // Determines if a request timestamp has expired.
    protected static function checkExpired($timestamp) {
        
    }
    
    // Checks if a request has expired based on the machine's counter, to guard against replay
    // attacks.
    protected static function checkRequestCounter($id, $counter)
    {
        
    }

    
    // Execute a whitelisted command. Should only be called after authentication passed.
    protected static function executeCommand()
    {
        
    }

    /**
     * Count TfishMachine objects, optionally matching conditions specified with a TfishCriteria
     * object.
     * 
     * @param object $criteria TfishCriteria object
     * @return int $count
     */
    protected static function getCount($criteria)
    {
        
    }
    
    /**
     * Get TfishMachine objects, optionally matching conditions specified with a TfishCriteria
     * object.
     * 
     * Note that the object type is automatically set, so it is unnecessary to set it when calling
     * TfishVideoHandler::getObjects($criteria). However, if you want to use the generic handler
     * TfishContentHandler::getObjects($criteria) then you do need to specify the object type,
     * otherwise you will get all types of content returned. It is acceptable to use either handler,
     * although good practice to use the type-specific one when you know you want a specific kind of
     * object.
     * 
     * @param object $criteria TfishCriteria object
     * @return array $objects TfishMachine objects
     */
    protected static function getObjects($criteria)
    {
        
    }
    
    /**
	 * Checks if the remote command HMAC matches that calculated by Tuskfish using pre-shared key
	 * 
	 * The remote device supplies a HMAC of the command parameters calculated using a pre-shared
     * key. Also calculates the HMAC locally. If the HMACs match, the integrity and authenticity of
     * the remote command is validated and Tuskfish will execute it. If the HMACs do not match, then
     * the command is discarded as invalid.
	 * 
	 * Note that in order for the HMACs to match, the remote client must use EXACTLY the same data 
	 * in exactly the same order as the Straylight module when calculating the HMAC. You can get the
	 * order from the function below.
	 */
    protected static function checkHmac()
    {
        /**
         * HMAC calculation procedure (must be followed by both Tuskfish and remote device).
         * 
         * 1. Retrieve the shared key.
         * 2. Concatenate signal parameters ($data) in the following order:
         *    - client_id
         *    - command
         *    - request counter
         *    - request timestamp
         *    - random string of crap (RSOC)
         * 
         * 3. Calculate sha256 hash:
         * 
         * $local_hash = hash_hmac('sha256', $data, $key, false);
         * 
         * 4. Extract the first 32 characters (only) of the hash and compare to validate.
         * 
         * $local_hash = mb_substr($hash, 0, 32, "UTF-8");
         * 
         * if ($local_hash == $remote_hash) {
         *     // Good signal: Authenticity and integrity of communication established.
         * } else {
         *     // Bad signal: May be spoofed or modified, discard.
         * }
         * 
         * Note that you still need to check that a machine is enabled and authorised to use a
         * particular command before acting on it.
         * 
         */
    }

    // Return an array of machine IDs and names.
    protected static function getList($criteria)
    {
        
    }

    // Return a single machine object specified by ID.
    protected static function getMachine($id)
    {
        
    }

    // Return multiple machine objects matching $criteria.
    protected static function getMachines($criteria)
    {
        
    }

    // Return a whitelist of permitted commands.
    protected static function getVocabulary()
    {
        return array(
            'closeSite', // Close website.
            'enableCache', // Enable website cache.
            'disableCache', // Disable website cache.
            'flushCache', // Flush website cache.
            'openSite', // Open website.            
        );
    }

    // Insert a new machine object.
    protected static function insert()
    {
        
    }

    // Enable or disable a machine.
    protected static function enable($flag)
    {
        
    }

    // Update a machine object specified by ID.
    protected static function update($id)
    {
        
    }
    
    protected static function updateRequestCounter($id)
    {
        
    }
    
    protected static function validateData()
    {
        
    }

    /////////////////////////////////////
    ////////// Command methods //////////
    /////////////////////////////////////
    
    protected  function closeSite()
    {
        
    }
    
    protected function disableCache()
    {
        
    }
    
    protected function flushCache()
    {
        
    }
    
    protected function openSite()
    {
        
    }
        
}
