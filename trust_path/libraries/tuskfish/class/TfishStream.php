<?php

/**
* Handle and parse data streams (not yet implemented).
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishStream
{
	/**
	 * Generic constructor
	 */
	function __construct(&$handler) 
	{
		$this->__data['handler'] = $handler;
	}
}