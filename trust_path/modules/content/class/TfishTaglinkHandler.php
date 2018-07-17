<?php

/**
 * TfishTaglinkHandler class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Handler class for taglink objects.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */
class TfishTaglinkHandler
{
    
    use TfishContentTypes;
    
    protected $validator;
    
    public function __construct(object $tfish_validator)
    {
        if (is_object($tfish_validator)) {
            $this->validator = $tfish_validator;
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
    }

    /**
     * Delete taglinks associated with a particular content object.
     * 
     * @param object $obj A TfishContentObject subclass object.
     * @return bool True for success, false on failure.
     */
    public function deleteTaglinks(object $obj)
    {
        if ($this->validator->isInt($obj->id, 1)) {
            $clean_content_id = (int) $obj->id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        $criteria = new TfishCriteria($this->validator);
        
        if ($obj->type === 'TfishTag') {
            $criteria->add(new TfishCriteriaItem('tag_id', $clean_content_id));
        } else {
            $criteria->add(new TfishCriteriaItem('content_id', $clean_content_id));
        }
        
        $result = TfishDatabase::deleteAll('taglink', $criteria);
        
        if (!$result) {
            return false;
        }

        return true;
    }

    /**
     * Insert taglinks to the taglink table.
     * 
     * Taglinks represent relationships between tags and content objects.
     * 
     * @param int $content_id ID of content object.
     * @param string $type Type of content object as whitelisted in TfishTaglinkHandler::getType().
     * @param array $tags IDs of tags as integers.
     * @return bool True on success false on failure.
     */
    public function insertTaglinks(int $content_id, string $type, array $tags)
    {
        if ($this->validator->isInt($content_id, 1)) {
            $clean_content_id = (int) $content_id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            exit;
        }

        $typeList = $this->getTypes();
        
        if ($this->validator->isAlpha($type) && array_key_exists($type, $typeList)) {
            $clean_type = $this->validator->trimString($type);
        } else {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
            exit;
        }

        $clean_tags = array();
        
        foreach ($tags as $tag_id) {
            $tag = array();
            
            if ($this->validator->isInt($tag_id, 1)) {
                $tag['tag_id'] = (int) $tag_id;
            } else {
                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            }
            
            $tag['content_id'] = $clean_content_id;
            $tag['content_type'] = $clean_type;
            $clean_tags[] = $tag;
            unset($tag);
        }
        foreach ($clean_tags as $clean_tag) {
            $result = TfishDatabase::insert('taglink', $clean_tag);
            
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * Updates taglinks for a particular content object.
     * 
     * Old taglinks are deleted, newly designated set of taglinks are inserted. Objects that have
     * had their type converted to TfishTag lose all taglinks (tags are not allowed to reference
     * tags).
     * 
     * @param int $id ID of target content object.
     * @param string $type Type of content object as whitelisted in TfishTaglinkHandler::getType().
     * @param array $tags IDs of tags as integers.
     * @return bool True on success false on failure.
     */
    public function updateTaglinks(int $id, string $type, array $tags = null)
    {
        // Validate ID.
        if ($this->validator->isInt($id, 1)) {
            $clean_id = (int) $id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }

        // Validate type.
        $typeList = $this->getTypes();
        
        if ($this->validator->isAlpha($type) && array_key_exists($type, $typeList)) {
            $clean_type = $this->validator->trimString($type);
        } else {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
            exit;
        }

        // Validate tags.
        $clean_tag_id = array();
        
        if ($this->validator->isArray($tags)) {
            foreach ($tags as $tag) {
                if ($this->validator->isInt($tag, 1)) {
                    $clean_tag_id[] = (int) $tag;
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
                unset($tag);
            }
        }

        // Delete any existing tags.
        $criteria = new TfishCriteria($this->validator);
        $criteria->add(new TfishCriteriaItem('content_id', $clean_id));
        $result = TfishDatabase::deleteAll('taglink', $criteria);
        
        if (!$result) {
            return false;
        }
        
        unset($result);

        // If the content object is a tag, it is not allowed to have taglinks, so there is no need
        // to proceed to insert new ones.
        if ($type === 'TfishTag') {
            return true;
        }
        
        // Insert new taglinks, if any.
        $clean_tags = array();
        
        foreach ($clean_tag_id as $tag_id) {
            $tag = array();
            
            if ($this->validator->isInt($tag_id, 1)) {
                $tag['tag_id'] = (int) $tag_id;
            } else {
                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            }
            
            $tag['content_id'] = $clean_id;
            $tag['content_type'] = $type;
            $clean_tags[] = $tag;
            unset($tag);
        }

        // Insert the new taglinks.
        foreach ($clean_tags as $clean_tag) {
            $result = TfishDatabase::insert('taglink', $clean_tag);
            
            if (!$result) {
                return false;
            }
        }

        return true;
    }

}
