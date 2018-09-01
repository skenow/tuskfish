<?php

/**
 * TfPreferenceHandler class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1
 * @package     core
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/** 
 * Read and write site preferences to the database.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1
 * @package     core
 * @var         TfDatabase $db Instance of the Tuskfish database class.
 */
class TfPreferenceHandler
{
    protected $db;
    
    /**
     * Constructor.
     * 
     * @param TfDatabase $db Instance of the database class.
     */
    public function __construct(TfDatabase $db)
    {
        if (is_a($db, 'TfDatabase')) {
            $this->db = $db; 
        } else {
            trigger_error(TFISH_ERROR_NOT_DATABASE, E_USER_ERROR);
        }
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
     * @param TfPreference $preference Instance of the Tuskfish site preference class.
     * @return bool True on success false on failure.
     */
    public function writePreferences(TfPreference $preference)
    {
        // Convert preference object to array of key => values.
        $keyValues = $preference->getPreferencesAsArray();
        
        // Unset the validator object as it is not stored in the database.
        unset($keyValues['validator']);
        
        foreach ($keyValues as $key => $value) {
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
