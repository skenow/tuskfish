<?php

//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
// Author: Kazumi Ono (AKA onokazu)                                          //
// URL: http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/ //
// Project: The XOOPS Project                                                //
// ------------------------------------------------------------------------- //

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Represents hierarchical relationships between collections and member content objects.
 * 
 * Essentially this is a category tree, although collections (category analogues) are fully-fledged
 * content objects in their own right. Pass in an array of collection objects; you can choose to
 * pass in all collection objects or you can pass in a branch, in which case the tree will just
 * consist of descendants of the root node.
 * 
 * As for the name, don't ask.
 *
 * @copyright	http://smartfactory.ca The SmartFactory
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @author		Kazumi Ono 	<onokazu@xoops.org>
 * @author		marcan aka Marc-André Lanciault <marcan@smartfactory.ca>
 * @author		Madfish <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		core
 */
class TfishAngryTree
{

    /** @var array $_objects Array of objects to be assembled into a category tree. */
    public $_objects;
    
    /** @var string $_myId Name of object ID field. */
    public $_myId;
    
    /** @var string $_parentId Name of parent object ID field. */
    private $_parentId;
    
    /** @var int $_rootId Name of root object ID, to be used as the root node of the tree. */
    private $_rootId = null;
    
    /** @var object $_tree Associative array that comprises the category tree. */
    public $_tree = array();

    /**
     * Constructor
     *
     * @param array $objectArr Array of collection objects
     * @param string $myId field name of object ID
     * @param string $parentId field name of parent object ID
     * @param string $rootId field name of root object ID
     * */
    function __construct(&$objectArr, $myId, $parentId, $rootId = null)
    {
        $this->_objects = & $objectArr;
        $this->_myId = $myId;
        $this->_parentId = $parentId;
        if (isset($rootId)) {
            $this->_rootId = $rootId;
        }
        $this->_initialize();
    }

    /**
     * Initialize the object
     * */
    private function _initialize()
    {
        foreach (array_keys($this->_objects) as $i) {

            $id_field = $this->_myId;
            $key1 = $this->_objects[$i]->$id_field;
            $this->_tree[$key1]['obj'] = & $this->_objects[$i];
            $parent_id_field = $this->_parentId;
            $key2 = $this->_objects[$i]->$parent_id_field;
            $this->_tree[$key1]['parent'] = $key2;
            $this->_tree[$key2]['child'][] = $key1;
            if (isset($this->_rootId)) {
                $this->_tree[$key1]['root'] = $this->_objects[$i]->getVar($this->_rootId);
            }
        }
    }

    /**
     * Get a category tree
     *
     * @return  array   Associative array comprising the tree
     * */
    public function &getTree()
    {
        return $this->_tree;
    }

    /**
     * returns an object from the category tree specified by its id
     *
     * @param   string  $key    ID of the object to retrieve
     * @return  object  object within the tree
     * */
    public function &getByKey($key)
    {
        return $this->_tree[$key]['obj'];
    }

    /**
     * returns an array of all the first child object of an object specified by its id
     *
     * @param   string $key ID of the parent object
     * @return  array of children of the parent
     * */
    public function getFirstChild($key)
    {
        $ret = array();
        if (isset($this->_tree[$key]['child'])) {
            foreach ($this->_tree[$key]['child'] as $childkey) {
                $ret[$childkey] = & $this->_tree[$childkey]['obj'];
            }
        }
        return $ret;
    }

    /**
     * returns an array of all child objects of an object specified by its id
     *
     * @param string $key ID of the parent
     * @param array $ret array of children from previous recursions (empty when called from client)
     * @return array of child nodes.
     * */
    public function getAllChild($key, $ret = array())
    {
        if (isset($this->_tree[$key]['child'])) {
            foreach ($this->_tree[$key]['child'] as $childkey) {
                $ret[$childkey] = & $this->_tree[$childkey]['obj'];
                $children = & $this->getAllChild($childkey, $ret);
                foreach (array_keys($children) as $newkey) {
                    $ret[$newkey] = & $children[$newkey];
                }
            }
        }
        return $ret;
    }

    /**
     * Returns an array of all parent objects.
     * 
     * The key of returned array represents how many levels up from the specified object.
     *
     * @param string $key ID of the child object
     * @param array $ret result from previous recursions (empty when called from outside)
     * @param int $uplevel level of recursion (empty when called from outside)
     * @return array of parent nodes.
     * */
    public function getAllParent($key, $ret = array(), $uplevel = 1)
    {
        if (isset($this->_tree[$key]['parent']) && isset($this->_tree[$this->_tree[$key]['parent']]['obj'])) {
            $ret[$uplevel] = & $this->_tree[$this->_tree[$key]['parent']]['obj'];
            $parents = & $this->getAllParent($this->_tree[$key]['parent'], $ret, $uplevel + 1);
            foreach (array_keys($parents) as $newkey) {
                $ret[$newkey] = & $parents[$newkey];
            }
        }
        return $ret;
    }

    /**
     * Make options for a select box from
     *
     * @param string $fieldName name of the member variable from the node objects that should
     * be used as the title for the options.
     * @param string $selected Value to display as selected
     * @param int $key ID of the object to display as the root of select options
     * @param string $ret result from previous recursions (reference to a string when called from outside)
     * @param string $prefix_orig string to indent items at deeper levels
     * @param string $prefix_curr string to indent the current item
     */
    private function _makeSelBoxOptions($fieldName, $selected, $key, &$ret, $prefix_orig, $prefix_curr = '')
    {
        if ($key > 0) {
            $id_field = $this->_myId;
            $value = $this->_tree[$key]['obj']->$id_field;
            $ret[$value] = $prefix_curr . $this->_tree[$key]['obj']->$fieldName;
            $prefix_curr .= $prefix_orig;
        }
        if (isset($this->_tree[$key]['child']) && !empty($this->_tree[$key]['child'])) {
            foreach ($this->_tree[$key]['child'] as $childkey) {
                $this->_makeSelBoxOptions($fieldName, $selected, $childkey, $ret, $prefix_orig, $prefix_curr);
            }
        }
    }

    /**
     * Make select box options from the tree
     * 
     * Returns an indented array of options that can be used to build a HTML select box, indented
     * according to the relative hierarchy.
     *
     * @param string $name of the select box
     * @param string $fieldName of the member variable from the node objects that
     * should be used as the title for the options.
     * @param string $prefix string to indent deeper levels
     * @param int $selected value to display as selected
     * @param bool $addEmptyOption set TRUE to add an empty option with value "0" at the top of the hierarchy
     * @param int $key ID of the object to display as the root of select options
     * @return array select box options as key => value pairs
     * */
    public function makeSelBox($name, $fieldName, $prefix = '-- ', $selected = '', $addEmptyOption = FALSE, $key = 0)
    {
        $ret = array(0 => TFISH_SELECT_BOX_ZERO_OPTION);

        $this->_makeSelBoxOptions($fieldName, $selected, $key, $ret, $prefix);
        return $ret;
    }

    /**
     * Make a select box of parent collections from the tree.
     * 
     * @param int $selected element
     * @param int $key
     * @return string HTML select box
     */
    public function makeParentSelectBox($selected = 0, $key = 0)
    {
        $ret = array(0 => TFISH_SELECT_PARENT);

        $this->_makeSelBoxOptions('title', $selected, $key, $ret, '-- ');
        return $ret;
    }

}
