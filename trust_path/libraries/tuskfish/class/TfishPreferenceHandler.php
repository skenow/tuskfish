<?php

/**
* Tuskfish site preference handler class
* 
* Retrieve and set site configuration data
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishPreferenceHandler
{
	// Permitted properties.
	private $tfish_preferences;
	
	/**
	 * Generic constructor. Reads preferences from database and assigns whitelisted properties
	 */
	function __construct($tfish_preferences)
	{
		if (is_a($tfish_preferences, 'TfishPreference')) {
			$this->preferences = $tfish_preferences;
		} else {
			trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
		}
	}
	
	public static function get($pref)
	{
		if (TfishFilter::isAlnumUnderscore($pref)) {
			return $this->tfish_preferences->$pref;
		} else {
			trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
			return null;
		}
	}
	
	public static function setPreference($pref)
	{
		
	}
	
}
