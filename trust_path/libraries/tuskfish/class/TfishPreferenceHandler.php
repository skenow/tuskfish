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

    /** @var object $tfish_preferences Permitted website properties held in instance of TfishPreference */
    private $tfish_preferences;

    /** 
     * Initialise default property values.
     * 
     * @param object $tfish_preferences Instance of TfishPreference class, holds site preference info.
     */
    function __construct(TfishPreference $tfish_preferences)
    {
        $this->preferences = $tfish_preferences;
    }

    /**
     * Get the value of a particular site preference.
     * 
     * @param string $pref Name of preference.
     * @return mixed|null Value of preference if it exists, otherwise null.
     */
    public static function get(string $pref)
    {
        $pref = TfishDataValidator::trimString($pref);
        
        if (TfishDataValidator::isAlnumUnderscore($pref)) {
            return $this->tfish_preferences->$pref;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
            return null;
        }
    }

    /**
     * Updates the site preferences in the database.
     * 
     * @param object $obj TfishPreference object.
     * @return bool True on success false on failure.
     */
    public static function updatePreferences(TfishPreference $obj)
    {
        // Convert object to array of key => values.
        if (is_a($obj, 'TfishPreference')) {
            $key_values = $obj->toArray();
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }

        foreach ($key_values as $key => $value) {
            $sql = "UPDATE `preference` SET `value` = :value WHERE `title` = :title";
            $statement = TfishDatabase::preparedStatement($sql);
            $statement->bindValue(':title', $key, TfishDatabase::setType($key));
            $statement->bindValue(':value', $value, TfishDatabase::setType($value));
            unset($sql, $key, $value);
            $result = TfishDatabase::executeTransaction($statement);
            
            if (!$result) {
                trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
                return false;
            }
        }
        
        // Flush the cache.
        TfishCache::flushCache();
        
        return true;
    }

}
