<?php

/**
* Tuskfish site preference handler class.
* 
* Retrieve and set site configuration data.
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
	
	/**
	 * Updates the site preferences in the database.
	 * 
	 * @param object $obj
	 * @return boolean
	 */
	public static function updatePreferences($obj)
	{
		// Convert object to array of key => values.
		$key_values = $obj->toArray();
		
		foreach ($key_values as $key => $value) {
			$sql = "UPDATE `preference` SET `value` = :value WHERE `title` = :title";
			try {
				$statement = TfishDatabase::preparedStatement($sql);
				$statement->bindValue(':title', $key, TfishDatabase::setType($key));
				$statement->bindValue(':value', $value, TfishDatabase::setType($value));
			} catch (PDOException $e) {
				TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
			}
			unset($sql, $key, $value);
			$result = TfishDatabase::executeTransaction($statement);
			if (!$result) {
				trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
				return false;
			}
		}
		return true;
	}
}
