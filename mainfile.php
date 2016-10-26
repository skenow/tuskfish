<?php

/**
* Tuskfish mainfile script.
* 
* Includes critical files. Must be included in ALL pages as first order of business.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

/**
 * Access trust path, DB credentials and read preferences.
 * All paths MUST end with a trailing slash /
 * If you are using SSL, you MUST specify https in the TFISH_URL
 */
define("TFISH_ROOT_PATH", "/home/isengard/public_html/tuskfish/");
define("TFISH_TRUST_PATH", "/home/isengard/public_html/tuskfish/trust_path/");
define("TFISH_PATH", TFISH_TRUST_PATH . "libraries/tuskfish/");
define("TFISH_URL", "https://tuskfish.biz/");

// Include critical files
require_once(TFISH_PATH . "configuration/config.php");