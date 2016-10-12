<?php

/**
* Tuskfish collection object class.
* 
* Represents a collection of content objects. For example, a magazine produced at regular intervals.
* Collections can be nested by assigning another collection as a parent object. In this way,
* collections can effectively serve as categories and you can construct independent category trees.
* For example, if you wanted to create a "publications" category you would just create a publications
* collection object and assign it as the parent of your publications content objects. Collections
* are searchable content objects, so provide them with a nice description and image/screenshot! 
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishCollection extends TfishContentObject
{
	function __construct()
	{
		// Must call parent constructor first.
		parent::__construct();
		
		// Declare the type, template and module for this this class
		$this->__data['type'] = "TfishCollection";
		$this->__data['template'] = "collection";
		$this->__data['module'] = "collections";
		
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
