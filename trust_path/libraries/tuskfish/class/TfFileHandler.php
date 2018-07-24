<?php

/**
 * TfFileHandler class file.
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
class TfFileHandler
{
    
    use TfMimetypes;
    
    protected $validator;
    
    public function __construct(TfValidator $tf_validator)
    {
        $this->validator = $tf_validator;
    }

    /**
     * Append a string to a file.
     * 
     * Do not set the $filepath using untrusted data sources, such as user input.
     * 
     * @param string $filepath Path to the target file.
     * @param string $contents Content to append to the target file.
     * @return bool True on success false on failure.
     */
    public function appendToFile(string $filepath, string $contents)
    {
        // Check for directory traversals and null byte injection.
        if ($this->validator->hasTraversalorNullByte($filepath)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            return false;
        }
        
        $clean_filepath = $this->validator->trimString($filepath);
        // NOTE: Calling trim() removes linefeed from the contents.
        $clean_content = PHP_EOL . $this->validator->trimString($contents);
        
        if ($clean_filepath && $clean_content) {
            $result = $this->_appendToFile($clean_filepath, $clean_content);
            
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
    private function _appendToFile(string $filepath, string $contents)
    {
        return file_put_contents($filepath, $contents, FILE_APPEND);
    }

    /**
     * Deletes the contents of a specific directory, subdirectories are unaffected.
     * 
     * Do not set the $filepath using untrusted data sources, such as user input.
     * 
     * @param string $filepath Path to the target directory.
     * @return bool True on success false on failure.
     */
    
    public function clearDirectory(string $filepath)
    {
        // Check for directory traversals and null byte injection.
        if ($this->validator->hasTraversalorNullByte($filepath)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            return false;
        }
        
        $clean_filepath = $this->validator->trimString($filepath);
        
        if (!empty($clean_filepath)) {
            $result = $this->_clearDirectory($clean_filepath);
            
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
    private function _clearDirectory(string $filepath)
    {
        $resolved_path = $this->_dataFilePath($filepath);
        
        if ($resolved_path) {
            try {
                foreach (new DirectoryIterator($resolved_path) as $file) {
                    if ($file->isFile() && !$file->isDot()) {
                        $this->_deleteFile($filepath . '/' . $file->getFileName());
                    }
                }
            } catch (Exception $e) {
                trigger_error(TFISH_ERROR_FAILED_TO_DELETE_DIRECTORY, E_USER_NOTICE);
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
     * @param string $filepath Path relative to the data_file directory.
     * @return string|bool Path on success, false on failure.
     */
    private function _dataFilePath(string $filepath)
    {
        if (mb_strlen($filepath, 'UTF-8') > 0) {
            $filepath = rtrim($filepath, '/');
            $filepath = TFISH_UPLOADS_PATH . $filepath;
            $resolved_path = realpath($filepath);
            
            // Basically this checks for directory traversals. This is a limited use function and
            // directory traversals are unnecessary. If any are found the input is suspect and
            // rejected.
            if ($filepath === $resolved_path) {
                return $filepath; // Path is good.
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
     * Do not set the $filepath using untrusted data sources, such as user input.
     * 
     * @param string $filepath Path relative to data_file directory.
     * @return bool True on success, false on failure.
     */
    public function deleteDirectory(string $filepath)
    {
        // Do not allow the upload, image or media directories to be deleted!
        if (empty($filepath)) {
            trigger_error(TFISH_ERROR_FAILED_TO_DELETE_DIRECTORY, E_USER_NOTICE);
            return false;
        }
        
        // Check for directory traversals and null byte injection.
        if ($this->validator->hasTraversalorNullByte($filepath)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            return false;
        }
        
        $clean_filepath = $this->validator->trimString($filepath);
        
        if ($clean_filepath) {
            $result = $this->_deleteDirectory($clean_filepath);
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
    private function _deleteDirectory(string $filepath)
    {
        $filepath = $this->_dataFilePath($filepath);
        
        if ($filepath) {
            try {
                $iterator = new RecursiveDirectoryIterator(
                        $filepath,RecursiveDirectoryIterator::SKIP_DOTS);
                
                foreach (new RecursiveIteratorIterator(
                        $iterator, RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file->isDir()) {
                        rmdir($file->getPathname());
                    } else {
                        unlink($file->getPathname());
                    }
                }
                rmdir($filepath);
                return true;
            } catch (Exception $e) {
                trigger_error(TFISH_ERROR_FAILED_TO_DELETE_DIRECTORY, E_USER_NOTICE);
                return false;
            }
        }
        
        trigger_error(TFISH_ERROR_BAD_PATH, E_USER_NOTICE);
        
        return false;
    }

    /**
     * Destroys an individual file in the data_file directory.
     * 
     * Do not set the $filepath using untrusted data sources, such as user input.
     * 
     * @param string $filepath Path relative to the data_file directory.
     * @return bool True on success, false on failure.
     */
    public function deleteFile(string $filepath)
    {
        // Check for directory traversals and null byte injection.
        if ($this->validator->hasTraversalorNullByte($filepath)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            return false;
        }
        
        $clean_filepath = $this->validator->trimString($filepath);
        
        if (!empty($clean_filepath)) {
            $result = $this->_deleteFile($clean_filepath);
            
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
    private function _deleteFile(string $filepath)
    {
        $filepath = $this->_dataFilePath($filepath);
        
        if ($filepath && file_exists($filepath)) {
            try {
                unlink($filepath);
            } catch (Exeption $e) {
                trigger_error(TFISH_ERROR_FAILED_TO_DELETE_FILE, E_USER_NOTICE);
            }
        } else {
            trigger_error(TFISH_ERROR_BAD_PATH, E_USER_NOTICE);
            return false;
        }

        return true;
    }

    /**
     * Upload a file to the uploads/image or uploads/media directory and set permissions to 644.
     * 
     * @param string $filename Filename.
     * @param string $fieldname Name of form field associated with this upload ('image' or 'media').
     * @return string|bool Filename on success, false on failure.
     */
    public function uploadFile(string $filename, string $fieldname)
    {
        // Check for directory traversals and null byte injection.
        if ($this->validator->hasTraversalorNullByte($filename)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
            exit;
        }
        
        $filename = $this->validator->trimString($filename);
        $clean_filename = mb_strtolower(pathinfo($filename, PATHINFO_FILENAME), 'UTF-8');
        
        // Check that target directory is whitelisted (locked to uploads/image or uploads/media).
        if ($fieldname === 'image' || $fieldname === 'media') {
            $clean_fieldname = $this->validator->trimString($fieldname);
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE);
            exit;
        }

        $mimetype_list = $this->getListOfPermittedUploadMimetypes(); // extension => mimetype
        $extension = mb_strtolower(pathinfo($filename, PATHINFO_EXTENSION), 'UTF-8');
        $clean_extension = array_key_exists($extension, $mimetype_list)
                ? $this->validator->trimString($extension) : false;
        
        if ($clean_filename && $clean_fieldname && $clean_extension) {
            return $this->_uploadFile($clean_filename, $clean_fieldname, $clean_extension);
        }
        
        if (!$clean_extension) {
            trigger_error(TFISH_ERROR_ILLEGAL_MIMETYPE, E_USER_NOTICE);
        } else {
            trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_NOTICE);
        }

        return false;
    }

    /** @internal */
    private function _uploadFile(string $filename, string $fieldname, string $extension)
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
