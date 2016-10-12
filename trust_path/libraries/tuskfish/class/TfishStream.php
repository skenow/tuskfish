<?php

/**
* Handle and parse data streams (not yet implemented).
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishStream
{
	function __construct(&$handler) 
	{
		$this->__data['handler'] = $handler;
	}
}