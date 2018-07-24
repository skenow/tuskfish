<?php
/**
 * TfBlockHandler class file.
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
 * Base handler class for block objects.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */
class TfBlockHandler extends TfContentHandler
{
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfCriteriaItemFactory $criteriaItemFactory,
            TfFileHandler $fileHandler, TfTaglinkHandler $taglinkHandler)
    {
        parent::__construct($validator, $db, $criteriaFactory, $criteriaItemFactory,
                $fileHandler, $taglinkHandler);
    }
    
    /**
     * Count TfBlock objects, optionally matching conditions specified in a TfCriteria object.
     * 
     * @param object $criteria TfCriteria object used to build conditional database query.
     * @return int $count Count of TfBlock objects matching conditions.
     */
    public function getCount(TfCriteria $criteria = null)
    {
        if (!isset($criteria)) {
            $criteria = $this->criteriaFactory->getCriteria();
        }

        // Unset any pre-existing object type criteria.
        $typeKey = $this->getTypeIndex($criteria->item);
        
        if (isset($typeKey)) {
            $criteria->unsetType($typeKey);
        }

        // Set new type criteria specific to this object.
        $criteria->add($this->itemFactory->getItem('type', 'TfBlock'));
        $count = parent::getcount($criteria);

        return $count;
    }
    
    /**
     * Get TfBlock objects, optionally matching conditions specified with a TfCriteria object.
     * 
     * Note that the article type is automatically set, so when calling
     * TfBlockHandler::getObjects($criteria) it is unnecessary to set the object type.
     * However, if you want to use TfContentHandler::getObjects($criteria) then you do need to
     * specify the object type, otherwise you will get all types of content returned. it is
     * acceptable to use either handler, although probably good practice to use the object-
     * specific one when you know you want a specific kind of object.
     * 
     * @param object $criteria TfCriteria object used to build conditional database query.
     * @return array $objects content objects.
     */
    public function getObjects(TfCriteria $criteria = null)
    {
        if (!isset($criteria)) {
            $criteria = $this->criteriaFactory->getCriteria();
        }

        // Unset any pre-existing object type criteria.
        $typeKey = $this->getTypeIndex($criteria->item);
        
        if (isset($typeKey)) {
            $criteria->unsetType($typeKey);
        }

        // Set new type criteria specific to this object.
        $criteria->add($this->itemFactory->getItem('type', 'TfBlock'));
        $objects = parent::getObjects($criteria);

        return $objects;
    }

}
