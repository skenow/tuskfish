<?php

/**
 * Tuskfish file handler class.
 *
 * Provides methods for handling common file operations. In some cases, sensitive operations are
 * restricted to a particular directory (for example, file uploads).
 * 
 * @copyright	Simon Wilkinson (Crushdepth) 2013-2016
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @since		1.0
 * @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
 * @package		core
 */
if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishFileHandler {

    public function __construct() {
        
    }

    /**
     * Returns an array of audio mimetypes that are permitted for content objects.
     * 
     * Note that ogg audio files should use the .oga extension, although the legacy .ogg extension
     * is still acceptable, although it must no longer be used for video files.
     * 
     * @return array
     */
    public static function allowedAudioMimetypes() {
        return array(
            "mp3" => "audio/mpeg",
            "oga" => "audio/ogg",
            "ogg" => "audio/ogg",
            "wav" => "audio/x-wav"
        );
    }

    /**
     * Returns a string of video mimetypes that are permitted for upload.
     * 
     * Note that ogg video files must use the .ogv file extension. Please do not use .ogg for
     * video files as this practice has been deprecated in favour of .ogv. While .ogg is still in
     * wide use it is now presumed to refer to audio files only.
     * 
     * @return string
     */
    public static function allowedVideoMimetypes() {
        return array(
            "mp4" => "video/mp4",
            "ogv" => "video/ogg",
            "webm" => "video/webm"
        );
    }

    /**
     * Append a string to a file.
     * 
     * @param string $path
     * @param string $contents
     * @return boolean
     */
    public static function appendFile($path, $contents) {
        $clean_path = TfishFilter::trimString($path);
        $clean_content = PHP_EOL . TfishFilter::trimString($contents); // NOTE: Calling trim() removes linefeed from the contents.
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

    private static function _appendFile($path, $contents) {
        return file_put_contents($path, $contents, FILE_APPEND);
    }

    /**
     * Deletes the contents of a specific directory, subdirectories are unaffected.
     * 
     * @param string $path
     * @return boolean true on success false on failure
     */
    public static function clearDirectory($path) {
        $clean_path = TfishFilter::trimString($path);
        if ($clean_path) {
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

    private static function _clearDirectory($path) {
        $path = self::_dataFilePath($path);
        if ($path) {
            try {
                foreach (new DirectoryIterator($path) as $file) {
                    if ($file->isFile()) {
                        self::_deleteFile($path . '/' . $file->getFileName());
                    }
                }
            } catch (Exception $e) {
                TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
                return false;
            }
            return true;
        }
        trigger_error(TFISH_ERROR_BAD_PATH, E_USER_NOTICE);
        return false;
    }

    /**
     * Create a new subdirectory in the data_file directory, optionally with parents if they don't exist
     * 
     * @param string $directory_path relative to the data_file directory
     * @param string $chmod directory permission (chmod) mode
     * @param bool $create_parents makes parent directories if they do not exist
     * @return bool true on success false on failure
     */
    public static function createDirectory($path, $chmod = 0755, $create_parents = true) {
        $clean_path = TfishFilter::trimString($path);
        $clean_chmod = TfishFilter::isDigit($chmod) ? $chmod : false;
        $clean_create_parents = TfishFilter::isBool($create_parents) ? $create_parents : null;
        if ($clean_path && $clean_chmod && isset($clean_create_parents)) {
            $result = self::_createDirectory($clean_path, $clean_chmod, $clean_create_parents);
            if (!$result) {
                trigger_error(TFISH_ERROR_FAILED_TO_CREATE_DIRECTORY, E_USER_NOTICE);
                return false;
            }
            return true;
        }
        trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_NOTICE);
        return false;
    }

    private static function _createDirectory($path, $chmod, $create_parents) {
        $path = self::_dataFilePath($path);
        if ($path) {
            try {
                $directory_created = mkdir($path, $chmod, $create_parents);
            } catch (Exception $e) {
                TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
            }
        } else {
            trigger_error(TFISH_ERROR_BAD_PATH, E_USER_NOTICE);
        }
        return $directory_created;
    }

    /**
     * Create a new file in the data_file directory
     * 
     * Does NOT check that specified subdirectories exist within data_file,
     * so create them first if they are needed
     * 
     * @param string $file_name
     * @param string $contents
     * $param bool $append
     * @return boolean true on success, false on failure
     */
    public static function createFile($path, $contents = false, $chmod = 0600, $append = false) {
        $clean_path = TfishFilter::trimString($path);
        $clean_contents = TfishFilter::trimString($contents);
        $clean_chmod = TfishFilter::isDigit($chmod) ? $chmod : false;
        $clean_append = TfishFilter::isBool($append) ? $append : null;
        if ($clean_path && $clean_contents && $clean_chmod && $clean_append) {
            $result = self::_createFile($clean_path, $clean_contents, $clean_chmod, $clean_append);
            if (!$result) {
                trigger_error(TFISH_ERROR_FAILED_TO_CREATE_FILE, E_USER_NOTICE);
                return false;
            }
            return true;
        }
        trigger_error(TFISH_ERROR_REQUIRED, E_USER_NOTICE);
        return false;
    }

    private static function _createFile($path, $contents, $chmod, $append) {
        $path = self::_dataFilePath($path);
        if ($path && isset($append)) {
            try {
                $file_handle = fopen($path, 'w');
                chmod($path, $chmod);
            } catch (Exception $e) {
                TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
            }
            if ($file_handle) {
                try {
                    $bytes_written = fwrite($file_handle);
                    fclose($file_handle);
                } catch (Exception $e) {
                    TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
                }
            }
        } else {
            $bytes_written = false;
        }
        return $bytes_written;
    }

    /**
     * Prepends the upload directory path to a file or folder name and checks that the path
     * does not contain directory traversals
     *
     * @param string $file_name
     * @return string|bool path on success false on failture
     */
    private static function _dataFilePath($path) {
        if (mb_strlen($path, 'UTF-8') > 0) {
            $path = rtrim($path, '/');

            // Construct the full path and verify that it lies within the data_file directory.			
            $resolved_path = realpath(TFISH_UPLOADS_PATH . $path);
            if ($resolved_path == TFISH_UPLOADS_PATH . $path) {
                return $resolved_path; // Path is good.
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
     * @param string $directory_name
     * @return bool true on success false on failure
     */
    public static function deleteDirectory($path) {
        $clean_path = TfishFilter::trimString($path);
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

    private static function _deleteDirectory($path) {
        $path = self::_dataFilePath($path);
        if ($path) {
            try {
                $iterator = new RecursiveDirectoryIterator($path);
                foreach (new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file->isDir()) {
                        rmdir($file->getPathname());
                    } else {
                        unlink($file->getPathname());
                    }
                }
                rmdir($path);
                return true;
            } catch (Exception $e) {
                TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
                return false;
            }
        }
        trigger_error(TFISH_ERROR_BAD_PATH, E_USER_NOTICE);
        return false;
    }

    /**
     * Destroys an individual file in the data_file directory
     * 
     * @param string $file_path
     * @return bool true on success false on failure
     */
    public static function deleteFile($path) {
        $clean_path = TfishFilter::trimString($path);
        if ($clean_path) {
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

    private static function _deleteFile($path) {
        $path = self::_dataFilePath($path);
        if ($path && file_exists($path)) {
            try {
                unlink($path);
            } catch (Exeption $e) {
                TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
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
     * @return array of permitted mimetypes as file extensions.
     * @todo Move this into a static TfishPreference method
     *
     */
    public static function getPermittedUploadMimetypes() {
        return array(
            "doc" => "application/msword", // Documents.
            "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "pdf" => "application/pdf",
            "odt" => "application/vnd.oasis.opendocument.text",
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
     * Initiate streaming of a downloadable media file associated with a content object.
     * 
     * DOES NOT WORK WITH COMPRESSION ENABLED IN OUTPUT BUFFER. This method acts as an intermediary
     * to provide access to uploaded file resources that reside outside of the web root, while
     * concealing the real file path and name. Use this method to provide safe user access to
     * uploaded files. If anything nasty gets uploaded nobody will be able to execute it directly
     * through the browser.
     * 
     * @param int $id of the associated content object.
     * @param string $filename an alternative name (rename) for the file you wish to transfer,
     * excluding extension.
     * @return boolean
     */
    public static function sendDownload($id, $filename = false) {
        $clean_id = TfishFilter::isInt($id, 1) ? (int) $id : false;
        $clean_filename = !empty($filename) ? TfishFilter::trimString($filename) : false;
        if ($clean_id) {
            $result = self::_sendDownload($clean_id, $clean_filename);
            if ($result == false) {
                return false;
            }
            return true;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_NOTICE);
        }
    }

    private static function _sendDownload($id, $filename) {
        $criteria = new TfishCriteria();
        $criteria->add(new TfishCriteriaItem('id', $id));
        $statement = TfishDatabase::select('content', $criteria);
        if (!$statement) {
            trigger_error(TFISH_ERROR_NO_STATEMENT, E_USER_NOTICE);
            return false;
        }
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $content = TfishContentHandler::toObject($row);
        if ($content && $content->online == true) {
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
                header('Content-Disposition: attachment; filename="' . $filename . '.' . $file_extension . '"');
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
     * @param string $file Filename.
     * @param string $fieldname Name of form field associated with this upload (media subdirectory).
     * @return string|bool $filename on success or false on failure.
     */
    public static function uploadFile($filename, $fieldname) {
        $filename = TfishFilter::trimString($filename);
        $clean_filename = mb_strtolower(pathinfo($filename, PATHINFO_FILENAME), 'UTF-8');

        $fieldname = TfishFilter::trimString($fieldname);
        $clean_fieldname = TfishFilter::isAlnum($fieldname) ? $fieldname : false;

        $mimetype_list = self::getPermittedUploadMimetypes(); // extension => mimetype
        $extension = mb_strtolower(pathinfo($filename, PATHINFO_EXTENSION), 'UTF-8');
        $clean_extension = array_key_exists($extension, $mimetype_list) ? TfishFilter::trimString($extension) : false;
        if ($clean_filename && $clean_fieldname && $clean_extension) {
            return self::_uploadFile($clean_filename, $clean_fieldname, $clean_extension);
        }
        trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_NOTICE);
        return false;
    }

    private static function _uploadFile($filename, $fieldname, $extension) {
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
