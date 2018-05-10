<?php

/**
 * TfishContactHandler class file.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishContactHandler extends TfishContentHandler
{
    
    public static function delete(int $id)
    {
        $clean_id = (int) $id;
        
        if (!TfishFilter::isInt($clean_id, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            return false;
        }

        // Delete associated taglinks. If this object is a tag, delete taglinks referring to it.
        /*$result = TfishTaglinkHandler::deleteTaglinks($obj);
        
        if (!$result) {
            return false;
        }*/

        // Delete the object.
        $result = TfishDatabase::delete('contact', $clean_id);
        
        if (!$result) {
            return false;
        }

        return true;
    }

    public static function getObject(int $id)
    {
        $clean_id = (int) $id;
        $row = $object = '';
        
        if (TfishFilter::isInt($id, 1)) {
            $criteria = new TfishCriteria();
            $criteria->add(new TfishCriteriaItem('id', $clean_id));
            $statement = TfishDatabase::select('contact', $criteria);
            
            if ($statement) {
                $row = $statement->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($row) {
                $object = self::toObject($row);
                return $object;
            }
        }
        
        return false;
    }
    
    public static function getObjects(TfishCriteria $criteria = null)
    {
        if (!isset($criteria)) {
            $criteria = new TfishCriteria();
        }

        // Unset any pre-existing object type criteria.
        $type_key = self::getTypeIndex($criteria->item);
        
        if (isset($type_key)) {
            $criteria->killType($type_key);
        }

        // Set new type criteria specific to this object.
        $criteria->add(new TfishCriteriaItem('type', 'TfishContact'));
        $objects = parent::getObjects($criteria);

        return $objects;
    }

    public static function getCount(TfishCriteria $criteria = null)
    {
        if (!isset($criteria)) {
            $criteria = new TfishCriteria();
        }

        // Unset any pre-existing object type criteria.
        $type_key = self::getTypeIndex($criteria->item);
        
        if (isset($type_key)) {
            $criteria->killType($type_key);
        }

        // Set new type criteria specific to this object.
        $criteria->add(new TfishCriteriaItem('type', 'TfishContact'));
        $count = parent::getcount($criteria);

        return $count;
    }
    
    public static function getTitles() {
        return array(
            1 => TFISH_DR,
            2 => TFISH_PROF,
            3 => TFISH_MR,
            4 => TFISH_MS,
            5 => TFISH_MRS
        );
    }
    
    /**
     * Inserts a contact object into the database. 
     * 
     * @param object $obj TfishContactObject.
     * @return bool True on success, false on failure.
     */
    public static function insert(TfishContact $obj)
    {
        $key_values = $obj->toArray();
        $key_values['submission_time'] = time(); // Automatically set submission time.
        unset($key_values['id']); // ID is auto-incremented by the database on insert operations.
        unset($key_values['tags']);

        // Insert the object into the database.
        $result = TfishDatabase::insert('contact', $key_values);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
            return false;
        } else {
            $content_id = TfishDatabase::lastInsertId();
        }
        
        unset($key_values, $result);

        // Tags are stored separately in the taglinks table. Tags are assembled in one batch before
        // proceeding to insertion; so if one fails a range check all should fail.
        /*if (isset($obj->tags) and TfishFilter::isArray($obj->tags)) {
            // If the lastInsertId could not be retrieved, then halt execution becuase this data
            // is necessary in order to correctly assign taglinks to content objects.
            if (!$content_id) {
                trigger_error(TFISH_ERROR_NO_LAST_INSERT_ID, E_USER_ERROR);
                exit;
            }

            $result = TfishTaglinkHandler::insertTaglinks($content_id, $obj->type, $obj->tags);
            
            if (!$result) {
                return false;
            }
        }*/

        return true;
    }
    
    public static function toObject(array $row)
    {
        $contact = new TfishContact();
        foreach ($row as $key => $value) {
            if (isset($contact->$key)) {
                $contact->$key = $value;
            }
        }

        return $contact;
    }
    
    public static function update(TfishContact $obj)
    {
        $clean_id = TfishFilter::isInt($obj->id, 1) ? (int) $obj->id : 0;
        $key_values = $obj->toArray();
        unset($key_values['submission_time']); // Submission time should not be overwritten.

        // Tags are stored in a separate table and must be handled in a separate query.
        unset($key_values['tags']);

        //$saved_object = self::getObject($clean_id);

        // Update tags.
        /*$result = TfishTaglinkHandler::updateTaglinks($clean_id, $obj->type, $obj->tags);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_TAGLINK_UPDATE_FAILED, E_USER_NOTICE);
            return false;
        }*/

        // Update the content object.
        $result = TfishDatabase::update('contact', $clean_id, $key_values);
        
        if (!$result) {
            trigger_error(TFISH_ERROR_INSERTION_FAILED, E_USER_ERROR);
        }
        
        unset($result);

        return true;
    }
    
}
