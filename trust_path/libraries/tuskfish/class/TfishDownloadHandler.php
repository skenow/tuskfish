<?php

/**
 * TfishDownloadHandler class file.
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
 * Handler class for download content objects.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */
class TfishDownloadHandler extends TfishContentHandler
{

    /**
     * Count TfishDownload objects, optionally matching conditions specified with a TfishCriteria
     * object.
     * 
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return int $count Number of TfishDownload objects that match the criteria.
     */
    public function getCount(TfishCriteria $criteria = null)
    {
        if (!isset($criteria)) {
            $criteria = new TfishCriteria();
        }

        // Unset any pre-existing object type criteria.
        $type_key = $this->getTypeIndex($criteria->item);
        
        if (isset($type_key)) {
            $criteria->unsetType($type_key);
        }

        // Set new type criteria specific to this object.
        $criteria->add(new TfishCriteriaItem('type', 'TfishDownload'));
        $count = parent::getcount($criteria);

        return $count;
    }

    /**
     * Get TfishDownload objects, optionally matching conditions specified with a TfishCriteria
     * object.
     * 
     * Note that the object type is automatically set, so it is unnecessary to set it when calling
     * TfishDownloadHandler::getObjects($criteria). However, if you want to use the generic handler
     * TfishContentHandler::getObjects($criteria) then you do need to specify the object type,
     * otherwise you will get all types of content returned. It is acceptable to use either handler,
     * although good practice to use the type-specific one when you know you want a specific kind of
     * object.
     * 
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return array $objects Array of TfishDownload objects.
     */
    public function getObjects(TfishCriteria $criteria = null)
    {
        if (!isset($criteria)) {
            $criteria = new TfishCriteria();
        }

        // Unset any pre-existing object type criteria.
        $type_key = $this->getTypeIndex($criteria->item);
        
        if (isset($type_key)) {
            $criteria->unsetType($type_key);
        }

        // Set new type criteria specific to this object.
        $criteria->add(new TfishCriteriaItem('type', 'TfishDownload'));
        $objects = parent::getObjects($criteria);

        return $objects;
    }

}
