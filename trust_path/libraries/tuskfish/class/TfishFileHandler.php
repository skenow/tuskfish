<?php

/**
 * TfishFileHandler class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Tuskfish file handler class.
 *
 * Provides methods for handling common file operations. In some cases, sensitive operations are
 * restricted to a particular directory (for example, file uploads).
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
 */
class TfishFileHandler
{

    /**
     * Returns an array of audio mimetypes that are permitted for content objects.
     * 
     * Note that ogg audio files should use the .oga extension, although the legacy .ogg extension
     * is still acceptable, although it must no longer be used for video files.
     * 
     * @return array Array of permitted audio mimetypes in file extension => mimetype format.
     */
    public static function allowedAudioMimetypes()
    {
        return array(
            "mp3" => "audio/mpeg",
            "oga" => "audio/ogg",
            "ogg" => "audio/ogg",
            "wav" => "audio/x-wav"
        );
    }
    
    /**
     * Returns an array of image mimetypes that are permitted for content objects.
     * 
     * @return array Array of permitted image mimetypes in file extension => mimetype format.
     */
    public static function allowedImageMimetypes()
    {
        return array(
            "gif" => "image/gif",
            "jpg" => "image/jpeg",
            "png" => "image/png"
        );
    }            

    /**
     * Returns an array of video mimetypes that are permitted for upload.
     * 
     * Note that ogg video files must use the .ogv file extension. Please do not use .ogg for
     * video files as this practice has been deprecated in favour of .ogv. While .ogg is still in
     * wide use it is now presumed to refer to audio files only.
     * 
     * @return array Array of permitted video mimetypes in file extension => mimetype format.
     */
    public static function allowedVideoMimetypes()
    {
        return array(
            "mp4" => "video/mp4",
            "ogv" => "video/ogg",
            "webm" => "video/webm"
        );
    }

