<?php

/**
* Tuskfish base template object.
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishTemplate {
	protected $file;
	protected $content;
	
	public function __construct() {}
	
	public function concatenate($templates, $delimiter = "n")
	{
		$output = "";
		
		foreach ($templates as $template) {
			if (!is_a($template, 'TfishTemplate')) {
				trigger_error(TFISH_ERROR_NOT_TEMPLATE_OBJECT, E_USER_ERROR);
			}
			$output .= $content . $delimiter;
		}
		
		return $output;
	}
	
	public function render()
	{
		if (!file_exists($this->file)) {
			$output = file_get_contents(TFISH_TEMPLATES_OBJECT_PATH . $this->file);
		}
		
		$properties = $this->content->getPropertyWhitelist();
		foreach ($properties as $key => $value) {
			$placeholder = '{' . $key . '}';
			if (TfishFilter::isArray($this->content->$key)) {
				// Need to handle arrays (basically, tags) somehow.
			} else {
				$output = str_replace($placeholder, $this->content->$key, $output);
			}
		}
		
		return $output;
	}
	
	/**
	 * Set a template variable.
	 * 
	 * Note that data must be passed to this method pre-escaped for output to display. 
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key, $value)
	{
		$this->content[$key] = $value;
	}
}
