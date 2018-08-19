<?php

/**
 * TfDataProtocols trait file.
 * 
 * Provides common sensor data protocol definitions.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.1
 * @package     core
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Provides definition of permitted sensor data protocols.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1
 * @package     machines
 * 
 */
trait TfDataProtocols
{
    /**
     * Returns a whitelist of permitted data protocols used by remote sensors.
     * 
     * Used in the sensor entry/editing form.
     * 
     * @return array Array of whitelisted (permitted) data protocols.
     */
    public function getDataProtocols()
    {
        return array(
            'MQTT' => TFISH_MACHINE_MQTT,
            'HTTP' => TFISH_MACHINE_HTTP,
            'COaP' => TFISH_MACHINE_COAP,
        );
    }
}
