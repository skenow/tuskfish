<?php

/**
* Tuskfish configuration script.
* 
* Stores the site salt (used for recursive password hashing), key and database path. Included in
* every page via mainfile.php / masterfile.php  
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

if (!defined("TFISH_SITE_SALT")) define("TFISH_SITE_SALT", "YN1j+i/CnV933fNii0lycDYvQ+HfpGEiK4NB6jk/7sUnfV0SEfEURa3y1ZaAkxd2");
if (!defined("TFISH_KEY")) define("TFISH_KEY", "vkOgkkp2vl27riArGxK486Ei1M2sak4D0neezxJjBYr0Q4LQpdlEXKLZoSno2nK");
if (!defined("TFISH_DATABASE")) define("TFISH_DATABASE", "/home/isengard/public_html/tuskfish/trust_path/database/244542853_tfish11.db");