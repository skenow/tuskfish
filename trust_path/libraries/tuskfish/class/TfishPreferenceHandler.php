<?php

/**
 * TfishPreferenceHandler class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Handler class for Tuskfish preference object.
 * 
 * Retrieve and set site configuration data.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
 */
class TfishPreferenceHandler
{
    /**
     * Get the value of a particular site preference.
     * 
     * @param string $pref Name of preference.
     * @return mixed|null Value of preference if it exists, otherwise null.
     */
    public function get(string $pref)
    {
        $pref = TfishDataValidator::trimString($pref);
        
        if (TfishDataValidator::isAlnumUnderscore($pref)) {
            return $this->tfish_preference->$pref;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
            return null;
        }
    }

    /**
     * Updates the site preferences in the database.
     * 
     * @return bool True on success false on failure.
     */
    public function writePreferences(TfishPreference $tfish_preference, TfishDatabase $tfish_database)
    {
        // Convert preference object to array of key => values.
        if (is_a($tfish_preference, 'TfishPreference')) {
            $key_values = $tfish_preference->toArray();
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }

        foreach ($key_values as $key => $value) {
            $sql = "UPDATE `preference` SET `value` = :value WHERE `title` = :title";
            $statement = $tfish_database->preparedStatement($sql);
            $statement->bindValue(':title', $key, $tfish_database->setType($key));
            $statement->bindValue(':value', $value, $tfish_database->setType($value));
            unset($sql, $key, $value);
            $result = $tfish_database->executeTransaction($statement);
            
            if (!$result) {
                trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
                return false;
            }
        }
        
        return true;
    }

}
