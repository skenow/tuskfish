<?php

/**
* Handle and parse data streams
* 
* Object properties are held in a protected store and accessed via magic methods.
* Note that if a subclass implements magical __get() and __set() methods, the parental versions
* will NOT be called unless you explicitly do it using parent::__get(). However, 
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