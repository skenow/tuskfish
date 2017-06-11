<?php

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Handler class for taglink objects.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		core
 */
class TfishTaglinkHandler extends TfishContentHandler
{

    /**
     * Delete taglinks associated with a particular content object.
     * 
     * @param int $content_id
     * @return bool true for success false on failure
     */
    public static function deleteTaglinks($content_id)
    {
        if (TfishFilter::isInt($content_id, 1)) {
            $clean_content_id = (int) $content_id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        $criteria = new TfishCriteria();
        $criteria->add(new TfishCriteriaItem('content_id', $clean_content_id));
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
     * @param int $content_id of content object
     * @param string $type alphabetical characters only and whitelisted in TfishTaglinkHandler::getType()
     * @param array $tags as integers
     * @return bool true on success false on failure
     */
    public static function insertTaglinks($content_id, $type, $tags)
    {
        if (TfishFilter::isInt($content_id, 1)) {
            $clean_content_id = (int) $content_id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            exit;
        }

        $typeList = self::getTypes();
        if (TfishFilter::isAlpha($type) && array_key_exists($type, $typeList)) {
            $clean_type = TfishFilter::trimString($type);
        } else {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
            exit;
        }

        $clean_tags = array();
        foreach ($tags as $tag_id) {
            $tag = array();
            if (TfishFilter::isInt($tag_id, 1)) {
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
     * Old taglinks are deleted, newly designated set of taglinks are inserted.
     * 
     * @param int $id of content object
     * @param string $type of content object as whitelisted in TfishTaglinkHandler::getType()
     * @param array $tags as integers
     * @return bool true on success false on failure
     */
    public static function updateTaglinks($id, $type, $tags)
    {
        // Validate ID.
        if (TfishFilter::isInt($id, 1)) {
            $clean_id = (int) $id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }

        // Validate type.
        $typeList = self::getTypes();
        if (TfishFilter::isAlpha($type) && array_key_exists($type, $typeList)) {
            $clean_type = TfishFilter::trimString($type);
        } else {
            trigger_error(TFISH_ERROR_NOT_ALPHA, E_USER_ERROR);
            exit;
        }

        // Validate tags.
        $clean_tag_id = array();
        if (TfishFilter::isArray($tags)) {
            foreach ($tags as $tag) {
                if (TfishFilter::isInt($tag, 1)) {
                    $clean_tag_id[] = (int) $tag;
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
                unset($tag);
            }
        }

        // Delete any existing tags.
        $criteria = new TfishCriteria();
        $criteria->add(new TfishCriteriaItem('content_id', $clean_id));
        $result = TfishDatabase::deleteAll('taglink', $criteria);
        if (!$result) {
            return false;
        }
        unset($result);

        // Insert new taglinks, if any.
        $clean_tags = array();
        foreach ($clean_tag_id as $tag_id) {
            $tag = array();
            if (TfishFilter::isInt($tag_id, 1)) {
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
