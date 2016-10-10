<?php

/**
* Tuskfish template object.
* 
* Used to hold template variables and to render templates for display. A template object is
* automatically made available on every page via tfish_header.php.
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishTemplate
{	
	protected $__data = array();
	
	public function __construct()
	{}
	
	public function __get($property)
	{
		if (isset($this->__data[$property])) {
			return $this->__data[$property];
		} else {
			return null;
		}
	}
	
	public function __set($property, $value)
	{
		if ($property == 'template') {
			trigger_error(TFISH_CANNOT_OVERWRITE_TEMPLATE_VARIABLE, E_USER_ERROR);
		}
		$this->__data[$property] = $value;
	}
	
	public function render($template)
	{
		if (array_key_exists('template', $this->__data)) {
			trigger_error(TFISH_CANNOT_OVERWRITE_TEMPLATE_VARIABLE, E_USER_ERROR);
		}
		extract($this->__data);
		if (file_exists(TFISH_TEMPLATES_OBJECT_PATH . $template . '.html')) {
			ob_start();
			include TFISH_TEMPLATES_OBJECT_PATH . $template . '.html';
			return ob_get_clean();
		} else {
			trigger_error(TFISH_ERROR_TEMPLATE_DOES_NOT_EXIST, E_USER_ERROR);
		}
	}
}