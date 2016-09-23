<?php

/**
* Tuskfish tag template class.
* 
* Usage:
* $articleTpl = new TfishArticleTemplate($articleObj);
* $articleTpl->output(); 
*
* @copyright	Simon Wilkinson (Crushdepth) 2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishTagTemplate extends TfishTemplate
{
	public function __construct(TfishTag $tags)
	{
		$this->file = $tags->template;
		$this->content = $tags; // Array of tag objects.
	}
	
	public function render_tag_list()
	{
		$output = '';
		
		foreach ($this->content as $tag) {
			$placeholder = '{tag}';
			$output = str_replace($placeholder, $tag, $output) . ', ';
		}
		$output = rtrim(', ', $output);
			
		return $output;
	}
}
