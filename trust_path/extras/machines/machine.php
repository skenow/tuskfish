<?php

/**
 * Tuskfish machine interface (experimental).
 * 
 * Handles incoming requests from remote machines and sensors. For experimental purposes the script
 * will attempt to log data from a temperature sensor on a remote Arduino.
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     content
 */
// Access trust path, DB credentials and preferences. This file must be included in *ALL* pages.
require_once "mainfile.php";
require_once TFISH_PATH . "tfish_header.php";

// Get the relevant handler.
$content_handler = 'TfishMachineHandler';

// Validate input parameters.
$clean_machine_id = isset($_POST['machine_id']) ? (int) $_POST['machine_id'] : 0;
$clean_temperature = isset($_POST['temperature']) ? (float) $_POST['temperature'] : 0;

/**
 * Authenticate machine and / or sensor; assure integrity of incoming requests and data points.
 * 
 * This requires a bit of thought since many IOT devices do not have sufficient resources to
 * undertake robust crypto. For example, the Arduino does not have an acceptable RNG and the
 * ethernet shield accessory can't do SSL. Mobile phones can though.
 * 
 * HMAC may be a viable options (I think there is a library, but don't know how reliable it is).
 * A pre-shared key (and a cache of data points) could be stored on the Ethernet shield's SD card.
 * However, the possibility of the key being lifted by someone with physical access can't be
 * discounted, so machines that don't have protected physical access should not be allowed to do
 * anything risky.
 * 
 * There is some talk of developing lightweight crypto standards and libraries for small devices
 * but it hasn't really progressed far yet.
 * 
 * As a temporary measure for testing purposes only, set a random integer here to prevent spamming
 * of the script.
 */
$authorised_machine = '';

// If the machine is authorised to submit data, log the data point to a dedicated data table.
if ($clean_machine_id === $authorised_machine && $clean_temperature) {
    // Log data point to database.
}

require_once TFISH_PATH . "tfish_footer.php";