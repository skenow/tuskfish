<?php

/**
* Tuskfish file handler class
*
* All file operations are conducted relative to the TFISH_MEDIA_PATH directory, which is the 
* only place where files are allowed to be uploaded to. It exists outside the web root, in the
* trust path directory.
* 
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishFileHandler
{
	public function __construct()
	{}
	
	/**
	 * Append a string to a file.
	 * 
	 * @param string $path
	 * @param string $contents
	 * @return boolean
	 */
	public static function appendFile($path, $contents)
	{
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
	
	/**
	 * Delete the contents of a directory.
	 * 
	 * @param string $path
	 * @return boolean
	 */
	public static function clearDirectory($path)
	{
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
	
	/**
	 * Create a directory.
	 * 
	 * @param string $path
	 * @return boolean
	 */
	public static function createDirectory($path, $chmod = 0755, $create_parents = true)
	{
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
	
	/**
	 * Create a file.
	 * 
	 * @param string $path
	 * @return boolean
	 */
	public static function createFile($path, $contents = false, $chmod = 0600, $append = false)
	{
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
		
	/**
	 * Delete a directory.
	 * 
	 * @param string $path
	 * @return boolean
	 */
	public static function deleteDirectory($path)
	{
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
	
	/**
	 * Delete a file.
	 * 
	 * @param string $path
	 * @return boolean
	 */
	public static function deleteFile($path)
	{
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
	
	/**
	 * Returns an array of mimetypes that are permitted for upload to the media directory.
	 * 
	 * Todo: Move this into a static TfishPreference method.
	 * 
	 * NOTE: Adding HTML to this list would be a BAD IDEA, as such files can include PHP code,
	 * although uploaded files have execution permissions removed and are stored outside of the
	 * web root in order to prevent direct access by browser. 
	 * 
	 * @return array
	 */
	public static function getPermittedUploadMimetypes() {
		return array(
			'doc', // Documents.
			'docx',
			'pdf',
			'odt',
			'gif', // Images.
			'jpg',
			'png',
			'mp3', // Audio.
			'wav',
			'mpeg',	// Video.		
			'mp4', 
			'webm',
			'ogg', // Audio/video container
			'zip', // Archives.
			'tar',			
		);
	}
	
	/**
	 * Returns a string of mimetypes that are permitted for upload.
	 * 
	 * @return string
	 */
	public static function getPermittedMediaMimetypes() {
		$mimetypes = self::getPermittedUploadMimetypes();
		return implode(',', $mimetypes);
	}
	
	/**
	 * Returns an string of image mimeetypes that are permitted for content objects.
	 * 
	 * @return string
	 */
	public static function getPermittedImageMimetypes() {
		return "gif,jpg,png";
	}
	
	/**
	 * Initiate (emit) a downloadable media file associated with a content object.
	 * 
	 * @param int $id of the associated content object.
	 * @param string $filename an alternative name (rename) for the file you wish to transfer,
	 * excluding extension.
	 * @return boolean
	 */
	public static function sendDownload($id, $filename = false)
	{
		$clean_id = TfishFilter::isInt($id, 1) ? (int)$id : false;
		$clean_filename = !empty($filename) ? TfishFilter::trimString($filename) : false;
		if ($clean_id) {
			$result = self::_sendDownload($clean_id, $clean_filename);
			if (!$result) {
				return false;
			}
			return true;
		} else {
			trigger_error(TFISH_ERROR_NOT_INT, E_USER_NOTICE);
		}
	}
	
	/**
	 * Upload a file to the media directory.
	 * 
	 * @param string $file Filename.
	 * @param string $fieldname Name of form field associated with this upload (media subdirectory).
	 * @return mixed string $filename on success or false on failure.
	 */
	public static function uploadFile($filename, $fieldname)
	{		
		$filename = TfishFilter::trimString($filename);
		$clean_filename = pathinfo($filename, PATHINFO_FILENAME);
		
		$fieldname = TfishFilter::trimString($fieldname);
		$clean_fieldname = TfishFilter::isAlnum($fieldname) ? $fieldname : false ;
		
		$mimetype_list = self::getPermittedUploadMimetypes();
		$mimetype = pathinfo($filename, PATHINFO_EXTENSION);
		$clean_mimetype = in_array($mimetype, $mimetype_list) ? $mimetype : false;
		
		if ($clean_filename && $clean_fieldname && $clean_mimetype) {
			return self::_uploadFile($clean_filename, $clean_fieldname, $clean_mimetype);
		}
		trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_NOTICE);
		return false;
	}
	
	private static function _appendFile($path, $contents)
	{
		return file_put_contents($path, $contents, FILE_APPEND);
	}
	
	/**
	 * Deletes the contents of a specific directory, subdirectories are unaffected.
	 * 
	 * @param string $directory_path
	 * @return bool
	 */
	private static function _clearDirectory($path)
	{
		$path = self::_data_file_path($path);
		if ($path) {
			try {
				foreach (new DirectoryIteratory($path) as $file) {
					if (isFile($file)) {
						self::_deleteFile($path . '/' . getFileName($file));
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
	 * Create a new file in the data_file directory
	 * 
	 * Does NOT check that specified subdirectories exist within data_file,
	 * so create them first if they are needed
	 * 
	 * @param string $file_name
	 * @param mixed $contents
	 * $param bool $append
	 * @return mixed
	 */
	private static function _createFile($path, $contents, $chmod, $append)
	{
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
	 * Create a new subdirectory in the data_file directory, optionally with parents if they don't exist
	 * 
	 * @param string $directory_path relative to the data_file directory
	 * @param string $chmod directory permission (chmod) mode
	 * @param bool $create_parents makes parent directories if they do not exist
	 * @return bool
	 */
	private static function _createDirectory ($path, $chmod, $create_parents)
	{
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
	 * Prepends the upload directory path to a file or folder name and checks that the path
	 * does not contain directory traversals
	 *
	 * @param string $file_name
	 * @return mixed
	 */
	private static function _dataFilePath($path)
	{
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
	 * @param type $directory_name
	 */
	private static function _deleteDirectory($path)
	{
		$path = self::_data_file_path($path);
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
	 * @return bool
	 */
	private static function _deleteFile($path)
	{
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
	 * Initiate streaming of a media file associated with a content object via indirect access.
	 * 
	 * DOES NOT WORK WITH COMPRESSION ENABLED IN OUTPUT BUFFER.
	 * 
	 * Basically this method acts as an intermediary to provide access to uploaded file resources
	 * that reside outside of the web root, while concealing the real file path and name. Use this
	 * method to provide safe user access to uploaded files. If anything nasty gets uploaded
	 * nobody will be able to execute it directly through the browser.
	 * 
	 * @param int $id of associated content object.
	 * @param string $filename alternative name (rename) for the file you wish to transfer,
	 * excluding extension.
	 */
	private static function _sendDownload($id, $filename)
	{
		$criteria = new TfishCriteria();
		$criteria->add(new TfishCriteriaItem('id', $id));
		$statement = TfishDatabase::select('content', $criteria);
		if (!$statement) {
			trigger_error(TFISH_ERROR_NO_STATEMENT, E_USER_NOTICE);
			return false;
		}
		$row = $statement->fetch(PDO::FETCH_ASSOC);
		$content = TfishContentHandler::toObject($row);
		if ($content) {
			$media = isset($content->media) ? $content->media : false;
			if ($media && is_readable(TFISH_MEDIA_PATH . $content->media)) {
				ob_start();
				$file_path = TFISH_MEDIA_PATH . $content->media;
				$filename = empty($filename) ? pathinfo($file_path, PATHINFO_FILENAME) : $filename;
				$file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
				$file_size = filesize(TFISH_MEDIA_PATH . $content->media);
				$mimetype_list = TfishUtils::getMimetypes();
				$mimetype = $mimetype_list[$file_extension];

				// Output the file.
				header('Content-Description: File Transfer');
				header("Content-type: " . $mimetype);
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment;filename="' . $filename . '.' . $file_extension . '"');
				header("Content-Transfer-Encoding: binary");
				header("Expires: 0"); 
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
				header("Pragma: public");
				header("Content-Length: " . $file_size);
				header('Content-Description: File Transfer');
				ob_clean();
				flush();
				readfile($file_path);
				exit;
			} else {
				return false;
			}
		} else {
			trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_NOTICE);
			return false;
		}
	}
	
	/**
	 * Upload a file to the media directory and set permissions to 600.
	 * 
	 * @param string $filename
	 * @param string $mimetype
	 * @return mixed string $filename on success bool false on failure
	 */
	private static function _uploadFile($filename, $fieldname, $mimetype)
	{		
		$filename = time() . '_' . $filename;
		$upload_path = TFISH_UPLOADS_PATH . $fieldname . '/' . $filename . '.' . $mimetype;
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
			$permissions = chmod($upload_path, 0600);
			if ($permissions) {
				return $filename . '.' . $mimetype;
			}
		}
		return false;
	}
}