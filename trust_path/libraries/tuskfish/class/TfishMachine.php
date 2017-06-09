<?php

/**
 * Base Tuskfish machine object class.
 * 
 * This class is not yet operational.
 * 
 * Represents a networked computer, sensor or IOT device with which Tuskfish can communicate,
 * either to send instructions and data or to receive them. This class is intended to facilitate
 * interaction between Tuskfish and the IOT, for example to log data or to manage a group of
 * sensors or devices.
 * 
 * Authenticity and integrity of communications is assured through use of HMACs with private keys
 * shared by Tuskfish and remote machines, however they are not private (encrypted). Do not use
 * this interface to transmit confidential information unless you secure the transport layer. 
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		core
 */

/**
 * Requirements
 * 
 * 1. Shared secret key for each device, needs to be stored in machine database table.
 * 2. HMAC authentication and integrity check.
 * 3. Vocabulary (whitelist) of commands that can be sent.
 * 4. Vocabulary (whitelist) of commands that can be received.
 * 5. Authority file for each device - which commands is it allowed to use?
 * 6. Each command should probably have a separate method, to keep the control script clean.
 * 7. TfishMachineHandler class to handle the actual HMAC and signalling.
 */

if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishMachine extends TfishTfishAncestralObject
{

    public function __construct()
    {
        parent::__construct();
        
        /**
         * Whitelist of allowed properties and data types.
         */
        $this->__properties['id'] = 'int'; // Auto-increment, set by database.
        $this->__properties['title'] = 'string'; // Human readable name of this machine.
        $this->__properties['url'] = 'url'; // The URL and port of the remote device.
        $this->__properties['shared_key'] = 'string'; // Used for HMAC authentication / integrity checks
        $this->__properties['allowed_in'] = 'array'; // Must check contents of array against whitelist.
        $this->__properties['allowed_out'] = 'array'; // Must check contents of array against whitelist.
        $this->__properties['request_counter'] = 'array'; // Track the number of requests to mitigate replay attacks.
        $this->__properties['enabled'] = 'int'; // Permit or deny communications with this machine.
        
        /**
         * Set the permitted properties of this object.
         */
        foreach ($this->__properties as $key => $value) {
            $this->__data[$key] = '';
        }

        /**
         * Set default values of permitted properties.
         */
        // Machine communications are disabled by default. You must explicitly enable them to talk
        // to a remote device, for security reasons.
        $this->__data['enabled'] = 0;
    }
    
    /**
     * Set the value of an object property and will not allow non-whitelisted properties to be set.
     * 
     * Intercepts direct calls to set the value of an object property. This method is overridden by
     * child classes to impose data type restrictions and range checks before allowing the property
     * to be set. Tuskfish objects are designed not to trust other components; each conducts its
     * own internal validation checks. 
     * 
     * @param string $property name
     * @param return void
     */
    public function __set($property, $value)
    {
        if (isset($this->__data[$property])) {
            $type = $this->__properties[$property];
            
            // Validate $value against expected data type and business rules.
            switch ($type) {
                
                // allowed_in, allowed_out
                case "array":
                    if ($property == 'allowed_in') {
                        // Whitelist of allowed inbound commands issued by remote machines.
                    }
                    if ($property == 'allowed_out') {
                        // Whitelist of allowed outbound commands to remote machines.
                    }
                    break;
                
                // id, enabled, request_counter.
                case "int":
                    $value = (int) $value;
                    switch ($property) {

                        // 0 or 1.
                        case "enabled":
                            if (TfishFilter::isInt($value, 0, 1)) {
                                $this->__data[$property] = (int) $value;
                            } else {
                                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                            }
                            break;

                        // Minimum value 0.
                        case "request_counter":
                        case "id":
                            if (TfishFilter::isInt($value, 0)) {
                                $this->__data[$property] = (int) $value;
                            } else {
                                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                            }

                            break;
                    }
                    break;                
                
                // title, shared_key.
                case "string":
                    $value = TfishFilter::trimString($value);
                    $this->__data[$property] = TfishFilter::trimString($value);
                    break;
                
                // url
                case "url":
                    $value = TfishFilter::trimString($value);
                    if ($value == "" || TfishFilter::isUrl($value)) {
                        $this->__data[$property] = $value;
                    } else {
                        trigger_error(TFISH_ERROR_NOT_URL, E_USER_ERROR);
                    }
                    break;
            }
        }
    }

}
