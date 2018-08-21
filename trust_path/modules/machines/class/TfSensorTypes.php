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
     * Use this whitelist when dynamically instantiating sensor objects. An equivalent class file
     * must be present in the Machine module class directory.
     * 
     * @return array Array of whitelisted (permitted) sensor object types.
     */
    public function getSensorTypes()
    {
        return array(
            'TfSensor' => TFISH_SENSOR_GENERIC,
        );
    }
}
