<?php

/**
* Tuskfish custom error logger class.
* 
* Custom error handler functions such as this one should return FALSE; otherwise calls to 
* trigger_error($msg, E_USER_ERROR) will not cause a script to stop execution!
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishLogger
{
	public static function logErrors($errno = false, $error = false, $file = false, $line = false, $context = false)
	{
		$message = date("Y-m-d, H:i:s", time()) . ": [ERROR][$errno][$error]";
		$message .= "[$file:$line]\r\n";
		error_log($message, 3, TFISH_ERROR_LOG_PATH);
		
		// Debug only - comment OUT in production site.
		echo '<p>' . print($message) . '</p>';
		
		// Debug only - UNCOMMENT in production site.
		// return false;
	}
}