    /**
     * Append a string to a file.
     * 
     * Do not set the $path using untrusted data sources, such as user input.
     * 
     * @param string $path Path to the target file.
     * @param string $contents Content to append to the target file.
     * @return bool True on success false on failure.
     */
    public static function appendFile(string $path, string $contents)
    {
        // Check for directory traversals and null byte injection.
        if (TfishDataValidator::hasTraversalorNullByte($path)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            return false;
        }
        
        $clean_path = TfishDataValidator::trimString($path);
        // NOTE: Calling trim() removes linefeed from the contents.
        $clean_content = PHP_EOL . TfishDataValidator::trimString($contents);
        
        if ($clean_path && $clean_content) {
            $result = self::_appendFile($clean_path, $clean_content);
            
            if (!$result) {
                trigger_error(TFISH_ERROR_FAILED_TO_APPEND_FILE, E_USER_NOTICE);
                return false;
            }
            
            return true;
        }
        trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_NOTICE);
        return false;
    }

    /** @internal */
    private static function _appendFile(string $path, string $contents)
    {
        return file_put_contents($path, $contents, FILE_APPEND);
    }

    /**
     * Deletes the contents of a specific directory, subdirectories are unaffected.
     * 
     * Do not set the $path using untrusted data sources, such as user input.
     * 
     * @param string $path Path to the target directory.
     * @return bool True on success false on failure.
     */
    
    public static function clearDirectory(string $path)
    {
        // Check for directory traversals and null byte injection.
        if (TfishDataValidator::hasTraversalorNullByte($path)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            return false;
        }
        
        $clean_path = TfishDataValidator::trimString($path);
        
        if (!empty($clean_path)) {
            $result = self::_clearDirectory($clean_path);
            
            if (!$result) {
                trigger_error(TFISH_ERROR_FAILED_TO_DELETE_DIRECTORY, E_USER_NOTICE);
                return false;
            }
            
            return true;
        }
        
        trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_NOTICE);
        
        return false;
    }

    /** @internal */
    private static function _clearDirectory(string $path)
    {
        $resolved_path = self::_dataFilePath($path);
        
        if ($resolved_path) {
            try {
                foreach (new DirectoryIterator($resolved_path) as $file) {
                    if ($file->isFile() && !$file->isDot()) {
                        self::_deleteFile($path . '/' . $file->getFileName());
                    }
                }
            } catch (Exception $e) {
                TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(),
                        $e->getLine());
                return false;
            }
            return true;
        }
        trigger_error(TFISH_ERROR_BAD_PATH, E_USER_NOTICE);
        return false;
    }

    /**
     * Prepends the upload directory path to a file or folder name and checks that the path
     * does not contain directory traversals.
     * 
     * Note that the running script must have executable permissions on all directories in the
     * hierarchy, otherwise realpath() will return FALSE (this is a realpath() limitation).
     *
     * @param string $path Path relative to the data_file directory.
     * @return string|bool Path on success, false on failure.
     */
    private static function _dataFilePath(string $path)
    {
        if (mb_strlen($path, 'UTF-8') > 0) {
            $path = rtrim($path, '/');
            $path = TFISH_UPLOADS_PATH . $path;
            $resolved_path = realpath($path);
            
            // Basically this checks for directory traversals. This is a limited use function and
            // directory traversals are unnecessary. If any are found the input is suspect and
            // rejected.
            if ($path === $resolved_path) {
                return $path; // Path is good.
            } else {
                trigger_error(TFISH_ERROR_BAD_PATH, E_USER_NOTICE);
                return false; // Path is bad.
            }
        }
        
        trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_NOTICE);
        
        return false;
    }

    /**
     * Destroys a directory and all contents recursively relative to the data_file directory.
     * 
     * Do not set the $path using untrusted data sources, such as user input.
     * 
     * @param string $path Path relative to data_file directory.
     * @return bool True on success, false on failure.
     */
    public static function deleteDirectory(string $path)
    {
        // Do not allow the upload, image or media directories to be deleted!
        if (empty($path)) {
            trigger_error(TFISH_ERROR_FAILED_TO_DELETE_DIRECTORY, E_USER_NOTICE);
            return false;
        }
        
        // Check for directory traversals and null byte injection.
        if (TfishDataValidator::hasTraversalorNullByte($path)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            return false;
        }
        
        $clean_path = TfishDataValidator::trimString($path);
        
        if ($clean_path) {
            $result = self::_deleteDirectory($clean_path);
            if (!$result) {
                trigger_error(TFISH_ERROR_FAILED_TO_DELETE_DIRECTORY, E_USER_NOTICE);
                return false;
            }
            
            return true;
        }
        
        trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_NOTICE);
        
        return false;
    }

    /** @internal */
    private static function _deleteDirectory(string $path)
    {
        $path = self::_dataFilePath($path);
        
        if ($path) {
            try {
                $iterator = new RecursiveDirectoryIterator(
                        $path,RecursiveDirectoryIterator::SKIP_DOTS);
                
                foreach (new RecursiveIteratorIterator(
                        $iterator, RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file->isDir()) {
                        rmdir($file->getPathname());
                    } else {
                        unlink($file->getPathname());
                    }
                }
                rmdir($path);
                return true;
            } catch (Exception $e) {
                TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(),
                        $e->getLine());
                return false;
            }
        }
        
        trigger_error(TFISH_ERROR_BAD_PATH, E_USER_NOTICE);
        
        return false;
    }

    /**
     * Destroys an individual file in the data_file directory.
     * 
     * Do not set the $path using untrusted data sources, such as user input.
     * 
     * @param string $path Path relative to the data_file directory.
     * @return bool True on success, false on failure.
     */
    public static function deleteFile(string $path)
    {
        // Check for directory traversals and null byte injection.
        if (TfishDataValidator::hasTraversalorNullByte($path)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            return false;
        }
        
        $clean_path = TfishDataValidator::trimString($path);
        
        if (!empty($clean_path)) {
            $result = self::_deleteFile($clean_path);
            
            if (!$result) {
                trigger_error(TFISH_ERROR_FAILED_TO_DELETE_FILE, E_USER_NOTICE);
                return false;
            }
            
            return true;
        }
        
        trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_NOTICE);
        
        return false;
    }

    /** @internal */
    private static function _deleteFile(string $path)
    {
        $path = self::_dataFilePath($path);
        
        if ($path && file_exists($path)) {
            try {
                unlink($path);
            } catch (Exeption $e) {
                TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(),
                        $e->getLine());
            }
        } else {
            trigger_error(TFISH_ERROR_BAD_PATH, E_USER_NOTICE);
            return false;
        }

        return true;
    }

    /**
     * Returns an array of mimetypes that are permitted for upload to the media directory.
     * 
     * NOTE: Adding HTML to this list would be a BAD IDEA, as such files can include PHP code,
     * although uploaded files have execution permissions removed and are stored outside of the
     * web root in order to prevent direct access by browser. 
     * 
     * @return array Array of permitted mimetypes as file extensions.
     * @todo Move this into a static TfishPreference method.
     *
     */
    public static function getPermittedUploadMimetypes()
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
    
    /**
     * Returns an array of permitted extensions/mimetypes for a given content type.
     * 
     * @param string $type The type (class) of content object.
     * @return array Array of mimetypes as extension => mimetype pairs.
     */
    public static function getTypeMimetypes(string $type)
    {
        $clean_type = TfishDataValidator::trimString($type);

        switch ($clean_type) {
            case "TfishAudio":
                return self::allowedAudioMimetypes();
                break;
            case "TfishImage":
                return self::allowedImageMimetypes();
                break;
            case "TfishVideo":
                return self::allowedVideoMimetypes();
                break;
            default:
                return self::getPermittedUploadMimetypes();
                break;
        }
    }

    /**
     * Initiate streaming of a downloadable media file associated with a content object.
     * 
     * DOES NOT WORK WITH COMPRESSION ENABLED IN OUTPUT BUFFER. This method acts as an intermediary
     * to provide access to uploaded file resources that reside outside of the web root, while
     * concealing the real file path and name. Use this method to provide safe user access to
     * uploaded files. If anything nasty gets uploaded nobody will be able to execute it directly
     * through the browser.
     * 
     * @param int $id ID of the associated content object.
     * @param string $filename An alternative name (rename) for the file you wish to transfer,
     * excluding extension.
     * @return bool True on success, false on failure. 
     */
    public static function sendDownload(int $id, TfishDatabase $tfish_database, string $filename = '')
    {
        $clean_id = TfishDataValidator::isInt($id, 1) ? (int) $id : false;
        $clean_filename = !empty($filename) ? TfishDataValidator::trimString($filename) : '';
        
        if ($clean_id) {
            $result = self::_sendDownload($clean_id, $tfish_database, $clean_filename);
            if ($result === false) {
                return false;
            }
            return true;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_NOTICE);
        }
    }

    /** @internal */
    private static function _sendDownload(int $id, TfishDatabase $tfish_database, string $filename)
    {
        $criteria = new TfishCriteria($this->tfish_database);
        $criteria->add(new TfishCriteriaItem('id', $id));
        $statement = $tfish_database->select('content', $criteria);
        
        if (!$statement) {
            trigger_error(TFISH_ERROR_NO_STATEMENT, E_USER_NOTICE);
            return false;
        }
        
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $content_handler = new TfishContentHandler();
        $content = $content_handler->toObject($row);
        
        if ($content && $content->online) {
            $media = isset($content->media) ? $content->media : false;
            
            if ($media && is_readable(TFISH_MEDIA_PATH . $content->media)) {
                ob_start();
                $file_path = TFISH_MEDIA_PATH . $content->media;
                $filename = empty($filename) ? pathinfo($file_path, PATHINFO_FILENAME) : $filename;
                $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
                $file_size = filesize(TFISH_MEDIA_PATH . $content->media);
                $mimetype_list = TfishUtils::getMimetypes();
                $mimetype = $mimetype_list[$file_extension];

                // Must call session_write_close() first otherwise the script gets locked.
                session_write_close();

                // Prevent caching
                header("Pragma: public");
                header("Expires: -1");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

                // Set file-specific headers.
                header('Content-Disposition: attachment; filename="' . $filename . '.'
                        . $file_extension . '"');
                //header('Content-Type: application/octet-stream');
                header("Content-Type: " . $mimetype);
                header("Content-Length: " . $file_size);
                ob_clean();
                flush();
                readfile($file_path);
            } else {
                return false;
            }
        } else {
            trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_WARNING);
            return false;
        }
    }

    /**
     * Upload a file to the uploads/image or uploads/media directory and set permissions to 644.
     * 
     * @param string $filename Filename.
     * @param string $fieldname Name of form field associated with this upload ('image' or 'media').
     * @return string|bool Filename on success, false on failure.
     */
    public static function uploadFile(string $filename, string $fieldname)
    {
        // Check for directory traversals and null byte injection.
        if (TfishDataValidator::hasTraversalorNullByte($filename)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            exit;
        }
        
        $filename = TfishDataValidator::trimString($filename);
        $clean_filename = mb_strtolower(pathinfo($filename, PATHINFO_FILENAME), 'UTF-8');
        
        // Check that target directory is whitelisted (locked to uploads/image or uploads/media).
        if ($fieldname === 'image' || $fieldname === 'media') {
            $clean_fieldname = TfishDataValidator::trimString($fieldname);
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE);
            exit;
        }

        $mimetype_list = self::getPermittedUploadMimetypes(); // extension => mimetype
        $extension = mb_strtolower(pathinfo($filename, PATHINFO_EXTENSION), 'UTF-8');
        $clean_extension = array_key_exists($extension, $mimetype_list)
                ? TfishDataValidator::trimString($extension) : false;
        
        if ($clean_filename && $clean_fieldname && $clean_extension) {
            return self::_uploadFile($clean_filename, $clean_fieldname, $clean_extension);
        }
        
        if (!$clean_extension) {
            trigger_error(TFISH_ERROR_ILLEGAL_MIMETYPE, E_USER_NOTICE);
        } else {
            trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_NOTICE);
        }

        return false;
    }

    /** @internal */
    private static function _uploadFile(string $filename, string $fieldname, string $extension)
    {
        $filename = time() . '_' . $filename;
        $upload_path = TFISH_UPLOADS_PATH . $fieldname . '/' . $filename . '.' . $extension;
        
        if ($_FILES[$fieldname]["error"]) {
            switch ($_FILES[$fieldname]["error"]) {
                case 1: // UPLOAD_ERR_INI_SIZE
                    trigger_error(TFISH_ERROR_UPLOAD_ERR_INI_SIZE, E_USER_NOTICE);
                    return false;
                    break;

                case 2: // UPLOAD_ERR_FORM_SIZE
                    trigger_error(TFISH_ERROR_UPLOAD_ERR_FORM_SIZE, E_USER_NOTICE);
                    return false;
                    break;

                case 3: // UPLOAD_ERR_PARTIAL
                    trigger_error(TFISH_ERROR_UPLOAD_ERR_PARTIAL, E_USER_NOTICE);
                    return false;
                    break;

                case 4: // UPLOAD_ERR_NO_FILE
                    trigger_error(TFISH_ERROR_UPLOAD_ERR_NO_FILE, E_USER_NOTICE);
                    return false;
                    break;

                case 6: // UPLOAD_ERR_NO_TMP_DIR
                    trigger_error(TFISH_ERROR_UPLOAD_ERR_NO_TMP_DIR, E_USER_NOTICE);
                    return false;
                    break;

                case 7: // UPLOAD_ERR_CANT_WRITE
                    trigger_error(TFISH_ERROR_UPLOAD_ERR_CANT_WRITE, E_USER_NOTICE);
                    return false;
                    break;
            }
        }
        
        if (!move_uploaded_file($_FILES[$fieldname]["tmp_name"], $upload_path)) {
            trigger_error(TFISH_ERROR_FILE_UPLOAD_FAILED, E_USER_ERROR);
        } else {
            $permissions = chmod($upload_path, 0644);
            if ($permissions) {
                return $filename . '.' . $extension;
            }
        }
        
        return false;
    }

}
