<?php

/**
 * TfCollectionHandler class file.
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
 * Handler class for collection content objects.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */
class TfCollectionHandler extends TfContentHandler
{
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfCriteriaItemFactory $criteriaItemFactory,
            TfFileHandler $fileHandler, TfTaglinkHandler $taglinkHandler)
    {
        parent::__construct($validator, $db, $criteriaFactory, $criteriaItemFactory,
                $fileHandler, $taglinkHandler);
    }

    /**
     * Count TfCollection objects, optionally matching conditions specified with a TfCriteria\
     * object.
     * 
     * @param object $criteria TfCriteria object used to build conditional database query.
     * @return int $count Number of collection objects matching the criteria.
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
        $criteria->add($this->itemFactory->getItem('type', 'TfCollection'));
        $count = parent::getcount($criteria);

        return $count;
    }

    /**
     * Get TfCollection objects, optionally matching conditions specified with a TfCriteria
     * object.
     * 
     * Note that the object type is automatically set, so it is unnecessary to set it when calling
     * TfCollectionHandler::getObjects($criteria). However, if you want to use the generic
     * handler TfContentHandler::getObjects($criteria) then you do need to specify the object
     * type, otherwise you will get all types of content returned. It is acceptable to use either
     * handler, although good practice to use the type-specific one when you know you want a
     * specific kind of object.
     * 
     * @param object $criteria TfCriteria object used to build conditional database query.
     * @return array $objects TfCollection objects.
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
        $criteria->add($this->itemFactory->getItem('type', 'TfCollection'));
        $objects = parent::getObjects($criteria);

        return $objects;
    }

    /**
     * Get a select box listing a tree of parent (TfCollection) objects.
     * 
     * @param int $selected Currently selected option.
     * @return string HTML select box.
     */
    public function getParentSelectBox(int $selected = 0)
    {
        $cleanSelected = $this->validator->isInt($selected, 1) ? $selected : 0;
        $options = array(0 => TFISH_SELECT_PARENT);
        $selectBox = '';

        $criteria = $this->criteriaFactory->getCriteria();
        $criteria->add($this->itemFactory->getItem('type', 'TfCollection'));
        $criteria->setOrder('title');
        $criteria->setOrderType('ASC');
        $options = $options + $this->getListOfObjectTitles($criteria);

        $selectBox = '<select id="parent" name="parent" class="form-control">';
        
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                
                if ($key === $cleanSelected) {
                    $selectBox .= '<option value="' . $key . '" selected>' . $value . '</option>';
                } else {
                    $selectBox .= '<option value="' . $key . '">' . $value . '</option>';
                }
            }
        }
        
        $selectBox .= '</select>';

        return $selectBox;
    }

}