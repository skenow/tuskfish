<?php

/**
* Tuskfish user class.
* 
* Represents a user. Since Tuskfish is a single-user system, this class will probably be deprecated
* in due course.
*
* @copyright	Simon Wilkinson (Crushdepth) 2013
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishUser
{
	// Permitted properties of this object. Child classes should extend this list (not replace it).
	protected $__data = array(
		'id',
		'admin_email',
		'password_hash',
		'user_salt',
		'user_group',
		);
	
	function __construct() {}
	
	/**
	 * Access an existing object property; intercepts direct external calls to read the value.
	 * 
	 * @param string $property
	 * @return mixed|null
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
	 * Set an existing object property; intercepts direct external calls to set the value.
	 * 
	 * @param string $property
	 * @param mixed $value
	 */
	public function __set($property, $value)
	{
		if (isset($this->__data[$property])) {
			$this->__data[$property] = $value;
		} else {
			trigger_error(TFISH_ERROR_NO_SUCH_PROPERTY, E_USER_ERROR);
		}
	}
	
	/**
	 * Intercept external isset() calls to correctly read object properties.
	 * 
	 * @param string $property
	 * @return boolean 
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
	 * Intercept external unset() calls to correctly unset object properties
	 * 
	 * @param string $property
	 * @return boolean 
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