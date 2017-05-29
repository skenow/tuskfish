<?php

/**
 * Provides a variety of utility functions used by the core (lists of timezones, mimetypes etc).
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @since		1.3
 * @author		marcan <marcan@impresscms.org>
 * @version		SVN: $Id: Utils.php 11448 2011-11-21 16:37:13Z fiammy $
 */
if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishUtils
{

    /**
     * Return a list of mimetypes.
     * 
     * This list is not exhaustive, but it does cover most things that a sane person would want.
     * Feel free to add more if you wish, but do NOT use this as a whitelist of permitted mimetypes,
     * it is just a reference.
     * 
     * @return array of mimetypes with extension as key.
     * @copyright	The ImpressCMS Project http://www.impresscms.org/
     * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
     * @author		marcan <marcan@impresscms.org>
     */
    public static function getMimetypes()
    {
        return array(
            "hqx" => "application/mac-binhex40",
            "doc" => "application/msword",
            "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "dot" => "application/msword",
            "bin" => "application/octet-stream",
            "lha" => "application/octet-stream",
            "lzh" => "application/octet-stream",
            "exe" => "application/octet-stream",
            "class" => "application/octet-stream",
            "so" => "application/octet-stream",
            "dll" => "application/octet-stream",
            "pdf" => "application/pdf",
            "ai" => "application/postscript",
            "eps" => "application/postscript",
            "ps" => "application/postscript",
            "smi" => "application/smil",
            "smil" => "application/smil",
            "wbxml" => "application/vnd.wap.wbxml",
            "wmlc" => "application/vnd.wap.wmlc",
            "wmlsc" => "application/vnd.wap.wmlscriptc",
            "odt" => "application/vnd.oasis.opendocument.text",
            "xla" => "application/vnd.ms-excel",
            "xls" => "application/vnd.ms-excel",
            "xlt" => "application/vnd.ms-excel",
            "ppt" => "application/vnd.ms-powerpoint",
            "csh" => "application/x-csh",
            "dcr" => "application/x-director",
            "dir" => "application/x-director",
            "dxr" => "application/x-director",
            "spl" => "application/x-futuresplash",
            "gtar" => "application/x-gtar",
            "php" => "application/x-httpd-php",
            "php3" => "application/x-httpd-php",
            "php4" => "application/x-httpd-php",
            "php5" => "application/x-httpd-php",
            "phtml" => "application/x-httpd-php",
            "js" => "application/x-javascript",
            "sh" => "application/x-sh",
            "swf" => "application/x-shockwave-flash",
            "sit" => "application/x-stuffit",
            "tar" => "application/x-tar",
            "tcl" => "application/x-tcl",
            "xhtml" => "application/xhtml+xml",
            "xht" => "application/xhtml+xml",
            "xhtml" => "application/xml",
            "ent" => "application/xml-external-parsed-entity",
            "dtd" => "application/xml-dtd",
            "mod" => "application/xml-dtd",
            "gz" => "application/x-gzip",
            "zip" => "application/zip",
            "au" => "audio/basic",
            "snd" => "audio/basic",
            "mid" => "audio/midi",
            "midi" => "audio/midi",
            "kar" => "audio/midi",
            "mp1" => "audio/mpeg",
            "mp2" => "audio/mpeg",
            "mp3" => "audio/mpeg",
            "aif" => "audio/x-aiff",
            "aiff" => "audio/x-aiff",
            "m3u" => "audio/x-mpegurl",
            "ram" => "audio/x-pn-realaudio",
            "rm" => "audio/x-pn-realaudio",
            "rpm" => "audio/x-pn-realaudio-plugin",
            "ra" => "audio/x-realaudio",
            "wav" => "audio/x-wav",
            "bmp" => "image/bmp",
            "gif" => "image/gif",
            "jpeg" => "image/jpeg",
            "jpg" => "image/jpeg",
            "jpe" => "image/jpeg",
            "png" => "image/png",
            "tiff" => "image/tiff",
            "tif" => "image/tif",
            "wbmp" => "image/vnd.wap.wbmp",
            "pnm" => "image/x-portable-anymap",
            "pbm" => "image/x-portable-bitmap",
            "pgm" => "image/x-portable-graymap",
            "ppm" => "image/x-portable-pixmap",
            "xbm" => "image/x-xbitmap",
            "xpm" => "image/x-xpixmap",
            "ics" => "text/calendar",
            "ifb" => "text/calendar",
            "css" => "text/css",
            "html" => "text/html",
            "htm" => "text/html",
            "asc" => "text/plain",
            "txt" => "text/plain",
            "rtf" => "text/rtf",
            "sgml" => "text/x-sgml",
            "sgm" => "text/x-sgml",
            "tsv" => "text/tab-seperated-values",
            "wml" => "text/vnd.wap.wml",
            "wmls" => "text/vnd.wap.wmlscript",
            "xsl" => "text/xml",
            "mpeg" => "video/mpeg",
            "mpg" => "video/mpeg",
            "mpe" => "video/mpeg",
            "mp4" => "video/mp4",
            "qt" => "video/quicktime",
            "mov" => "video/quicktime",
            "avi" => "video/x-msvideo",
        );
    }

    /**
     * Provide a list of timezone offsets.
     * 
     * @return array of timezone offsets
     */
    public static function getTimezones()
    {
        return array(
            '-12' => 'UTC-12:00',
            '-11' => 'UTC-11:00',
            '-10' => 'UTC-10:00',
            '-9.5' => 'UTC-9:30',
            '-9' => 'UTC-9:00',
            '-8' => 'UTC-8:00',
            '-7' => 'UTC-7:00',
            '-6' => 'UTC-6:00',
            '-5' => 'UTC-5:00',
            '-4' => 'UTC-4:00',
            '-3.5' => 'UTC-3:30',
            '-3' => 'UTC-3:00',
            '-2' => 'UTC-2:00',
            '-1' => 'UTC-1:00',
            '0' => 'UTC',
            '1' => 'UTC+1:00',
            '2' => 'UTC+2:00',
            '3' => 'UTC+3:00',
            '3.5' => 'UTC+3:30',
            '4' => 'UTC+4:00',
            '4.5' => 'UTC+4:30',
            '5' => 'UTC+5:00',
            '5.5' => 'UTC+5:30',
            '5.75' => 'UTC+5:45',
            '6' => 'UTC+6:00',
            '6.5' => 'UTC+6:30',
            '7' => 'UTC+7:00',
            '8' => 'UTC+8:00',
            '8.75' => 'UTC+8:45',
            '9' => 'UTC+9:00',
            '9.5' => 'UTC+9:30',
            '10' => 'UTC+10:00',
            '10.5' => 'UTC+10:30',
            '11' => 'UTC+11:00',
            '12' => 'UTC+12:00',
        );
    }

}
