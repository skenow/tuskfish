<?php
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
 */

if (!defined("TFISH_ROOT_PATH")) die("ICMS root path not defined");

class TfishAngryTree {

	private $_parentId;
	public $_myId;
	private $_rootId = null;
	public $_tree = array();
	public $_objects;

	/**
	 * Constructor
	 *
	 * @param array $objectArr Array of collection objects
	 * @param string $myId field name of object ID
	 * @param string $parentId field name of parent object ID
	 * @param string $rootId field name of root object ID
	 **/
	function __construct(&$objectArr, $myId, $parentId, $rootId = null)
	{
		$this->_objects =& $objectArr;
		$this->_myId = $myId;
		$this->_parentId = $parentId;
		if (isset($rootId)) {
			$this->_rootId = $rootId;
		}
		$this->_initialize();
	}

	/**
	 * Initialize the object
	 **/
	private function _initialize()
	{
		foreach (array_keys($this->_objects) as $i) {

			$id_field = $this->_myId;
			$key1 = $this->_objects[$i]->$id_field;
            $this->_tree[$key1]['obj'] =& $this->_objects[$i];
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
	 * Get the tree
	 *
	 * @return  array   Associative array comprising the tree
	 **/
	public function &getTree()
	{
		return $this->_tree;
	}

	/**
	 * returns an object from the tree specified by its id
	 *
	 * @param   string  $key    ID of the object to retrieve
     * @return  object  Object within the tree
	 **/
	public function &getByKey($key)
	{
		return $this->_tree[$key]['obj'];
	}

	/**
	 * returns an array of all the first child object of an object specified by its id
	 *
	 * @param   string  $key    ID of the parent object
	 * @return  array   Array of children of the parent
	 **/
	public function getFirstChild($key)
	{
		$ret = array();
		if (isset($this->_tree[$key]['child'])) {
			foreach ($this->_tree[$key]['child'] as $childkey) {
				$ret[$childkey] =& $this->_tree[$childkey]['obj'];
			}
		}
		return $ret;
	}

	/**
	 * returns an array of all child objects of an object specified by its id
	 *
	 * @param   string     $key    ID of the parent
	 * @param   array   $ret    (Empty when called from client) Array of children from previous recursions.
	 * @return  array   Array of child nodes.
	 **/
	public function getAllChild($key, $ret = array())
	{
		if (isset($this->_tree[$key]['child'])) {
			foreach ($this->_tree[$key]['child'] as $childkey) {
				$ret[$childkey] =& $this->_tree[$childkey]['obj'];
				$children =& $this->getAllChild($childkey, $ret);
				foreach (array_keys($children) as $newkey) {
					$ret[$newkey] =& $children[$newkey];
				}
			}
		}
		return $ret;
	}

	/**
     * returns an array of all parent objects.
     * the key of returned array represents how many levels up from the specified object
	 *
	 * @param   string     $key    ID of the child object
	 * @param   array   $ret    (empty when called from outside) Result from previous recursions
	 * @param   int $uplevel (empty when called from outside) level of recursion
	 * @return  array   Array of parent nodes.
	 **/
	public function getAllParent($key, $ret = array(), $uplevel = 1)
	{
		if (isset($this->_tree[$key]['parent']) && isset($this->_tree[$this->_tree[$key]['parent']]['obj'])) {
			$ret[$uplevel] =& $this->_tree[$this->_tree[$key]['parent']]['obj'];
			$parents =& $this->getAllParent($this->_tree[$key]['parent'], $ret, $uplevel+1);
			foreach (array_keys($parents) as $newkey) {
				$ret[$newkey] =& $parents[$newkey];
			}
		}
		return $ret;
	}

	/**
	 * Make options for a select box from
	 *
	 * @param   string  $fieldName   Name of the member variable from the
     *  node objects that should be used as the title for the options.
	 * @param   string  $selected    Value to display as selected
	 * @param   int $key         ID of the object to display as the root of select options
     * @param   string  $ret         (reference to a string when called from outside) Result from previous recursions
	 * @param   string  $prefix_orig  String to indent items at deeper levels
	 * @param   string  $prefix_curr  String to indent the current item
	 * @return
     *
     * @access	private
	 **/
	private function _makeSelBoxOptions($fieldName, $selected, $key, &$ret, $prefix_orig, $prefix_curr = '')
	{
        if ($key > 0) {
			$id_field = $this->_myId;
            $value = $this->_tree[$key]['obj']->$id_field;
            $ret[$value] = $prefix_curr.$this->_tree[$key]['obj']->$fieldName;
            $prefix_curr .= $prefix_orig;
        }
        if (isset($this->_tree[$key]['child']) && !empty($this->_tree[$key]['child'])) {
            foreach ($this->_tree[$key]['child'] as $childkey) {
                $this->_makeSelBoxOptions($fieldName, $selected, $childkey, $ret, $prefix_orig, $prefix_curr);
            }
        }
	}

	/**
	 * Make a select box with options from the tree
	 *
	 * @param   string  $name            Name of the select box
	 * @param   string  $fieldName       Name of the member variable from the node objects that
	 * should be used as the title for the options.
	 * @param   string  $prefix          String to indent deeper levels
	 * @param   string  $selected        Value to display as selected
	 * @param   bool    $addEmptyOption  Set TRUE to add an empty option with value "0" at the top of the hierarchy
	 * @param   integer $key             ID of the object to display as the root of select options
	 * @return  string  HTML select box
	 **/
	public function makeSelBox($name, $fieldName, $prefix='-', $selected='', $addEmptyOption = FALSE, $key=0)
    {
        $ret = array(0 => TFISH_SELECT_BOX_ZERO_OPTION);
		
        $this->_makeSelBoxOptions($fieldName, $selected, $key, $ret, $prefix);
        return $ret;
    }
	
	/**
	 * Make a select box of parent collections from the tree.
	 * 
	 * @param int $selected
	 * @param int $key
	 * @return string
	 */
	public function makeParentSelectBox($selected = 0, $key = 0)
	{
		$ret = array(0 => TFISH_SELECT_PARENT);
		
        $this->_makeSelBoxOptions('title', $selected, $key, $ret, '-');
        return $ret;
	}
}