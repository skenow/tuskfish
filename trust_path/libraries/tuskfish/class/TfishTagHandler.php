<?php

/**
 * TfishTagHandler class file.
 * 
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		content
 */

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Handler class for tag content objects.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		content
 */
class TfishTagHandler extends TfishContentHandler
{

    /**
     * Count TfishTag objects, optionally matching conditions specified with a TfishCriteria object.
     * 
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return int $count Number of TfishTagObjects that match the criteria.
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
        $criteria->add(new TfishCriteriaItem('type', 'TfishTag'));
        $count = parent::getcount($criteria);

        return $count;
    }

    /**
     * Get TfishTag objects, optionally matching conditions specified with a TfishCriteria object.
     * 
     * Note that the object type is automatically set, so it is unnecessary to set it when calling
     * TfishTagHandler::getObjects($criteria). However, if you want to use the generic handler
     * TfishContentHandler::getObjects($criteria) then you do need to specify the object type,
     * otherwise you will get all types of content returned. It is acceptable to use either handler,
     * although good practice to use the type-specific one when you know you want a specific kind of
     * object.
     * 
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return array $objects Array of TfishTag objects.
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
        $criteria->add(new TfishCriteriaItem('type', 'TfishTag'));
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
     * @param string $type Type of content object.
     * @param string $zero_option The string that will be displayed for the 'zero' or no selection
     * option.
     * @param bool $online_only Get all tags or just those marked online.
     * @return bool|string False if no tags or a HTML select box if there are.
     */
    public static function getTagSelectBox($selected = null, $type = null,
            $zero_option = TFISH_SELECT_TAGS, $online_only = true)
    {
        $select_box = '';
        $tag_list = array();

        // ID of a previously selected tag, if any.
        $clean_selected = (isset($selected) && TfishFilter::isInt($selected, 1))
                ? (int) $selected : null;
        // The text to display in the zero option of the select box.
        $clean_zero_option = TfishFilter::escape(TfishFilter::trimString($zero_option));
        // Used to filter tags relevant to a specific content subclass, eg. TfishArticle.
        $clean_type = TfishContentHandler::isSanctionedType($type)
                ? TfishFilter::trimString($type) : null;
        $clean_online_only = TfishFilter::isBool($online_only) ? (bool) $online_only : true;

        $tag_list = TfishContentHandler::getActiveTagList($clean_type, $clean_online_only);
        
        if (!empty($tag_list)) {
            asort($tag_list);
            $tag_list = array(0 => $clean_zero_option) + $tag_list;
            $select_box = '<select class="form-control" name="tag_id" id="tag_id"'
                    . 'onchange="this.form.submit()">';
            
            foreach ($tag_list as $key => $value) {
                $select_box .= ($key == $selected) ? '<option value="' . $key . '" selected>'
                        . $value . '</option>' : '<option value="' . $key . '">' . $value
                        . '</option>';
            }
            
            $select_box .= '</select>';
            
            return $select_box;
        } else {
            return false;
        }
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
     * @param array $tag_list Array of options in tag_id => title format.
     * @param string $key_name The input parameter name you want to use as key for this select box.
     * Defaults to 'tag_id'.
     * @param string $zero_option The string that will be displayed for the 'zero' or no selection
     * option.
     * @return string HTML select box.
     */
    public static function getArbitraryTagSelectBox($selected = null, $tag_list = array(),
            $key_name = null, $zero_option = TFISH_SELECT_TAGS)
    {
        // Initialise variables.
        $select_box = '';
        $clean_key_name = '';
        $clean_tag_list = array();

        // Validate input.
        // ID of a previously selected tag, if any.
        $clean_selected = (isset($selected) && TfishFilter::isInt($selected, 1))
                ? (int) $selected : null;
        
        if (TfishFilter::isArray($tag_list) && !empty($tag_list)) {
            asort($tag_list);
            
            foreach ($tag_list as $key => $value) {
                $clean_key = (int) $key;
                $clean_value = TfishFilter::escape(TfishFilter::trimString($value));
                $clean_tag_list[$clean_key] = $clean_value;
                unset($key, $clean_key, $value, $clean_value);
            }
        }
        
        // The text to display in the zero option of the select box.
        $clean_zero_option = TfishFilter::escape(TfishFilter::trimString($zero_option));
        $clean_key_name = isset($key_name)
                ? TfishFilter::escape(TfishFilter::trimString($key_name)) : 'tag_id';

        // Build the select box.
        $clean_tag_list = array(0 => $clean_zero_option) + $clean_tag_list;
        $select_box = '<select class="form-control" name="' . $clean_key_name . '" id="'
                . $clean_key_name . '" onchange="this.form.submit()">';
        
        foreach ($clean_tag_list as $key => $value) {
            $select_box .= ($key == $selected) ? '<option value="' . $key . '" selected>' . $value
                    . '</option>' : '<option value="' . $key . '">' . $value . '</option>';
        }
        
        $select_box .= '</select>';

        return $select_box;
    }

}
