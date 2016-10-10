<?php

/**
* Tuskfish footer script, must be included on every page.
* 
* Includes the main layout template, kills the database connection and flushes the output buffer.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

// Include the relevant page template, or the default if not set.
if ($tfish_metadata && $tfish_metadata->template) {
	include_once TFISH_TEMPLATES_PATH . $tfish_metadata->template;
} else {
	include_once TFISH_TEMPLATES_PATH . "default.html";
}

// Close the database connection
TfishDatabase::close();

// Flush the output buffer to screen
ob_end_flush();