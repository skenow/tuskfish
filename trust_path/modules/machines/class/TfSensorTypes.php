<?php

/**
 * TfContentTypes trait file.
 * 
 * Provides common sensor type definition.
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
 * Provides definition of permitted sensor types.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1
 * @package     machines
 * 
 */
trait TfSensorTypes
{
    /**
     * Returns a whitelist of permitted sensor object types, ie. descendants of TfSensor.
     * 
     * Use this whitelist when dynamically instantiating sensor objects. If you create additional
     * types of sensor object (which must be descendants of the TfSensor class) you must add them to
     * the whitelist below. Otherwise their use will be denied in many parts of the Tuskfish system.
     * 
     * @return array Array of whitelisted (permitted) sensor object types.
     */
    public function getSensorTypes()
    {
        return array(
            'TfTemperature' => TFISH_SENSOR_TEMPERATURE,
        );
    }
}
