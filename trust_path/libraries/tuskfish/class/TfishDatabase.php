<?php

/**
* Tuskfish database handler class
* 
* Implements PDO and makes exclusive use of prepared statements with bound values to mitigate SQL
* injection attacks. Note that if other parts of queries are build up using unescaped input then
* SQL injection is still possible. So DON'T DO THAT, RIGHT?
* 
* It is expected that by the time data trickles down to this class it will have ALREADY BEEN
* THOROUGHLY VALIDATED by both i) user-facing control scripts and ii) internal object checks. As the
* validation conducted by this class is the last line of defense any failures will trigger
* FATAL ERRORS.   
*
* @copyright	Simon Wilkinson (Crushdepth) 2013
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishDatabase
{
	private static $_db;
	
	/**
	 * No cloning or instantiation permitted
	 */
	final private function __construct() // Finished
	{}
	final private function __clone() // Finished
	{}
	
	/**
	 * Enclose table and column identifiers in backticks to escape them.
	 * 
	 * This method must only be used on TABLE and COLUMN names. Column values must be escaped 
	 * through the use of bound parameters.
	 * 
	 * @param string $identifier
	 * @return string
	 */
	public static function addBackticks($identifier) // Finished
	{
		return '`' . $identifier . '`';
	}
	
	public static function close() // Finished
	{
		return self::_close();
	}
	
	public static function connect() // Finished
	{
		return self::_connect();
	}
	
	/**
	 * Create an SQLite databse.
	 * 
	 * Database name must be alphanumeric and underscore characters only. The database will
	 * automatically be appended with the suffix .db
	 * 
	 * @param string $db_name
	 * @return mixed
	 */
	public static function create($db_name) // Finished
	{
		// Validate input parameters
		$db_name = TfishFilter::trimString($db_name);
		if (TfishFilter::isAlnumUnderscore($db_name)) {
			return self::_create($db_name . '.db');
		} else {
			trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
			exit;
		}	
	}
	
	/**
	 * Create a table in the database.
	 * 
	 * Table names may only be alphanumeric characters. Column names may only be alphanumeric or
	 * underscores.
	 * 
	 * @param string (alphanumeric) $table_name
	 * @param array $columns
	 * @param string $primary_key
	 * @return type
	 */
	public static function createTable($table, $columns, $primary_key = null) // Finished
	{
		// Initialise
		$clean_primary_key = null;
		$clean_columns = array();
		
		// Validate input parameters
		$clean_table = self::validateTableName($table);
		if (TfishFilter::isArray($columns) && !empty($columns)) {
			$type_whitelist = array("BLOB", "TEXT", "INTEGER", "NULL", "REAL");
			foreach ($columns as $key => $value) {
				$key = self::escapeIdentifier($key);
				if (!TfishFilter::isAlnumUnderscore($key)) {
					trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
					exit;
				}
				if (!in_array($value, $type_whitelist)) {
					trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
					exit;
				}
				$clean_columns[$key] = $value;
				unset($key, $value);
			}
		} else {
			trigger_error(TFISH_ERROR_NOT_ARRAY_OR_EMPTY, E_USER_ERROR);
			exit;
		}		
		if ($primary_key) {
			$primary_key = self::escapeIdentifier($primary_key);
			if (array_key_exists($primary_key, $clean_columns)) {
				$clean_primary_key = TfishFilter::isAlnumUnderscore($primary_key) ? $primary_key : false;
			}
			if (!$clean_primary_key) {
				trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
				exit;
			}
		}
		
		// Proceed with the query
		if ($clean_primary_key) {
			return self::_createTable($clean_table, $clean_columns, $clean_primary_key);
		} else {
			return self::_createTable($clean_table, $clean_columns);
		}
	}
	
	/**
	 * Delete single row from table based on its ID.
	 * 
	 * @param string $table
	 * @param int $id
	 * @return bool
	 */
	public static function delete($table, $id) // Finished
	{
		$clean_table = self::validateTableName($table);
		$clean_id = self::validateId($id);
		return self::_delete($clean_table, $clean_id);
	}
	
	/**
	 * Delete multiple rows from a table according to criteria.
	 * 
	 * For safety reasons criteria are required; the function will not unconditionally empty a table.
	 * 
	 * @param string $table
	 * @param obj $criteria
	 */
	public static function deleteAll($table, $criteria)
	{
		$clean_table = self::validateTableName($table);
		$clean_criteria = self::validateCriteriaObject($criteria);
		return self::_deleteAll($clean_table, $clean_criteria);
	}
	
		/**
	 * Escape delimiters for identifiers (table and column names).
	 * 
	 * SQLite supports three styles of identifier delimitation:
	 * 
	 * 1. Standard SQL double quotes: "
	 * 2. MySQL style grave accents: `
	 * 3. MS SQL style square brackets: []
	 * 
	 * Escaping of delimiters where they are used as part of a table or column name is done by
	 * doubling them, eg ` becomes ``. In order to safely escape table and column names ALL
	 * three delimiter types must be escaped.
	 * 
	 * Tuskfish policy is that table names can only contain alphanumeric characters (and column
	 * names can only contain alphanumeric plus underscore characters) so delimiters should never
	 * get into a query as part of an identifier anyway. But just because we are paranoid they are
	 * escaped here anyway.
	 * 
	 * @param string $identfier
	 * @return string
	 */
	public static function escapeIdentifier($identifier)
	{
		$clean_identifier = '';
		$identifier = TfishFilter::trimString($identifier);
		$identifier = str_replace('"', '""', $identifier);
		$identifier = str_replace('`', '``', $identifier);
		$identifier = str_replace('[', '[[', $identifier);
		$clean_identifier = str_replace(']', ']]', $identifier);
		return $clean_identifier;
	}
	
	/**
	 * Execute a prepared statement within a transaction.
	 * 
	 * @param obj $statement
	 * @return boolean
	 */
	public static function executeTransaction($statement)
	{
		try {
			self::$_db->beginTransaction();
			$statement->execute();
			self::$_db->commit();
		} catch (PDOException $e) {
			self::$_db->rollBack();
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
			return false;
		}
		return true;
	}
	
	/**
	 * Insert single row in table.
	 * 
	 * @param string $table
	 * @param mixed $key_values
	 * @return bool
	 */
	public static function insert($table, $key_values) // Finished
	{
		$clean_table = self::validateTableName($table);
		$clean_keys = self::validateKeys($key_values);
		return self::_insert($clean_table, $clean_keys);
	}
	
	public static function preparedStatement($sql) // Finished
	{
		return self::_preparedStatement($sql);
	}
	
	public static function select($table, $criteria = false, $columns = false)
	{
		$clean_table = self::validateTableName($table);
		$clean_criteria = !empty($criteria) ? self::validateCriteriaObject($criteria) : false;
		$clean_columns = !empty($columns) ? self::validateColumns($columns) : false;
		return self::_select($clean_table, $clean_criteria, $clean_columns);
	}
	
	/**
	 * Count the number of rows matching a set of conditions.
	 * 
	 * @param string $table
	 * @param obj $criteria
	 * @param string $column
	 * @return int
	 */
	public static function selectCount($table, $criteria = false, $column = false)
	{
		$clean_table = self::validateTableName($table);
		$clean_criteria = !empty($criteria) ? self::validateCriteriaObject($criteria) : false;
		if ($column) {
			$column = self::escapeIdentifier($column);
			if (TfishFilter::isAlnumUnderscore($column)) {
				$clean_column = $column;
			} else {
				trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
				exit;
			}
		} else {
			$clean_column = "*";
		}	
		return self::_selectCount($clean_table, $clean_criteria, $clean_column);
	}
	
	/**
	 * Select results from the database but remove duplicates.
	 * 
	 * Use the $columns array to specify which fields you want to filter the results by.
	 * 
	 * @param string $table
	 * @param obj $criteria
	 * @param array $columns
	 * @return mixed
	 */
	public static function selectDistinct($table, $criteria = false, $columns)
	{
		// Validate the tablename (alphanumeric characters only).
		$clean_table = self::validateTableName($table);
		$clean_criteria = !empty($criteria) ? self::validateCriteriaObject($criteria) : false;
		$clean_columns = !empty($columns) ? self::validateColumns($columns) : false;
		return self::_selectDistinct($clean_table, $clean_criteria, $clean_columns);
	}
	
	/**
	 * Update a single row
	 * 
	 * @param string $table
	 * @param array $key_values
	 * @return bool
	 */
	public static function update($table, $id, $key_values) // Finished
	{
		$clean_table = self::validateTableName($table);	
		$clean_id = self::validateId($id);
		$clean_keys = self::validateKeys($key_values);
		return self::_update($clean_table, $clean_id, $clean_keys);
	}
	
	/**
	 * Update multiple rows in a table according to criteria.
	 * 
	 * @param string $table
	 * @param array $key_values
	 * @param obj $criteria
	 */
	public static function updateAll($table, $criteria = false, $key_values)
	{
		$clean_table = self::validateTableName($table);
		$clean_keys = self::validateKeys($key_values);
		if ($criteria) {
			$clean_criteria = self::validateCriteriaObject($criteria);
		} else {
			$clean_criteria = false;
		}
		return self::_updateAll($clean_table, $clean_criteria, $clean_keys);
	}
	
	/**
	 * Helper method to set appropriate PDO predefined constants in bindValue() and bindParam().
	 * 
	 * Do not use this method for arrays, objects or resources. Note that if you pass in an
	 * unexpected data type (ie. one that clashes with a column type definition) PDO will throw
	 * an error.
	 * 
	 * @param type $data
	 * @return type
	 */
	public static function setType($data)
	{
		$type = gettype($data);
		switch ($type) {
			case "boolean":
				return PDO::PARAM_BOOL;
			break;
		
			case "integer":
				return PDO::PARAM_INT;
			break;
		
			case "NULL":
				return PDO::PARAM_NULL;
			break;
		
			case "string":
			case "double":
				return PDO::PARAM_STR;
			break;

			default: // array, object, resource, "unknown type"
				trigger_error(TFISH_ERROR_ILLEGAL_TYPE, E_USER_ERROR);
				exit;
		}
	}
	
	/**
	 * Close the connection to the database
	 * 
	 * @return bool
	 */
	private static function _close() 
	{
		try {
			self::$_db = null;
			return true;
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
			return false;
		}
	}
	
	/**
	 * Open a connection to the database.
	 * 
	 * Connection is deliberately non-persistent (persistance can break things if scripts terminate unexpectedly).
	 * 
	 * @return bool
	 */
	private static function _connect()
	{
		try {
			// SQLite just expects a file name, which was defined as a constant during create()
			self::$_db = new PDO('sqlite:' . TFISH_DATABASE);
			if (self::$_db) {
				// Set PDO to throw exceptions every time it encounters an error.
				// On production sites it may be best to change the second argument to 
				// PDO::ERRMODE_SILENT OR PDO::ERRMODE_WARNING
				self::$_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				return true;
			}
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
			return false;
		}
	}
	
	/**
	 * Creates an SQLite database with random prefix and creates a language constant for it.
	 * 
	 * @param string $db_name
	 * @return bool
	 */
	private static function _create($db_name)
	{
		// Generate a random prefix for the database filename to make it unpredictable.
		$prefix = mt_rand();
		
		// Create database file and append a constant with the database path to config.php
		try {
			$db_path = TFISH_DATABASE_PATH . $prefix . '_' . $db_name;
			self::$_db = new PDO('sqlite:' . $db_path);
			$db_constant = PHP_EOL . 'if (!defined("TFISH_DATABASE")) define("TFISH_DATABASE", "' 
				. $db_path . '");';
			$result = TfishFileHandler::appendFile(TFISH_CONFIGURATION_PATH, $db_constant);
			if (!$result) {
				trigger_error(TFISH_ERROR_FAILED_TO_APPEND_FILE, E_USER_NOTICE);
				return false;
			}
			return $db_path;
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
			return false;
		}
	}
	
	/**
	 * Create a SQLite table in the database.
	 * 
	 * Note that table names may only be alphanumeric; column names are also alphanumeric but may
	 * also contain underscores.
	 * 
	 * @param string $table_name - name of the table
	 * @param array $columns - column names (keys) and types (values)
	 * @param string $primary_key - field to be used as the primary key
	 * @return bool
	 */
	private static function _createTable($table_name, $columns, $primary_key = null)
	{
		if (mb_strlen($table_name, 'UTF-8') > 0 && is_array($columns)) {
			$sql = "CREATE TABLE IF NOT EXISTS `" . $table_name . "` (";
			foreach ($columns as $key => $value) {
				$sql .= "`" . $key . "` " . $value . "";
				if ($primary_key && $primary_key == $key) {
					$sql .= " PRIMARY KEY";
				}
				$sql .= ", ";
			}
			$sql = trim($sql, ', ');
			$sql .= ")";
			try {
				$statement = self::preparedStatement($sql);
				$statement->execute();
				if ($statement) {
					return true;
				} else {
					trigger_error(TFISH_ERROR_NO_STATEMENT, E_USER_ERROR);
				}
			} catch (PDOException $e) {
				TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
				return false;
			}
		}
	}
	
	private static function _delete($table, $id)
	{
		$sql = "DELETE FROM " . self::addBackticks($table) . " WHERE `id` = :id";
		try {
			$statement = self::preparedStatement($sql);
			if ($statement) {
				$statement->bindValue(':id', $id, PDO::PARAM_INT);
			} else {
				return false;
			}
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}
		return self::executeTransaction($statement);
	}
	
	private static function _deleteAll($table, $criteria)
	{
		$sql = "DELETE FROM " . self::addBackticks($table) . " WHERE ";
		$pdo_placeholders = array();
		
		if (!empty($criteria->item) && TfishFilter::isArray($criteria->item)) {
			$sql .= $criteria->renderSQL();
			$pdo_placeholders = $criteria->renderPDO();
		}

		// Set the order (sort) column.
		if ($criteria->order) {
			$sql .= " ORDER BY " . self::addBackticks(self::escapeIdentifier(self::$criteria->order));

			// Set the sort order (default is ascending).
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
			$statement = self::preparedStatement($sql);
			if ($criteria) {
				if (!empty($pdo_placeholders)) {
					foreach ($pdo_placeholders as $placeholder => $value) {
						$statement->bindValue($placeholder, $value, self::setType($value));
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
		return self::executeTransaction($statement);
	}
	
	/**
	 * Insert a single row into the database within a transaction.
	 * 
	 * @param string $table
	 * @param array $key_values
	 * @return bool
	 */
	private static function _insert($table, $key_values)
	{
		$pdo_placeholders = '';
		$sql = "INSERT INTO " . self::addBackticks($table) . " (";

		// Prepare statement
		foreach ($key_values as $key => $value) {
			$pdo_placeholders  .= ":" . $key . ", ";
			$sql .= self::addBackticks($key) . ", ";
			unset($key, $value);
		}
		$pdo_placeholders = trim($pdo_placeholders, ', ');
		$sql = trim($sql, ', ');
		$sql .= ") VALUES (" . $pdo_placeholders . ")";
		// Prepare the statement and bind the values.
		try {
			$statement = self::$_db->prepare($sql);
			foreach ($key_values as $key => $value) {
				$statement->bindValue(":" . $key, $value, self::setType($value));
				unset($key, $value);
			}
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
			return false;
		}
		return self::executeTransaction($statement);
	}

	/**
	 * Return a statement object that can be used to bind parameters and execute queries, thereby
	 * mitigating direct SQL injection attacks.
	 * 
	 * @param string $sql
	 * @return object
	 */
	private static function _preparedStatement($sql)
	{
		return self::$_db->prepare($sql);
	}
	
	/**
	 * Prepare and execute a select query.
	 * 
	 * Returns a PDO statement object, from which results can be extracted with standard PDO calls.
	 * 
	 * @param string $table
	 * @param obj $criteria
	 * 
	 * @return object PDO statement
	 */
	private static function _select($table, $criteria, $columns)
	{		
		// Specify operation
		$sql = "SELECT ";
			
		// Select columns.
		if ($columns) {
			foreach ($columns as $column) {
				$sql .= self::addBackticks($column) . ", ";
			}
			$sql = rtrim($sql, ", ");
		} else {
			$sql .= "*";
		}
		
		// Set table.
		$sql .= " FROM " . self::addBackticks($table);
		
		// Set WHERE criteria.
		if ($criteria) {
			if (!empty($criteria->item) && TfishFilter::isArray($criteria->item)) {
				$pdo_placeholders = array();
				$sql .= $criteria->renderSQL();
				$pdo_placeholders = $criteria->renderPDO();
			}
			
			// Set GROUP BY.
			if ($criteria->groupby) {
				$sql .= " GROUP BY " . self::addBackticks(self::escapeIdentifier($criteria->groupby));
			}
			
			// Set the order (sort) column and order (default is ascending).
			if ($criteria->order) {
				$sql .= " ORDER BY " . self::addBackticks(self::escapeIdentifier($criteria->order)) . " ";
				$sql .= $criteria->ordertype == "DESC" ? "DESC" : "ASC";
			}

			// Set the LIMIT and OFFSET.
			if ($criteria->offset && $criteria->limit) {
				$sql .= " LIMIT :limit OFFSET :offset";
			} elseif ($criteria->limit) {
				$sql .= " LIMIT :limit";
			}
		}

		// Prepare the statement and bind the values.
		try {
			$statement = self::preparedStatement($sql);
			if ($criteria && $statement) {
				if (!empty($pdo_placeholders)) {
					foreach ($pdo_placeholders as $placeholder => $value) {
						$statement->bindValue($placeholder, $value, self::setType($value));
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
		
		// Return the statement object, results can be extracted as required with standard PDO calls.
		return $statement;
	}
	
	private static function _selectCount($table, $criteria, $column)
	{
		// Specify operation and column
		$sql = "SELECT COUNT(";
		$sql .= $column = "*" ? $column : self::addBackticks($column);
		$sql .= ")";
		
		// Set table.
		$sql .= " FROM " . self::addBackticks($table);
		
		// Set WHERE conditions.
		if ($criteria) {
			if (!empty($criteria->item) && TfishFilter::isArray($criteria->item)) {
				if (!empty($criteria->item) && TfishFilter::isArray($criteria->item)) {
					$pdo_placeholders = array();
					$sql .= $criteria->renderSQL();
					$pdo_placeholders = $criteria->renderPDO();
				}
			} else {
				trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
				exit;
			}
			
			// Set GROUP BY.
			if ($criteria->groupby) {
				$sql .= " GROUP BY " . self::addBackticks(self::escapeIdentifier($criteria->groupby));
			}
		}

		// Prepare the statement and bind the values.
		try {
			$statement = self::preparedStatement($sql);
			if ($criteria && $statement) {
				if (!empty($pdo_placeholders)) {
					foreach ($pdo_placeholders as $placeholder => $value) {
						$statement->bindValue($placeholder, $value, self::setType($value));
						unset($placeholder);
					}
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

		// Return the row count (integer) by retrieving the row.
		try {
			$count = $statement->fetch(PDO::FETCH_NUM);
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}
		return reset($count);
	}
	
	/**
	 * Prepare and execute a select query returning distinct rows.
	 * 
	 * @param string $table
	 * @param obj $criteria
	 * @param array $columns 
	 */
	private static function _selectDistinct($table, $criteria, $columns)
	{		
		// Specify operation
		$sql = "SELECT DISTINCT ";
		
		// Select columns.
		foreach ($columns as $column) {
			$sql .= self::addBackticks($column) . ", ";
		}
		$sql = rtrim($sql, ", ");
		
		// Set table.
		$sql .= " FROM " . self::addBackticks($table);
		
		// Set parameters.
		if ($criteria) {
			if (!empty($criteria->item) && TfishFilter::isArray($criteria->item)) {
				if (!empty($criteria->item) && TfishFilter::isArray($criteria->item)) {
					$pdo_placeholders = array();
					$sql .= $criteria->renderSQL();
					$pdo_placeholders = $criteria->renderPDO();
				}
			} else {
				trigger_error(TFISH_ERROR_NOT_ARRAY_OR_EMPTY, E_USER_ERROR);
				exit;
			}
			
			// Set GROUP BY.
			if ($criteria->groupby) {
				$sql .= " GROUP BY " . self::addBackticks(self::escapeIdentifier($criteria->groupby));
			}
			
			// Set the order (sort) column and type (default is ascending)
			if ($criteria->order) {
				$sql .= " ORDER BY " . self::addBackticks(self::escapeIdentifier($criteria->order));
				$sql .= $criteria->ordertype == "DESC" ? "DESC" : "ASC";
			}

			// Set the LIMIT and OFFSET.
			if ($criteria->offset && $criteria->limit) {
				$sql .= " LIMIT :limit OFFSET :offset";
			} elseif ($criteria->limit) {
				$sql .= " LIMIT :limit";
			}
		}

		// Prepare the statement and bind the values.
		try {
			$statement = self::preparedStatement($sql);
			if ($criteria && $statement) {
				if (!empty($pdo_placeholders)) {
					foreach ($pdo_placeholders as $placeholder => $value) {
						$statement->bindValue($placeholder, $value, self::setType($value));
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
			$statement->execute();
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}
		return $statement;
	}
	
	/**
	 * Updates row(s) in the database within a transaction
	 * 
	 * @param type $table
	 * @param type $key_values
	 */
	private static function _update($table, $id, $key_values)
	{		
		// Prepare the statement
		$sql = "UPDATE " . self::addBackticks($table) . " SET ";
		foreach ($key_values as $key => $value) {
			$sql .= self::addBackticks($key) . " = :" . $key . ", ";
		}
		$sql = trim($sql, ", ");
		$sql .= " WHERE `id` = :id";
		
		// Prepare the statement and bind the values.
		try {
			$statement = self::preparedStatement($sql);
			if ($statement) {
				$statement->bindValue(":id", $id, PDO::PARAM_INT);
				foreach($key_values as $key => $value) {
					$type = gettype($value);
					$statement->bindValue(":" . $key, $value, self::setType($type));
					unset($type);
				}
			} else {
				return false;
			}
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}
		return self::executeTransaction($statement);	
	}
	
	private static function _updateAll($table, $criteria, $key_values)
	{
		// Prepare the query.
		$sql = "UPDATE " . self::addBackticks($table) . " SET ";
		foreach ($key_values as $key => $value) {
			$sql .= self::addBackticks($key) . " = :" . $key . ", ";
		}
		$sql = rtrim($sql, ", ");
		
		// Set WHERE conditions
		if ($criteria) {
			if (!empty($criteria->item) && TfishFilter::isArray($criteria->item)) {
				if (!empty($criteria->item) && TfishFilter::isArray($criteria->item)) {
					$pdo_placeholders = array();
					$sql .= $criteria->renderSQL();
					$pdo_placeholders = $criteria->renderPDO();
				}
			} else {
				trigger_error(TFISH_ERROR_NOT_ARRAY_OR_EMPTY, E_USER_ERROR);
				exit;
			}
		}
		
		// Prepare the statement and bind the values.
		try {
			$statement = self::preparedStatement($sql);
			foreach ($key_values as $key => $value) {
				$statement->bindValue(':' . $key, $value, self::setType($value));
				unset($key, $value);
			}
			if ($criteria) {
				if (!empty($pdo_placeholders)) {
					foreach ($pdo_placeholders as $placeholder => $value) {
						$statement->bindValue($placeholder, $value, self::setType($value));
						unset($placeholder);
					}
				}
			}
		} catch (PDOException $e) {
			TfishLogger::logErrors($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}
		return self::executeTransaction($statement);
	}
	
	private static function validateCriteriaObject($criteria)
	{
		if (!is_a($criteria, 'TfishCriteria')) {
			trigger_error(TFISH_ERROR_NOT_CRITERIA_OBJECT, E_USER_ERROR);
			exit;
		}
		if ($criteria->item) {
			if (!TfishFilter::isArray($criteria->item)) {
				trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
				exit;
			}
			if (empty($criteria->condition)) {
				trigger_error(TFISH_ERROR_REQUIRED_PROPERTY_NOT_SET, E_USER_ERROR);
				exit;
			}
			if (!TfishFilter::isArray($criteria->condition)) {
				trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
				exit;
			}
			if (count($criteria->item) != count($criteria->condition)) {
				trigger_error(TFISH_ERROR_COUNT_MISMATCH, E_USER_ERROR);
				exit;
			}
			foreach ($criteria->item as $item) {
				if (!is_a($item, 'TfishCriteriaItem')) {
					trigger_error(TFISH_ERROR_NOT_CRITERIA_ITEM_OBJECT, E_USER_ERROR);
					exit;
				}
				if (!TfishFilter::isAlnumUnderscore($item->column)) {
					trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
					exit;
				}
				if ($item->operator && !in_array($item->operator, $item->permittedOperators())) {
					trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
					exit;
				}
			}
			foreach ($criteria->condition as $condition) {
				if ($condition != "AND" && $condition != "OR") {
					trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
					exit;
				}
			}
		}
		if ($criteria->groupby && !TfishFilter::isAlnumUnderscore($criteria->groupby)) {
			trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
			exit;
		}
		if ($criteria->limit && !TfishFilter::isInt($criteria->limit, 1)) {
			trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
			exit;
		}
		if ($criteria->offset && !TfishFilter::isInt($criteria->offset, 0)) {
			trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
			exit;
		}
		if ($criteria->order && !TfishFilter::isAlnumUnderscore($criteria->order)) {
			trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
			exit;
		}
		if ($criteria->ordertype && 
				($criteria->ordertype != "ASC" && $criteria->ordertype != "DESC")) {
			trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
			exit;
		}
		return $criteria;
	}
	
	private static function validateColumns($columns)
	{
		$clean_columns = array();
		if (TfishFilter::isArray($columns) && !empty($columns)) {
			foreach ($columns as $column) {
				$column = self::escapeIdentifier($column);
				if (TfishFilter::isAlnumUnderscore($column)) {
					$clean_columns[] = $column;
				} else {
					trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
					exit;
				}
				unset($column);
			}
			return $clean_columns;
		} else {
			trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
			exit;
		}
	}
	
	private static function validateId($id)
	{
		$clean_id = TfishFilter::isInt($id) ? (int)$id : null;
		if (isset($clean_id) && $clean_id > 0) {
			return $clean_id;
		} else {
			trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
			exit;
		}
	}
	
	/**
	 * Validate that keys are alphanumeric and underscore characters only.
	 * 
	 * @param array $key_values
	 * @return array
	 */
	private static function validateKeys($key_values)
	{
		$clean_keys = array();
		if (TfishFilter::isArray($key_values) && !empty($key_values)) {
			foreach ($key_values as $key => $value) {
				$key = self::escapeIdentifier($key);
				if (TfishFilter::isAlnumUnderscore($key)) {
					$clean_keys[$key] = $value;
				} else {
					trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
					exit;
				}
				unset($key, $value);
			}
			return $clean_keys;
		} else {
			trigger_error(TFISH_ERROR_NOT_ARRAY_OR_EMPTY, E_USER_ERROR);
			exit;
		}
	}
	
	private static function validateTableName($table_name)
	{
		$table_name = self::escapeIdentifier($table_name);
		if (TfishFilter::isAlnum($table_name)) {
			return $table_name;
		} else {
			trigger_error(TFISH_ERROR_NOT_ALNUM, E_USER_ERROR);
			exit;
		}		
	}
}