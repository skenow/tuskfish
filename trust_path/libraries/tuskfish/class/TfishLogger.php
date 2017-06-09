<?php

/**
 * Tuskfish custom error logger class.
 * 
 * Custom error handler functions such as this one should return FALSE; otherwise calls to 
 * trigger_error($msg, E_USER_ERROR) will not cause a script to stop execution!
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		core
 */
if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishLogger
{

    /**
     * Tuskfish custom error logger class.
     * 
     * Errors are logged to TFISH_ERROR_LOG_PATH (default is /trust_path/log/tuskfish_log.txt). For
     * debugging purpose you can uncomment the last two lines to display errors on screen. Be aware,
     * however, that this will prevent script execution from halting when an error is triggered,
     * which has security implications. You must therefore CLOSE your site via the admin
     * preferences before doing this. Comment the lines back out before re-opening your site.
     * 
     * @param int $errno the level of the error raised
     * @param string $error the error message
     * @param string $file filename
     * @param int $line line number the error was raised at
     * @param array $context active symbol table, ie. an array of every variable in scope when the
     * error was triggered
     */
    public static function logErrors($errno = false, $error = false, $file = false, $line = false, $context = false)
    {
        $message = date("Y-m-d, H:i:s", time()) . ": [ERROR][$errno][$error]";
        $message .= "[$file:$line]\r\n";
        error_log($message, 3, TFISH_ERROR_LOG_PATH);

        // Debug only - comment OUT in production site.
        // echo '<p>' . print($message) . '</p>';
        // Debug only - UNCOMMENT in production site.
        return false;
    }

}
