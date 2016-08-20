<?php

/**
* Tuskfish page-level metadata class
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishMetadata
{	
	protected $__data = array(
		'template' => '',
		'title' => '',
		'description' => '',
		'author' => '',
		'copyright' => '',
		'generator' => '',
		'seo' => '',
		'robots' => '');
	
	/**
	 * Generic constructor
	 */
	function __construct()
	{
		$this->template = 'default.html';
		$this->title = 'Tuskfish CMS';
		$this->description = 'A cutting-edge single user micro CMS.';
		$this->author = 'Isengard.biz';
		$this->copyright = 'Copyright 2013-2016 Isengard.biz.';
		$this->generator = 'Tuskfish CMS';
		$this->seo = '';
		$this->robots = 'index,follow';
	}
	
	/**
	 * Access an existing object property and escape it for output to browser.
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property)
	{
		if (isset($this->__data[$property])) {
			return htmlspecialchars($this->__data[$property], ENT_QUOTES, "UTF-8");
		} else {
			return false;
		}
	}
	
	/**
	 * Set an existing object property
	 * 
	 * @param mixed $property
	 * @param mixed $value
	 */
	public function __set($property, $value)
	{
		if (isset($this->__data[$property])) {
			$value = TfishFilter::trimString($value);
			$clean_value = TfishFilter::escape($value);
			$this->__data[$property] = $clean_value;
		} else {
			trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
		}
	}
	
	/**
	 * Intercept isset() calls to correctly read object properties
	 * 
	 * @param type $property
	 * @return type 
	 */
	public function __isset($property)
	{
		if (isset($this->__data[$property])) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Intercept unset() calls to correctly unset object properties
	 * 
	 * @param type $property
	 * @return type 
	 */
	public function __unset($property)
	{
		if (isset($this->__data[$property])) {
			unset($this->__data[$property]);
		} else {
			return false;
		}
	}	
}