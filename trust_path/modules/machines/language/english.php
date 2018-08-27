<?php
/**
 * Tuskfish SOMEMODULE language constants (English).
 * 
 * Translate this file to convert the SOMEMODULE module to another language.
 *
 * @copyright   Your Name 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Your Name <you@email.com>
 * @since       1.0
 * @package     language
 */
declare(strict_types=1);

// Divide your constants into logical sections separated by comments
define("TFISH_MACHINE_MACHINES", "Machines");

// Page titles.
define("TFISH_MACHINES", "Machines");
define("TFISH_SENSORS", "Sensors");

// Machine properties.
define("TFISH_MACHINE_LATITUDE", "Latitude (decimal degrees)");
define("TFISH_MACHINE_LONGITUDE", "Longitude (decimal degrees)");
define("TFISH_MACHINE_PROTOCOL", "Protocol");
define("TFISH_MACHINE_PARENT", "Parent machine");
define("TFISH_MACHINE_KEY", "HMAC key");
define("TFISH_MACHINE_TEMPLATE", "Template");
define("TFISH_MACHINE_ICON", "Icon");

// Child and parent objects.
define("TFISH_MACHINE_ONBOARD_SENSORS", "Onboard sensors");
define("TFISH_MACHINE_HOST_MACHINE", "Host machine");

// Data protocols.
define("TFISH_MACHINE_MQTT", "MQTT");
define("TFISH_MACHINE_HTTP", "HTTP");
define("TFISH_MACHINE_COAP", "COaP");

// Actions.
define("TFISH_MACHINE_ADD", "Add");
define("TFISH_MACHINE_EDIT", "Edit machine");
define("TFISH_SENSOR_EDIT", "Edit sensor");

// Errors.
define("TFISH_ERROR_BAD_LATITUDE", "Bad latitude.");
define("TFISH_ERROR_BAD_LONGITUDE", "Bad longitude.");
define("TFISH_ERROR_NOT_SENSOR", "Not a sensor object, or illegal type.");
define("TFISH_ERROR_NOT_MACHINE", "Not a machine object, or illegal type.");

// Sensor types.
define("TFISH_SENSOR_GENERIC", "Generic sensor");
define("TFISH_SENSOR_TEMPERATURE", "Temperature sensor");

