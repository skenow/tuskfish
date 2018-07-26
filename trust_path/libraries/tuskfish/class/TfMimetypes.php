<?php

/**
 * TfMimetypes trait file.
 * 
 * Provides a list of common (permitted) mimetypes for file uploads.
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
 * TfMimetypes trait.
 * 
 * Provides a list of common (permitted) mimetypes for file uploads.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1
 * @package     core
 * 
 */
trait TfMimetypes
{
    
    /**
     * Returns an array of mimetypes that are permitted for upload to the media directory.
     * 
     * NOTE: Adding HTML to this list would be a BAD IDEA, as such files can include PHP code,
     * although uploaded files have execution permissions removed and are stored outside of the
     * web root in order to prevent direct access by browser. 
     * 
     * @return array Array of permitted mimetypes as file extensions.
     *
     */
    public function getListOfPermittedUploadMimetypes()
    {
        return array(
            "doc" => "application/msword", // Documents.
            "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "pdf" => "application/pdf",
            "ppt" => "application/vnd.ms-powerpoint",
            "pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
            "odt" => "application/vnd.oasis.opendocument.text",
            "ods" => "application/vnd.oasis.opendocument.spreadsheet",
            "odp" => "application/vnd.oasis.opendocument.presentation",
            "xls" => "application/vnd.ms-excel",
            "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "gif" => "image/gif", // Images.
            "jpg" => "image/jpeg",
            "png" => "image/png",
            "mp3" => "audio/mpeg", // Audio.
            "oga" => "audio/ogg",
            "ogg" => "audio/ogg",
            "wav" => "audio/x-wav",
            "mp4" => "video/mp4", // Video.
            "ogv" => "video/ogg",
            "webm" => "video/webm",
            "zip" => "application/zip", // Archives.
            "gz" => "application/x-gzip",
            "tar" => "application/x-tar"
        );
    }
}
