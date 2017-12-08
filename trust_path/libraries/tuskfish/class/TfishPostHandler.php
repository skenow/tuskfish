<?php

/**
 * TfishPostHandler class file.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Handler class for post content objects.
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */
class TfishPostHandler extends TfishContentHandler
{
    /**
     * Delete a post without disrupting the thread tree.
     * 
     * If a post has children deleting it outright would destroy the thread
     * structure. This method checks for the existence of children and, if they
     * exist, simply replaces the content of the post with [deleted] rather than
     * fully destroying the object. This ensures that the thread structure is
     * preserved. If there are no children then the post will be hard-deleted as
     * it has no useful function.
     */
    public static function deletePost($id)
    {
        // Check if post exists.
        
        // Check if post has children.
        
        // If children, replace sensitive content with [deleted]. Should
        // top-level posts (threads) be disallowed from deletion? Check how
        // Reddit handles things.
        
        // If no children, trigger hard delete by calling parent method.
    }
    
    /**
     * Retrieve a thread (or part of a thread) descending from a particular post $id.
     * 
     * Retrieves children of a particular post, descending an arbitrary number of levels.
     * Use this method to display relevant sections of threads, using $child_levels to
     * control the amount of nesting so as not to break the page layout. 
     * 
     * @param int $id ID of parent post.
     * @return array $objects TfishPost objects.
     */
    public static function getThread($post_id, $child_levels = 0)
    {
        
    }
    
    /**
     * Elevate a post to form a new thread in an arbitrary forum.
     * 
     * Moves a post to form a new thread in an arbitrary forum. Child posts
     * will follow, so an entire discussion can be moved at once. Can also be used to
     * elevate a comment to thread status within the same forum.
     * 
     * @param type $post_id
     * @param type $forum_id
     */
    public static function moveThread($post_id, $forum_id)
    {
        
    }

    /**
     * Get TfishPost objects, optionally matching conditions specified in a TfishCriteria object.
     * 
     * Note that the post type is automatically set, so when calling
     * TfishPostHandler::getObjects($criteria) it is unnecessary to set the object type.
     * However, if you want to use TfishContentHandler::getObjects($criteria) then you do need to
     * specify the object type, otherwise you will get all types of content returned. it is
     * acceptable to use either handler, although probably good practice to use the object-
     * specific one when you know you want a specific kind of object.
     * 
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return array $objects TfishPost objects.
     */
    public static function getObjects($criteria = false)
    {
        if (!$criteria) {
            $criteria = new TfishCriteria();
        }
        
        if (!is_a($criteria, 'TfishCriteria')) {
            trigger_error(TFISH_ERROR_NOT_CRITERIA_OBJECT, E_USER_ERROR);
        }

        // Unset any pre-existing object type criteria.
        $type_key = self::getTypeIndex($criteria->item);
        
        if (isset($type_key)) {
            $criteria->killType($type_key);
        }

        // Set new type criteria specific to this object.
        $criteria->add(new TfishCriteriaItem('type', 'TfishPost'));
        $objects = parent::getObjects($criteria);

        return $objects;
    }    

    /**
     * Count TfishPost objects, optionally matching conditions specified in a TfishCriteria object.
     * 
     * Note that the object type is automatically set, so it is unnecessary to set it when calling
     * TfishPostHandler::getObjects($criteria). However, if you want to use the generic handler
     * TfishContentHandler::getObjects($criteria) then you do need to specify the object type,
     * otherwise you will get all types of content returned. It is acceptable to use either handler,
     * although good practice to use the type-specific one when you know you want a specific kind of
     * object.
     * 
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return array $objects TfishPost objects.
     */
    public static function getCount($criteria = false)
    {
        if (!$criteria) {
            $criteria = new TfishCriteria();
        }
        
        if (!is_a($criteria, 'TfishCriteria')) {
            trigger_error(TFISH_ERROR_NOT_CRITERIA_OBJECT, E_USER_ERROR);
        }

        // Unset any pre-existing object type criteria.
        $type_key = self::getTypeIndex($criteria->item);
        
        if (isset($type_key)) {
            $criteria->killType($type_key);
        }

        // Set new type criteria specific to this object.
        $criteria->add(new TfishCriteriaItem('type', 'TfishPost'));
        $count = parent::getcount($criteria);

        return $count;
    }

}
