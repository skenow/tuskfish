<?php

/**
* Tuskfish block object class
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
class TfishBlockList extends TfishBlock
{	
	/**
	 * Generic constructor
	 */
	function __construct($title, $limit)
	{
		parent::__construct();
		
		/**
		 * Set default values of permitted properties.
		 */
		$this->__set('title', $title); // String: Title of the block (blank for no title).
		$this->__set('limit', $limit); // Int: Number of objects to be displayed.
		// $this->__set('type', $type): Alpha: Class name for this block type.
		// $this->__set('online', 1); // Int: Toggle object on (1) or offline (0).
		// $this->__set('handler', $handler); // String: Handler for this object.
		$this->__set('template', 'blocklist.html'); // String: The template that should be used to display this block.

	}
	
	/**
	 * Generates HTML code to display the block.
	 * 
	 * @param object $criteria
	 * @return string
	 */
	public function render($criteria = false)
	{
		if ($criteria) {
			$clean_criteria = TfishDatabase::validateCriteriaObject($criteria);
		} else {
			$clean_criteria = new TfishCriteria();
		}

		return $this->_render($clean_criteria);		
	}
	
	private function _render($criteria)
	{
		$block = array('title' => '<h2>' . TfishFilter::escape($this->title) . '</h2>');
		
		$statement = '';
		$sql = "SELECT `id`, `title` FROM `content` ";
		
		// Set some defaults; these can be overriden by setting $criteria, except for $limit.
		// The manual limit parameter overrides any set through $criteria. Basically, this is
		// so you can set up blocks easily without having to set criteria all the time, ie.
		// just by passing in the title and limit.
		$criteria->limit = $this->limit;
		$criteria->order = !empty($criteria->order) ? $criteria->order : 'submission_time';
		$criteria->ordertype = !empty($criteria->ordertype) ? $criteria->ordertype : 'DESC';

		// Generate the WHERE clause with PDO / bound values.
		$pdo_placeholders = array();
		$sql .= $criteria->renderSQL();
		$pdo_placeholders = $criteria->renderPDO();
		
		// Set GROUP BY.
		if ($criteria->groupby) {
			$sql .= " GROUP BY " . TfishDatabase::addBackticks(TfishDatabase::escapeIdentifier($criteria->groupby));
		}

		// Set the order (sort) column and order (default is ascending).
		if ($criteria->order) {
			$sql .= " ORDER BY " . TfishDatabase::addBackticks(TfishDatabase::escapeIdentifier($criteria->order)) . " ";
			$sql .= $criteria->ordertype == "DESC" ? "DESC" : "ASC";
		}

		// Set the LIMIT and OFFSET.
		if ($criteria->offset && $criteria->limit) {
			$sql .= " LIMIT :limit OFFSET :offset";
		} elseif ($criteria->limit) {
			$sql .= " LIMIT :limit";
		}
		
		// Prepare the statement and bind the values.
		try {
			$statement = TfishDatabase::preparedStatement($sql);
			if ($criteria && $statement) {
				if (!empty($pdo_placeholders)) {
					foreach ($pdo_placeholders as $placeholder => $value) {
						$statement->bindValue($placeholder, $value, TfishDatabase::setType($value));
						unset($placeholder);
					}
				}
				if ($criteria->limit && $criteria->offset) {
					$statement->bindValue(':limit', $criteria->limit, PDO::PARAM_INT);
					$statement->bindValue(':offset', $criteria->offset, PDO::PARAM_INT);
				} elseif ($criteria->limit) {
					$statement->bindValue(':limit', $criteria->limit, PDO::PARAM_INT);
				}
			}
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}
		
		// Execute the statement.
		try {
			$statement->execute();
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}
		
		if ($statement) {
				$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
			} else {
				trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
			}
			foreach ($rows as &$row) {
				$block['content'][] = '<li><a href="' . TFISH_URL . '?id=' . $row['id'] . '">' . TfishFilter::escape($row['title']) . '</li></a>';
			}
		
			$output = $block['title'];
			$output .= '<ul>';
			foreach ($block['content'] as $item) {
				$output .= $item;
			}
			$output .= '</ul>';
			
		return $output;
	}
}