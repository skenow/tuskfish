<?php

/**
* Tuskfish mainfile script, must be included in all pages
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

//echo '<p>Mainfile included.</p>';

/**
 * Access trust path, DB credentials and read preferences.
 * All paths MUST end with a trailing slash /
 * If you are using SSL, you MUST specify https in the TFISH_URL
 */
define("TFISH_ROOT_PATH", "/home/isengard/public_html/tuskfish/");
define("TFISH_TRUST_PATH", "/home/isengard/public_html/tuskfish/trust_path/");
define("TFISH_URL", "https://tuskfish.biz/");

// Include critical files
require_once(TFISH_TRUST_PATH . "masterfile.php");