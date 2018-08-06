<?php

/**
 * TfContactHandler class file.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     contacts
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Manipulates contact objects (TfContact).
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     contacts
 * @var         TfValidator $validator Instance of the Tuskfish data validator class.
 * @var         TfDatabase $db Instance of the Tuskfish database class.
 * @var         TfCriteriaFactory $criteriaFactory Instance of the Tuskfish criteria factory class.
 * @var         TfCriteriaItemFactory $itemFactory Instance of the Tuskfish criteria item factory.
 * @var         TfContactFactory $contactFactory Instance of the Tuskfish contact factory.
 */
class TfContactHandler
{
    
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $itemFactory;
    protected $contactFactory;
    
    public function __construct(TfValidator $validator, TfDatabase $db, TfCriteriaFactory
            $criteriaFactory, TfCriteriaItemFactory $itemFactory, TfContactFactory $contactFactory)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (is_a($db, 'TfDatabase')) {
            $this->db = $db;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (is_a($criteriaFactory, 'TfCriteriaFactory')) {
            $this->criteriaFactory = $criteriaFactory;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (is_a($itemFactory, 'TfCriteriaItemFactory')) {
            $this->itemFactory = $itemFactory;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (is_a($contactFactory, 'TfContactFactory')) {
            $this->contactFactory = $contactFactory;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
    }
    
    /**
     * Delete an individual contact.
     * 
     * @param int $id ID of the contact to delete.
     * @return boolean True on success, false on failure.
     */
    public function delete(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            return false;
        }

        $result = $this->db->delete('contact', $cleanId);
        
        if (!$result) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve an individual contact from the database.
     * 
     * @param int $id ID of the contact.
     * @return TfContact|boolean Contact object on success, false on failure.
     */
    public function getContact(int $id)
    {
        $cleanId = (int) $id;
        $row = $contact = '';
        
        if ($this->validator->isInt($cleanId, 1)) {
            $criteria = $this->criteriaFactory->getCriteria();
            $criteria->add($this->itemFactory->getItem('id', $cleanId));
            $statement = $this->db->select('contact', $criteria);
            
            if ($statement) {
                $row = $statement->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($row) {
                $contact = $this->contactFactory->getContact();
                $contact->loadPropertiesFromArray($row);
                
                return $contact;
            }
        }
        
        return false;
    }
    
    /**
     * Retrieve multiple contact objects from the database.
     * 
     * @param TfCriteria $criteria Query composer object.
     * @return array Array of contact objects.
     */
    public function getContacts(TfCriteria $criteria = null)
    {
        if (isset($criteria) && !is_a($criteria, 'TfCriteria')) {
            trigger_error(TFISH_ERROR_NOT_CRITERIA_OBJECT, E_USER_ERROR);
        }

        $contacts = array();

        $statement = $this->db->select('contact', $criteria);
        
        if ($statement) {

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $contact = $this->convertArrayToContact($row);
                $contacts[$contact->id] = $contact;
                unset($row, $contact);
            }            

        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }

        return $contacts;
    }

    /**
     * Count the number of contact objects that match the criteria.
     * 
     * @param TfCriteria $criteria Query composer object.
     * @return int Number of contacts that match the query conditions.
     */
    public function getCount(TfCriteria $criteria = null)
    {
        if (isset($criteria) && !is_a($criteria, 'TfCriteria')) {
            trigger_error(TFISH_ERROR_NOT_CRITERIA_OBJECT, E_USER_ERROR);
        }
        
        $count = $this->db->selectCount('contact', $criteria);

        return $count;
    }
    
    /**
     * Gets an array of title (salutation) options.
     * 
     * @return array Array of title options.
     */
    public function getTitles() {
        return array(
            1 => TFISH_CONTACTS_DR,
            2 => TFISH_CONTACTS_PROF,
            3 => TFISH_CONTACTS_MR,
            4 => TFISH_CONTACTS_MS,
            5 => TFISH_CONTACTS_MRS
        );
    }
    
    /**
     * Inserts a contact object into the database. 
     * 
     * @param TfContact $contact A contact object.
     * @return int|bool ID of contact object on success, false on failure.
     */
    public function insert(TfContact $contact)
    {
        $keyValues = $contact->convertToArray();
        unset($keyValues['id']); // ID is auto-incremented by the database on insert operations.
        $keyValues['lastUpdated'] = time(); // Automatically set submission time.

        $result = $this->db->insert('contact', $keyValues);

        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
            return false;
        } else {
            return $this->db->lastInsertId();
        }
    }
    
    /**
     * Update a contact in the database.
     * 
     * @param TfContact $contact A contact object.
     * @return boolean True on success, false on failure.
     */
    public function update(TfContact $contact)
    {
        if (!is_a($contact, 'TfContact')) {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        $cleanId = $this->validator->isInt($contact->id, 1) ? (int) $contact->id : 0;
        $keyValues = $contact->convertToArray();
        
        $result = $this->db->update('contact', $cleanId, $keyValues);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
        }

        return true;
    }
    
}
