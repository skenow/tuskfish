<?php

/**
* Tuskfish image object class.
* 
* Represents and image object.
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishImage extends TfishContentObject
{
	function __construct()
	{
		// Must call parent constructor first.
		parent::__construct();
		
		// Declare the type, template and module for this this class
		$this->__data['type'] = "TfishImage";
		$this->__data['template'] = "image";
		$this->__data['module'] = "images";
		
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
		// Use the media property to upload the image, as it specifies mimetype and file size.
		return array('image');
	}
}
