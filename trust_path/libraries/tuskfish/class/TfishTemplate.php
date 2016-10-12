<?php

/**
* Tuskfish template object.
* 
* Used to hold template variables and to render templates for display. A template object is
* automatically made available on every page via tfish_header.php.
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishTemplate
{	
	protected $__data = array();
	
	public function __construct()
	{}
	
	/**
	 * Get the value of an object property.
	 * 
	 * Intercepts direct calls to access an object property. This method can be modified to impose
	 * processing logic to the value before returning it.
	 * 
	 * @param string $property name
	 * @return mixed|null $property value if it is set; otherwise null.
	 */
	public function __get($property)
	{
		if (isset($this->__data[$property])) {
			return $this->__data[$property];
		} else {
			return null;
		}
	}
	
	/**
	 * Set the value of an object property. Will not allow the 'template' property to be overridden.
	 * 
	 * @param string $property name
	 * @param return void
	 */
	public function __set($property, $value)
	{
		if ($property == 'template') {
			trigger_error(TFISH_CANNOT_OVERWRITE_TEMPLATE_VARIABLE, E_USER_ERROR);
		}
		$this->__data[$property] = $value;
	}
	
	/**
	 * Check if an object property is set.
	 * 
	 * Intercepts isset() calls to correctly read object properties. Can be modified to add
	 * processing logic to specific properties.
	 * 
	 * @param string $property name
	 * @return bool 
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
	 * Unsets an object property.
	 * 
	 * Intercepts unset() calls to correctly unset object properties. Can be modified to add 
	 * processing logic for specific properties.
	 * 
	 * @param string $property name
	 * @return bool true on success false on failure 
	 */
	public function __unset($property)
	{
		if (isset($this->__data[$property])) {
			unset($this->__data[$property]);
		} else {
			return false;
		}
	}
	
	/**
	 * Renders a html template file for display.
	 * 
	 * Extracts all properties assigned to the template object as variables and includes the
	 * designated template file. The extracted variables are used to populate the dynamic sections
	 * of the template. Templates can be nested by assigning a rendered child template as a property
	 * of a parent template object.
	 * 
	 * @param string $template name file in the /templates/objects directory.
	 * @return string rendered template
	 */
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