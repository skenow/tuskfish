<?php

/**
* Tuskfish article template class.
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
class TfishArticleTemplate extends TfishTemplate
{
	public function __construct(TfishArticle $article)
	{
		$this->file = $article->template;
		$this->content = $article;
	}
}
