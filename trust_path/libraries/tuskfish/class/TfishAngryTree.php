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

/**
 * TfishAngryTree class file.
 * 
 * @copyright   XOOPS.org (https://xoops.org) 2000
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Kazumi Ono 	<onokazu@xoops.org>
 * @author      marcan aka Marc-André Lanciault <marcan@smartfactory.ca>
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */
// Enable strict type declaration.
declare(strict_types=1);

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
 * @copyright   http://smartfactory.ca The SmartFactory
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @author      Kazumi Ono 	<onokazu@xoops.org>
 * @author      marcan aka Marc-André Lanciault <marcan@smartfactory.ca>
 * @author      Madfish <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     core
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
     * @param array $objectArr Array of collection objects.
     * @param string $myId Name of object ID field.
     * @param string $parentId Name of parent object ID field.
     * @param string $rootId Name of root object ID field.
     * */
    function __construct(array &$objectArr, string $myId, string $parentId, string $rootId = null)
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
     * Initialise the tree.
     * 
     * @internal
     */
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
     * Get a category tree.
     *
     * @return  array   Associative array comprising the tree.
     * */
    public function &getTree()
    {
        return $this->_tree;
    }

    /**
     * returns an object from the category tree specified by its id.
     *
     * @param   string  $key    ID of the object to retrieve.
     * @return  object  Object (node) within the tree.
     * */
    public function &getByKey(int $key)
    {
        return $this->_tree[$key]['obj'];
    }

    /**
     * Returns an array of all the first child objects of a parental object specified by its id.
     *
     * @param   string $key ID of the parent object.
     * @return  array Array of child objects.
     * */
    public function getFirstChild(int $key)
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
     * Returns an array of all child objects of a parental object specified by its ID.
     *
     * @param string $key ID of the parent.
     * @param array $ret Array of child objects from previous recursions (empty if called from client).
     * @return array Array of child nodes.
     * */
    public function getAllChild(int $key, array $ret = array())
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
     * @param string $key ID of the child object.
     * @param array $ret Result from previous recursions (empty when called from outside).
     * @param int $uplevel Level of recursion (empty when called from outside).
     * @return array Array of parent nodes.
     * */
    public function getAllParent(int $key, array $ret = array(), int $uplevel = 1)
    {
        if (isset($this->_tree[$key]['parent']) 
                && isset($this->_tree[$this->_tree[$key]['parent']]['obj'])) {
            $ret[$uplevel] = & $this->_tree[$this->_tree[$key]['parent']]['obj'];
            $parents = & $this->getAllParent($this->_tree[$key]['parent'], $ret, $uplevel + 1);
            
            foreach (array_keys($parents) as $newkey) {
                $ret[$newkey] = & $parents[$newkey];
            }
        }
        
        return $ret;
    }

    /**
     * Make options for a select box from tree.
     *
     * @param string $fieldName Name of the member variable from the node objects that should
     * be used as the title for the options.
     * @param int $selected Value to display as selected.
     * @param int $key ID of the object to display as the root of select options.
     * @param string $ret Result from previous recursions (reference to a string when called from outside).
     * @param string $prefix_orig String to indent items at deeper levels.
     * @param string $prefix_curr String to indent the current item.
     */
    private function _makeSelBoxOptions(string $fieldName, int $selected, int $key, &$ret,
                string $prefix_orig, string $prefix_curr = '')
    {
        if ($key > 0) {
            $id_field = $this->_myId;
            $value = $this->_tree[$key]['obj']->$id_field;
            $ret[$value] = $prefix_curr . $this->_tree[$key]['obj']->$fieldName;
            $prefix_curr .= $prefix_orig;
        }
        
        if (isset($this->_tree[$key]['child']) && !empty($this->_tree[$key]['child'])) {
            foreach ($this->_tree[$key]['child'] as $childkey) {
                $this->_makeSelBoxOptions($fieldName, $selected, $childkey, $ret, $prefix_orig,
                        $prefix_curr);
            }
        }
    }

    /**
     * Make select box options from the tree
     * 
     * Returns an indented array of options that can be used to build a HTML select box, indented
     * according to the relative hierarchy.
     *
     * @param string $name Name of the select box.
     * @param string $fieldName Name of the member variable from the node objects that should be 
     * used as the title field for the options.
     * @param string $prefix String to indent deeper levels.
     * @param int $selected Value to display as selected.
     * @param bool $addEmptyOption Set TRUE to add an empty option with value "0" at the top of the
     * hierarchy.
     * @param int $key ID of the object to display as the root of select options.
     * @return array Select box options as ID => title pairs.
     * */
    public function makeSelBox(string $name, string $fieldName, string $prefix = '-- ',
        int $selected = 0, bool $addEmptyOption = false, int $key = 0)
    {
        $ret = array(0 => TFISH_SELECT_BOX_ZERO_OPTION);
        $this->_makeSelBoxOptions($fieldName, $selected, $key, $ret, $prefix);
        
        return $ret;
    }

    /**
     * Make a select box of parent collections from the tree.
     * 
     * @param int $selected Currently selected option.
     * @param int $key ID of the object to display as root of the select options.
     * @return string HTML select box.
     */
    public function makeParentSelectBox(int $selected = 0, int $key = 0)
    {
        $ret = array(0 => TFISH_SELECT_PARENT);
        $this->_makeSelBoxOptions('title', $selected, $key, $ret, '-- ');
        
        return $ret;
    }

}
