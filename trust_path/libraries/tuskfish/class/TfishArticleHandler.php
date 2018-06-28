<?php

/**
 * TfishArticleHandler class file.
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
 * Handler class for article content objects.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */
class TfishArticleHandler extends TfishContentHandler
{

    /**
     * Get TfishArticle objects, optionally matching conditions specified in a TfishCriteria object.
     * 
     * Note that the article type is automatically set, so when calling 
     * $this->getObjects($criteria) it is unnecessary to set the object type.
     * However, if you want to use the generic TfishContentHandler version of getObjects($criteria)
     * then you do need to specify the object type, otherwise you will get all types of content 
     * returned. it is acceptable to use either handler, although probably good practice to use the
     * object-specific one when you know you want a specific kind of object.
     * 
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return array $objects TfishArticle objects.
     */
    public function getObjects(TfishCriteria $criteria = null)
    {
        if (!isset($criteria)) {
            $criteria = new TfishCriteria();
        }

        // Unset any pre-existing object type criteria.
        $type_key = $this->getTypeIndex($criteria->item);
        
        if (isset($type_key)) {
            $criteria->killType($type_key);
        }

        // Set new type criteria specific to this object.
        $criteria->add(new TfishCriteriaItem('type', 'TfishArticle'));
        $objects = parent::getObjects($criteria);

        return $objects;
    }

    /**
     * Get TfishArticle objects, optionally matching conditions specified in a TfishCriteria object.
     * 
     * Note that the object type is automatically set, so it is unnecessary to set it, unless you
     * are using the generic (TfishContentHandler) handler. It is acceptable to use either handler,
     * although good practice to use the type-specific one when you know you want a specific kind of
     * object.
     * 
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return array $objects TfishArticle objects.
     */
    public function getCount(TfishCriteria $criteria = null)
    {
        if (!isset($criteria)) {
            $criteria = new TfishCriteria();
        }

        // Unset any pre-existing object type criteria.
        $type_key = $this->getTypeIndex($criteria->item);
        
        if (isset($type_key)) {
            $criteria->killType($type_key);
        }

        // Set new type criteria specific to this object.
        $criteria->add(new TfishCriteriaItem('type', 'TfishArticle'));
        $count = parent::getcount($criteria);

        return $count;
    }

}
