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
    protected $db;
    
    public function __construct(TfishDatabase $tfish_database)
    {
        $this->db = $tfish_database;
    }
    
    /**
     * Read out the site preferences into an array.
     * 
     * @return array Array of site preferences.
     */
    public function readPreferencesFromDatabase()
    {
        $preferences = array();
        $result = $this->db->select('preference');
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $preferences[$row['title']] = $row['value'];
        }
        return $preferences;
    }

    /**
     * Updates the site preferences in the database.
     * 
     * @return bool True on success false on failure.
     */
    public function writePreferences(TfishPreference $tfish_preference)
    {
        // Convert preference object to array of key => values.
        $key_values = $tfish_preference->getPreferencesAsArray();
        
        // Unset the validator object as it is not stored in the database.
        unset($key_values['validator']);
        
        foreach ($key_values as $key => $value) {
            $sql = "UPDATE `preference` SET `value` = :value WHERE `title` = :title";
            $statement = $this->db->preparedStatement($sql);
            $statement->bindValue(':title', $key, $this->db->setType($key));
            $statement->bindValue(':value', $value, $this->db->setType($value));
            unset($sql, $key, $value);
            $result = $this->db->executeTransaction($statement);
            
            if (!$result) {
                trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
                return false;
            }
        }
        
        return true;
    }

}
