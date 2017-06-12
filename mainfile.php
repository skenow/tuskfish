<?php

/**
 * Tuskfish mainfile script.
 * 
 * Includes critical files and configuration information. Must be included in ALL pages as first
 * order of business.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since		1.0
 * @package		core
 */
/**
 * Access trust path, DB credentials and read preferences.
 * All paths MUST end with a trailing slash /
 * If you are using SSL, you MUST specify https in the TFISH_URL
 * 
 * Example paths (yours may vary):
 * TFISH_ROOT_PATH is the file path to your web root, eg: /home/youraccount/public_html/
 * TFISH_TRUST_PATH is the file path to your trust_path directory, which should sit outside of
 * your web root if possible, eg.: /home/youraccount/trust_path/
 * TFISH_URL is simply your domain, eg: http://yourdomain.com/
 * 
 * After configuring the paths you should set the access permissions for this file (CHMOD) to 0400.
 * 
 * If you have problems please see the installation section of the Tuskfish User Manual for a
 * more detailed explanation. 
 */
////////////////////////////////////////////////////////////
////////// You must configure the following paths //////////
////////////////////////////////////////////////////////////
define("TFISH_ROOT_PATH", "");
define("TFISH_TRUST_PATH", "");
define("TFISH_URL", "");
////////////////////////////////////////////////////////////
//////////////////// End configuration /////////////////////
////////////////////////////////////////////////////////////

define("TFISH_PATH", TFISH_TRUST_PATH . "libraries/tuskfish/");
define("TFISH_CONFIGURATION_PATH", TFISH_PATH . "configuration/config.php");

// Include critical files
require_once(TFISH_CONFIGURATION_PATH);

/**
 * Autoload core Tuskfish classes. spl_autoload_register() avoids namespace clashes.
 * @param string $classname Name of class to autoload. 
 */
function tfish_autoload($classname) {
    include TFISH_CLASS_PATH . $classname . '.php';
}
