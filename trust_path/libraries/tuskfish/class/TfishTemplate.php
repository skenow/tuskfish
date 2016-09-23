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
	
	/**
	 * Concatenates an array of templates in preparation for display.
	 * 
	 * Note that data must be passed to this method pre-escaped for output to display. 
	 * 
	 * @param array $templates
	 * 
	 * @return string $output
	 */
	public function concatenate($templates)
	{
		$output = "";
		
		foreach ($templates as $template) {
			if (!is_a($template, 'TfishTemplate')) {
				trigger_error(TFISH_ERROR_NOT_TEMPLATE_OBJECT, E_USER_ERROR);
			}
			$output .= $content . 'n';
		}
		
		return $output;
	}
	
	/**
	 * Prepares object data for display (converts to human readable) and renders the template
	 * using the properties to replace placeholder tags.
	 * 
	 * @return string
	 */
	public function render()
	{
		if (file_exists(TFISH_TEMPLATES_OBJECT_PATH . $this->file . '.html')) {
			$output = file_get_contents(TFISH_TEMPLATES_OBJECT_PATH . $this->file . '.html');
		} else {
			trigger_error(TFISH_ERROR_TEMPLATE_DOES_NOT_EXIST, E_USER_ERROR);
		}
		
		$properties = $this->content->getPropertyWhitelist();
		$tag_list = TfishContentHandler::getTagList();
		foreach ($properties as $key => $value) {
			$placeholder = '{' . $key . '}';
			if ($key == 'tags') {
				$tags = implode(", ", $this->content->tags);
				$output = str_replace('{tags}', $tags, $output);
			} else {
				$output = str_replace($placeholder, $this->content->escape($key), $output);
			}
			unset($key, $value, $placeholder);
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
