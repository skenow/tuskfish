<?php

/**
 * TfTagHandler class file.
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
 * Handler class for tag content objects.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */
class TfTagHandler extends TfContentHandler
{
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfCriteriaItemFactory $criteriaItemFactory,
            TfFileHandler $fileHandler, TfTaglinkHandler $taglinkHandler)
    {
        parent::__construct($validator, $db, $criteriaFactory, $criteriaItemFactory,
                $fileHandler, $taglinkHandler);
    }
    
    /**
     * Build a select box from an arbitrary array of tags.
     * 
     * Use this when you need to customise a tag select box. Pass in an array of the tags you want
     * to use as $tag_list as key => value pairs. If you have multiple select boxes on one page then
     * you need to assign them different keys and listen for matching input parameters. If you have
     * organised tags into collections, you can run a query to retrieve that subset using the 
     * parental ID as a selection criteria.
     * 
     * @param int $selected ID of selected option.
     * @param array $tag_list Array of options in tagId => title format.
     * @param string $key_name The input parameter name you want to use as key for this select box.
     * Defaults to 'tagId'.
     * @param string $zero_option The string that will be displayed for the 'zero' or no selection
     * option.
     * @return string HTML select box.
     */
    public function getArbitraryTagSelectBox($selected = null, $tag_list = array(),
            $key_name = null, $zero_option = TFISH_SELECT_TAGS)
    {
        // Initialise variables.
        $selectBox = '';
        $clean_key_name = '';
        $cleanTag_list = array();

        // Validate input.
        // ID of a previously selected tag, if any.
        $cleanSelected = (isset($selected) && $this->validator->isInt($selected, 1))
                ? (int) $selected : null;
        
        if ($this->validator->isArray($tag_list) && !empty($tag_list)) {
            asort($tag_list);
            
            foreach ($tag_list as $key => $value) {
                $clean_key = (int) $key;
                $clean_value = $this->validator->escapeForXss($this->validator->trimString($value));
                $cleanTag_list[$clean_key] = $clean_value;
                unset($key, $clean_key, $value, $clean_value);
            }
        }
        
        // The text to display in the zero option of the select box.
        $clean_zero_option = $this->validator->escapeForXss($this->validator->trimString($zero_option));
        $clean_key_name = isset($key_name)
                ? $this->validator->escapeForXss($this->validator->trimString($key_name)) : 'tagId';

        // Build the select box.
        $cleanTag_list = array(0 => $clean_zero_option) + $cleanTag_list;
        $selectBox = '<select class="form-control custom-select" name="' . $clean_key_name . '" id="'
                . $clean_key_name . '" onchange="this.form.submit()">';
        
        foreach ($cleanTag_list as $key => $value) {
            $selectBox .= ($key === $selected) ? '<option value="' . $key . '" selected>' . $value
                    . '</option>' : '<option value="' . $key . '">' . $value . '</option>';
        }
        
        $selectBox .= '</select>';

        return $selectBox;
    }

    /**
     * Count TfTag objects, optionally matching conditions specified with a TfCriteria object.
     * 
     * @param object $criteria TfCriteria object used to build conditional database query.
     * @return int $count Number of TfTagObjects that match the criteria.
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
        $criteria->add($this->itemFactory->getItem('type', 'TfTag'));
        $count = parent::getcount($criteria);

        return $count;
    }

    /**
     * Get TfTag objects, optionally matching conditions specified with a TfCriteria object.
     * 
     * Note that the object type is automatically set, so it is unnecessary to set it when calling
     * TfTagHandler::getObjects($criteria). However, if you want to use the generic handler
     * TfContentHandler::getObjects($criteria) then you do need to specify the object type,
     * otherwise you will get all types of content returned. It is acceptable to use either handler,
     * although good practice to use the type-specific one when you know you want a specific kind of
     * object.
     * 
     * @param object $criteria TfCriteria object used to build conditional database query.
     * @return array $objects Array of TfTag objects.
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
        $criteria->add($this->itemFactory->getItem('type', 'TfTag'));
        $objects = parent::getObjects($criteria);

        return $objects;
    }

    /**
     * Generates a tag select box control.
     * 
     * Use the $online_only parameter to control whether you retrieve all tags, or just those marked
     * as online. Essentially this provides a way to keep your select box uncluttered; mark tags
     * that are not important enough to serve as navigation elements as 'offline'. They are still
     * available, but they won't appear in the select box list.
     * 
     * @param int $selected ID of selected option.
     * @param string $type Type of content object, eg. TfArticle.
     * @param string $zero_option The string that will be displayed for the 'zero' or no selection
     * option.
     * @param bool $online_only Get all tags or just those marked online.
     * @return bool|string False if no tags or a HTML select box if there are.
     */
    public function getTagSelectBox(int $selected = null, string $type = '',
            string $zero_option = TFISH_SELECT_TAGS, bool $online_only = true)
    {
        $selectBox = '';
        $tag_list = array();

        $cleanSelected = (isset($selected) && $this->validator->isInt($selected, 1))
                ? (int) $selected : null;
        $clean_zero_option = $this->validator->escapeForXss($this->validator->trimString($zero_option));
        $cleanType = $this->isSanctionedType($type)
                ? $this->validator->trimString($type) : null;
        $cleanOnline_only = $this->validator->isBool($online_only) ? (bool) $online_only : true;
        $tag_list = $this->getActiveTagList($cleanType, $cleanOnline_only);
        
        if ($tag_list) {
            $selectBox = $this->getArbitraryTagSelectBox($cleanSelected, $tag_list, 'tagId', $clean_zero_option);
        } else {
            $selectBox = false;
        }
        
        return $selectBox;
    }

}
