<?php

/**
* Tuskfish video object class.
* 
* Represents a video object.
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishVideo extends TfishContentObject
{
	function __construct()
	{
		// Must call parent constructor first.
		parent::__construct();
		
		// Declare the type, template and module for this this class
		$this->__data['type'] = "TfishVideo";
		$this->__data['template'] = "video";
		$this->__data['module'] = "videos";
		
		// Object definition - unset any properties unused in this subclass.
		$zeroedProperties = $this->zeroedProperties();		
		foreach ($zeroedProperties as $property) {
			unset($this->__properties[$property], $this->__data[$property]);
		}
	}
	
	/**
	 * Returns an array of base object properties that are not used by this subclass.
	 * 
	 * This list is also used in update calls to the database to ensure that unused columns are
	 * cleared and reset with default values.
	 * 
	 * @return array
	 */
	public function zeroedProperties()
	{
		return array();
	}
}
