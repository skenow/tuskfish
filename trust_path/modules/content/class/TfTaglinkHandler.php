<?php

/**
 * TfTaglinkHandler class file.
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
 * @uses        TfContentTypes Whitelist of sanctioned content subclasses.
 * @var         TfValidator $validator Instance of the Tuskfish data validator class.
 * @var         TfDatabase $db Instance of the Tuskfish database class.
 * @var         TfCriteriaFactory $criteriaFactory Instance of the Tuskfish criteria factory class.
 * @var         TfCriteriaItemFactory $itemFactory Instance of the Tuskfish criteria item
 * factory class.
 */
class TfTaglinkHandler
{
    
    use TfContentTypes;
    
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $itemFactory;
    
    public function __construct(TfValidator $validator, TfDatabase $db, 
            TfCriteriaFactory $criteriaFactory, TfCriteriaItemFactory $itemFactory)
    {
        $this->validator = $validator;
        $this->db = $db;
        $this->criteriaFactory = $criteriaFactory;
        $this->itemFactory = $itemFactory;
    }

    /**
     * Delete taglinks associated with a particular content object.
     * 
     * @param TfContentObject $obj A content subclass object.
     * @return bool True for success, false on failure.
     */
    public function deleteTaglinks(TfContentObject $obj)
    {
        if ($this->validator->isInt($obj->id, 1)) {
            $cleanContentId = (int) $obj->id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        $criteria = $this->criteriaFactory->getCriteria();
        
        if ($obj->type === 'TfTag') {
            $criteria->add($this->itemFactory->getItem('tagId', $cleanContentId));
        } else {
            $criteria->add($this->itemFactory->getItem('contentId', $cleanContentId));
        }
        
        $result = $this->db->deleteAll('taglink', $criteria);
        
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
     * @param int $contentId ID of content object.
     * @param string $type Type of content object as whitelisted in TfTaglinkHandler::getType().
     * @param array $tags IDs of tags as integers.
     * @return bool True on success false on failure.
     */
    public function insertTaglinks(int $contentId, string $type, array $tags)
    {
        if ($this->validator->isInt($contentId, 1)) {
            $cleanContentId = (int) $contentId;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            exit;
        }

        $typeList = $this->getTypes();
        
        if ($this->validator->isAlpha($type) && array_key_exists($type, $typeList)) {
            $cleanType = $this->validator->trimString($type);
        } else {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
            exit;
        }

        $cleanTags = array();
        
        foreach ($tags as $tagId) {
            $tag = array();
            
            if ($this->validator->isInt($tagId, 1)) {
                $tag['tagId'] = (int) $tagId;
            } else {
                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            }
            
            $tag['contentId'] = $cleanContentId;
            $tag['contentType'] = $cleanType;
            $cleanTags[] = $tag;
            unset($tag);
        }
        foreach ($cleanTags as $cleanTag) {
            $result = $this->db->insert('taglink', $cleanTag);
            
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
     * had their type converted to TfTag lose all taglinks (tags are not allowed to reference
     * tags).
     * 
     * @param int $id ID of target content object.
     * @param string $type Type of content object as whitelisted in TfTaglinkHandler::getType().
     * @param array $tags IDs of tags as integers.
     * @return bool True on success false on failure.
     */
    public function updateTaglinks(int $id, string $type, array $tags = null)
    {
        // Validate ID.
        if ($this->validator->isInt($id, 1)) {
            $cleanId = (int) $id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }

        // Validate type.
        $typeList = $this->getTypes();
        
        if ($this->validator->isAlpha($type) && array_key_exists($type, $typeList)) {
            $cleanType = $this->validator->trimString($type);
        } else {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
            exit;
        }

        // Validate tags.
        $cleanTagId = array();
        
        if ($this->validator->isArray($tags)) {
            foreach ($tags as $tag) {
                if ($this->validator->isInt($tag, 1)) {
                    $cleanTagId[] = (int) $tag;
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
                unset($tag);
            }
        }

        // Delete any existing tags.
        $criteria = $this->criteriaFactory->getCriteria();
        $criteria->add($this->itemFactory->getItem('contentId', $cleanId));
        $result = $this->db->deleteAll('taglink', $criteria);
        
        if (!$result) {
            return false;
        }
        
        unset($result);

        // If the content object is a tag, it is not allowed to have taglinks, so there is no need
        // to proceed to insert new ones.
        if ($cleanType === 'TfTag') {
            return true;
        }
        
        // Insert new taglinks, if any.
        $cleanTags = array();
        
        foreach ($cleanTagId as $tagId) {
            $tag = array();
            
            if ($this->validator->isInt($tagId, 1)) {
                $tag['tagId'] = (int) $tagId;
            } else {
                trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            }
            
            $tag['contentId'] = $cleanId;
            $tag['contentType'] = $cleanType;
            $cleanTags[] = $tag;
            unset($tag);
        }

        // Insert the new taglinks.
        foreach ($cleanTags as $cleanTag) {
            $result = $this->db->insert('taglink', $cleanTag);
            
            if (!$result) {
                return false;
            }
        }

        return true;
    }

}
