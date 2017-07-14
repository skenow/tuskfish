<?php

/**
 * TfishDatabase class file.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     database
 */

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Tuskfish database handler class.
 * 
 * Implements PDO and makes exclusive use of prepared statements with bound values to mitigate SQL
 * injection attacks. Table and column identifiers are also escaped.
 * 
 * It is expected that by the time data trickles down to this class it will have ALREADY BEEN
 * THOROUGHLY VALIDATED AND RANGE CHECKED by user-facing control scripts and internal object checks.
 * As the validation conducted by this class is the last line of defense any failures will trigger
 * FATAL errors and angry log entries.
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     database
 */
class TfishDatabase
{

    /** @var object $_db Instance of the PDO base class */
    private static $_db;

    /** Instantiation not permitted. */
    final private function __construct()
    {
    }

    /** No cloning permitted */
    final private function __clone()
    {
    }

    /**
     * Enclose table and column identifiers in backticks to escape them.
     * 
     * This method must only be used on TABLE and COLUMN names. Column values must be escaped 
     * through the use of bound parameters.
     * 
     * @param string $identifier Table or column name.
     * @return string Identifier encapsulated in backticks.
     */
    public static function addBackticks($identifier)
    {
        return '`' . $identifier . '`';
    }

    /**
     * Close the connection to the database.
     * 
     * @return bool True on success false on failure.
     */
    public static function close()
    {
        return self::_close();
    }

    /** @internal */
    private static function _close()
    {
        self::$_db = null;
        return true;
    }

    /**
     * Establish a connection to the database.
     * 
     * Connection is deliberately non-persistent (persistence can break things if scripts terminate
     * unexpectedly).
     * 
     * @return bool True on success, false on failure.
     */
    public static function connect()
    {
        return self::_connect();
    }

    /** @internal */
    private static function _connect()
    {
        // SQLite just expects a file name, which was defined as a constant during create()
        self::$_db = new PDO('sqlite:' . TFISH_DATABASE);
        
        if (self::$_db) {
            // Set PDO to throw exceptions every time it encounters an error.
            // On production sites it may be best to change the second argument to 
            // PDO::ERRMODE_SILENT OR PDO::ERRMODE_WARNING
            self::$_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create an SQLite database with random prefix and creates a language constant for it.
     * 
     * Database name must be alphanumeric and underscore characters only. The database will
     * automatically be appended with the suffix .db
     * 
     * @param string $db_name Database name.
     * @return string|bool Path to database file on success, false on failure.
     */
    public static function create($db_name)
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

    /** @internal */
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
     * Create a table in the database.
     * 
     * Table names may only be alphanumeric characters. Column names are also alphanumeric but may
     * also contain underscores.
     * 
     * @param string $table Table name (alphanumeric characters only). 
     * @param array $columns Array of column names (keys) and types (values).
     * @param string $primary_key Name of field to be used as primary key.
     * @return bool True on success, false on failure.
     */
    public static function createTable($table, $columns, $primary_key = null)
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
                $clean_primary_key = TfishFilter::isAlnumUnderscore($primary_key)
                        ? $primary_key : false;
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

    /** @internal */
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
            $statement = self::preparedStatement($sql);
            $statement->execute();
            
            if ($statement) {
                return true;
            } else {
                trigger_error(TFISH_ERROR_NO_STATEMENT, E_USER_ERROR);
            }
        }
    }

    /**
     * Delete single row from table based on its ID.
     * 
     * @param string $table Name of table.
     * @param int $id ID of row to be deleted.
     * @return bool True on success false on failure.
     */
    public static function delete($table, $id)
    {
        $clean_table = self::validateTableName($table);
        $clean_id = self::validateId($id);
        
        return self::_delete($clean_table, $clean_id);
    }

    /** @internal */
    private static function _delete($table, $id)
    {
        $sql = "DELETE FROM " . self::addBackticks($table) . " WHERE `id` = :id";
        $statement = self::preparedStatement($sql);
        
        if ($statement) {
            $statement->bindValue(':id', $id, PDO::PARAM_INT);
        } else {
            return false;
        }
        
        return self::executeTransaction($statement);
    }

    /**
     * Delete multiple rows from a table according to criteria.
     * 
     * For safety reasons criteria are required; the function will not unconditionally empty table.
     * Note that SQLite does not support DELETE with INNER JOIN or table alias. Therefore, you
     * cannot use tags as a criteria in deleteAll() (they will simply be ignored). It may be
     * possible to get around this restriction with a loop or subquery.
     * 
     * @param string $table Name of table.
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @return bool True on success, false on failure.
     */
    public static function deleteAll($table, $criteria)
    {
        $clean_table = self::validateTableName($table);
        $clean_criteria = self::validateCriteriaObject($criteria);
        
        return self::_deleteAll($clean_table, $clean_criteria);
    }

    /** @internal */
    private static function _deleteAll($table, $criteria)
    {
        // Set table.
        $sql = "DELETE FROM " . self::addBackticks($table) . " ";

        // Set WHERE criteria.
        if ($criteria) {

            if (!empty($criteria->item)) {
                $sql .= "WHERE ";
            }

            if (TfishFilter::isArray($criteria->item)) {
                $pdo_placeholders = array();
                $sql .= $criteria->renderSQL();
                $pdo_placeholders = $criteria->renderPDO();
            } else {
                trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
            }

            // Set the order (sort) column and order (default is ascending).
            if ($criteria->order) {
                $sql .= "ORDER BY `t1`." 
                        . self::addBackticks(self::escapeIdentifier($criteria->order)) . " ";
                $sql .= $criteria->ordertype == "DESC" ? "DESC" : "ASC";
                if ($criteria->order != 'submission_time') {
                    $sql .= ", `t1`.`submission_time` ";
                    $sql .= $criteria->ordertype == "DESC" ? "DESC" : "ASC";
                }
            }

            // Set the LIMIT and OFFSET.
            if ($criteria->offset && $criteria->limit) {
                $sql .= " LIMIT :limit OFFSET :offset";
            } elseif ($criteria->limit) {
                $sql .= " LIMIT :limit";
            }
        }

        // Prepare the statement and bind the values.
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
        
        return self::executeTransaction($statement);
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
     * get into a query as part of an identifier. But just because we are paranoid they are
     * escaped here anyway.
     * 
     * @param string $identifier Name of table or column.
     * @return string Escaped table or column name.
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
     * The $statement parameter should be a prepared statement obtained via preparedStatement($sql).
     * Note that statement execution is within a transaction and rollback will occur if it fails.
     * This method should be used with database write operations (INSERT, UPDATE, DELETE).
     * 
     * @param object $statement Prepared statement.
     * @return bool True on success, false on failure.
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
     * Insert a single row into the database within a transaction.
     * 
     * @param string $table Name of table.
     * @param array $key_values Column names and values to be inserted.
     * @return bool True on success, false on failure.
     */
    public static function insert($table, $key_values)
    {
        $clean_table = self::validateTableName($table);
        $clean_keys = self::validateKeys($key_values);
        
        return self::_insert($clean_table, $clean_keys);
    }

    /** @internal */
    private static function _insert($table, $key_values)
    {
        $pdo_placeholders = '';
        $sql = "INSERT INTO " . self::addBackticks($table) . " (";

        // Prepare statement
        foreach ($key_values as $key => $value) {
            $pdo_placeholders .= ":" . $key . ", ";
            $sql .= self::addBackticks($key) . ", ";
            unset($key, $value);
        }
        
        $pdo_placeholders = trim($pdo_placeholders, ', ');
        $sql = trim($sql, ', ');
        $sql .= ") VALUES (" . $pdo_placeholders . ")";

        // Prepare the statement and bind the values.
        $statement = self::$_db->prepare($sql);
        
        foreach ($key_values as $key => $value) {
            $statement->bindValue(":" . $key, $value, self::setType($value));
            unset($key, $value);
        }
        
        return self::executeTransaction($statement);
    }

    /**
     * Retrieves the ID of the last row inserted into the database.
     * 
     * Used primarily to grab the ID of newly created content objects so that their accompanying
     * taglinks can be correctly associated to them.
     * 
     * @return int|bool Row ID on success, false on failure.
     */
    public static function lastInsertId()
    {
        if (self::$_db->lastInsertId()) {
            return (int) self::$_db->lastInsertId();
        } else {
            return false;
        }
    }

    /**
     * Return a PDO statement object.
     * 
     * Statement object can be used to bind values or parameters and execute queries, thereby
     * mitigating direct SQL injection attacks.
     * 
     * @param string $sql SQL statement.
     * @return object PDOStatement object on success PDOException object on failure.
     */
    public static function preparedStatement($sql)
    {
        return self::_preparedStatement($sql);
    }

    /** @internal */
    private static function _preparedStatement($sql)
    {
        return self::$_db->prepare($sql);
    }

    /**
     * Prepare and execute a select query.
     * 
     * Returns a PDO statement object, from which results can be extracted with standard PDO calls.
     * 
     * @param string $table Name of table.
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @param array $columns Names of database columns to be selected.
     * @return object PDOStatement object on success PDOException on failure.
     */
    public static function select($table, $criteria = false, $columns = false)
    {
        $clean_table = self::validateTableName($table);
        $clean_criteria = !empty($criteria) ? self::validateCriteriaObject($criteria) : false;
        $clean_columns = !empty($columns) ? self::validateColumns($columns) : false;
        
        return self::_select($clean_table, $clean_criteria, $clean_columns);
    }

    /** @internal */
    private static function _select($table, $criteria, $columns)
    {
        // Specify operation.
        $sql = "SELECT ";

        // Select columns.
        if ($columns) {
            foreach ($columns as $column) {
                $sql .= '`t1`.' . self::addBackticks($column) . ", ";
            }
            
            $sql = rtrim($sql, ", ") . " ";
        } else {
            $sql .= "`t1`.* ";
        }

        // Set table.
        $sql .= "FROM " . self::addBackticks($table) . " AS `t1` ";

        // Check if a tag filter has been applied (JOIN is required).
        if ($criteria && !empty($criteria->tag)) {
            $sql .= self::_renderTagJoin($table);
        }

        // Set WHERE criteria.
        if ($criteria) {
            if (!empty($criteria->item) || !empty($criteria->tag)) {
                $sql .= "WHERE ";
            }

            if (TfishFilter::isArray($criteria->item)) {
                $pdo_placeholders = array();
                $sql .= $criteria->renderSQL();
                $pdo_placeholders = $criteria->renderPDO();
            } else {
                trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
            }

            if (!empty($criteria->item) && !empty($criteria->tag)) {
                $sql .= "AND ";
            }

            // Set tag(s).
            if (!empty($criteria->tag)) {
                $sql .= $criteria->renderTagSQL();
                $tag_placeholders = $criteria->renderTagPDO();
            }

            // Set GROUP BY.
            if ($criteria->groupby) {
                $sql .= " GROUP BY `t1`."
                        . self::addBackticks(self::escapeIdentifier($criteria->groupby));
            }

            // Set the order (sort) column and order (default is ascending).
            if ($criteria->order) {
                $sql .= " ORDER BY `t1`."
                        . self::addBackticks(self::escapeIdentifier($criteria->order)) . " ";
                $sql .= $criteria->ordertype == "DESC" ? "DESC" : "ASC";
                
                if ($criteria->order != 'submission_time') {
                    $sql .= ", `t1`.`submission_time` ";
                    $sql .= $criteria->ordertype == "DESC" ? "DESC" : "ASC";
                }
            }

            // Set the LIMIT and OFFSET.
            if ($criteria->offset && $criteria->limit) {
                $sql .= " LIMIT :limit OFFSET :offset";
            } elseif ($criteria->limit) {
                $sql .= " LIMIT :limit";
            }
        }

        // Prepare the statement and bind the values.
        $statement = self::preparedStatement($sql);
        
        if ($criteria && $statement) {
            if (!empty($pdo_placeholders)) {
                foreach ($pdo_placeholders as $placeholder => $value) {
                    $statement->bindValue($placeholder, $value, self::setType($value));
                    unset($placeholder);
                }
            }

            if (isset($tag_placeholders) && !empty($tag_placeholders)) {
                foreach ($tag_placeholders as $tag_placeholder => $value) {
                    $statement->bindValue($tag_placeholder, $value, PDO::PARAM_INT);
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

        // Execute the statement.
        $statement->execute();

        // Return the statement object, results can be extracted as required with standard PDO calls.
        return $statement;
    }

    /**
     * Count the number of rows matching a set of conditions.
     * 
     * @param string $table Name of table.
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @param string $column Name of column.
     * @return int|object Row count on success, PDOException object on failure.
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

    /** @internal */
    private static function _selectCount($table, $criteria, $column)
    {
        // Specify operation and column
        $sql = "SELECT COUNT(";
        $sql .= $column = "*" ? $column : self::addBackticks($column);
        $sql .= ") ";

        // Set table.
        $sql .= "FROM " . self::addBackticks($table) . " AS `t1` ";

        // Check if a tag filter has been applied (JOIN is required).
        if ($criteria && !empty($criteria->tag)) {
            $sql .= self::_renderTagJoin($table);
        }

        // Set WHERE criteria.
        if ($criteria) {

            if (!empty($criteria->item) || !empty($criteria->tag)) {
                $sql .= "WHERE ";
            }

            if (TfishFilter::isArray($criteria->item)) {
                $pdo_placeholders = array();
                $sql .= $criteria->renderSQL();
                $pdo_placeholders = $criteria->renderPDO();
            } else {
                trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
                exit;
            }

            if (!empty($criteria->item) && !empty($criteria->tag)) {
                $sql .= "AND ";
            }

            // Set tag(s).
            if (!empty($criteria->tag)) {
                $sql .= $criteria->renderTagSQL();
                $tag_placeholders = $criteria->renderTagPDO();
            }
        }

        // Prepare the statement and bind the values.
        $statement = self::preparedStatement($sql);
        if ($criteria && $statement) {
            if (!empty($pdo_placeholders)) {
                foreach ($pdo_placeholders as $placeholder => $value) {
                    $statement->bindValue($placeholder, $value, self::setType($value));
                    unset($placeholder);
                }
            }
        }
        
        if (isset($tag_placeholders) && !empty($tag_placeholders)) {
            foreach ($tag_placeholders as $tag_placeholder => $value) {
                $statement->bindValue($tag_placeholder, $value, PDO::PARAM_INT);
                unset($placeholder);
            }
        }

        // Execute the statement.
        $statement->execute();

        // Return the row count (integer) by retrieving the row.
        $count = $statement->fetch(PDO::FETCH_NUM);

        return (int) reset($count);
    }

    /**
     * Select results from the database but remove duplicate rows.
     * 
     * Use the $columns array to specify which fields you want to filter the results by.
     * 
     * @param string $table Name of table.
     * @param object $criteria TfishCriteria object used to build conditional database query.
     * @param array $columns Name of columns to filter results by.
     * @return object PDOStatement on success, PDOException on failure.
     */
    public static function selectDistinct($table, $criteria = false, $columns)
    {
        // Validate the tablename (alphanumeric characters only).
        $clean_table = self::validateTableName($table);
        $clean_criteria = !empty($criteria) ? self::validateCriteriaObject($criteria) : false;
        $clean_columns = !empty($columns) ? self::validateColumns($columns) : false;
        
        return self::_selectDistinct($clean_table, $clean_criteria, $clean_columns);
    }

    /** @internal */
    private static function _selectDistinct($table, $criteria, $columns)
    {
        // Specify operation
        $sql = "SELECT DISTINCT ";

        // Select columns.
        foreach ($columns as $column) {
            $sql .= '`t1`.' . self::addBackticks($column) . ", ";
        }
        
        $sql = rtrim($sql, ", ") . " ";

        // Set table.
        $sql .= "FROM " . self::addBackticks($table) . " AS `t1` ";

        // Set parameters.
        if ($criteria) {

            if (!empty($criteria->item) || !empty($criteria->tag)) {
                $sql .= "WHERE ";
            }

            if (TfishFilter::isArray($criteria->item)) {
                $pdo_placeholders = array();
                $sql .= $criteria->renderSQL();
                $pdo_placeholders = $criteria->renderPDO();
            } else {
                trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
                exit;
            }

            if (!empty($criteria->item) && !empty($criteria->tag)) {
                $sql .= "AND ";
            }

            // Set tag(s).
            if (!empty($criteria->tag)) {
                $sql .= $criteria->renderTagSQL();
                $tag_placeholders = $criteria->renderTagPDO();
            }

            // Set GROUP BY.
            if ($criteria->groupby) {
                $sql .= " GROUP BY `t1`."
                        . self::addBackticks(self::escapeIdentifier($criteria->groupby));
            }

            // Set the order (sort) column and type (default is ascending)
            if ($criteria->order) {
                $sql .= " ORDER BY `t1`."
                        . self::addBackticks(self::escapeIdentifier($criteria->order)) . " ";
                $sql .= $criteria->ordertype == "DESC" ? "DESC" : "ASC";
                
                if ($criteria->order != 'submission_time') {
                    $sql .= ", `t1`.`submission_time` ";
                    $sql .= $criteria->ordertype == "DESC" ? "DESC" : "ASC";
                }
            }

            // Set the LIMIT and OFFSET.
            if ($criteria->offset && $criteria->limit) {
                $sql .= " LIMIT :limit OFFSET :offset";
            } elseif ($criteria->limit) {
                $sql .= " LIMIT :limit";
            }
        }

        // Prepare the statement and bind the values.
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
        
        if (isset($tag_placeholders) && !empty($tag_placeholders)) {
            foreach ($tag_placeholders as $tag_placeholder => $value) {
                $statement->bindValue($tag_placeholder, $value, PDO::PARAM_INT);
                unset($placeholder);
            }
        }
        $statement->execute();

        return $statement;
    }

    /**
     * Toggle the online status of a column between 0 and 1, use for columns representing booleans.
     * 
     * Note that the $id MUST represent a column called ID for whatever table you want to run it on.
     * 
     * @param int $id ID of the row to update.
     * @param string $table Name of table.
     * @param string $column Name of column to update.
     * @return bool True on success, false on failure.
     */
    public static function toggleBoolean($id, $table, $column)
    {
        $clean_id = self::validateId($id);
        $clean_table = self::validateTableName($table);
        $clean_column = self::validateColumns(array($column));
        $clean_column = reset($clean_column);
        
        return self::_toggleBoolean($clean_id, $clean_table, $clean_column);
    }

    /** @internal */
    private static function _toggleBoolean($id, $table, $column)
    {
        $sql = "UPDATE " . self::addBackticks($table) . " SET " . self::addBackticks($column)
                . " = CASE WHEN " . self::addBackticks($column)
                . " = 1 THEN 0 ELSE 1 END WHERE `id` = :id";

        // Prepare the statement and bind the ID value.
        $statement = TfishDatabase::preparedStatement($sql);
        
        if ($statement) {
            $statement->bindValue(":id", $id, PDO::PARAM_INT);
        }

        return self::executeTransaction($statement);
    }

    /**
     * Increment a content object counter field by one.
     * 
     * Call this method when the full description of an individual content object is viewed, or
     * when a related media file is downloaded.
     * 
     * @param int $id ID of content object.
     * @param string $table Name of table.
     * @param string $column Name of column.
     * @return bool True on success false on failure.
     */
    public static function updateCounter($id, $table, $column)
    {
        $clean_id = self::validateId($id);
        $clean_table = self::validateTableName($table);
        $clean_column = self::validateColumns(array($column));
        $clean_column = reset($clean_column);
        
        return self::_updateCounter($clean_id, $clean_table, $clean_column);
    }

    /** @internal */
    private static function _updateCounter($id, $table, $column)
    {
        $sql = "UPDATE " . self::addBackticks($table) . " SET " . self::addBackticks($column)
                . " = " . self::addBackticks($column) . " + 1 WHERE `id` = :id";

        // Prepare the statement and bind the ID value.
        $statement = TfishDatabase::preparedStatement($sql);
        
        if ($statement) {
            $statement->bindValue(":id", $id, PDO::PARAM_INT);
        }

        return self::executeTransaction($statement);
    }

    /**
     * Update a single row in the database.
     * 
     * @param string $table Name of table.
     * @param int $id ID of row to update.
     * @param array $key_values Array of column names and values to update.
     * @return bool True on success, false on failure.
     */
    public static function update($table, $id, $key_values)
    {
        $clean_table = self::validateTableName($table);
        $clean_id = self::validateId($id);
        $clean_keys = self::validateKeys($key_values);
        
        return self::_update($clean_table, $clean_id, $clean_keys);
    }

    /** @internal */
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
        $statement = self::preparedStatement($sql);
        
        if ($statement) {
            $statement->bindValue(":id", $id, PDO::PARAM_INT);
            
            foreach ($key_values as $key => $value) {
                $type = gettype($value);
                $statement->bindValue(":" . $key, $value, self::setType($type));
                unset($type);
            }
        } else {
            return false;
        }
        
        return self::executeTransaction($statement);
    }

    /**
     * Update multiple rows in a table according to criteria.
     * 
     * Note that SQLite does not support INNER JOIN or table aliases in UPDATE; therefore it is
     * not possible to use tags as a criteria in updateAll() at present. It may be possible to get
     * around this limitation with a subquery. But given that the use case would be unusual /
     * marginal it is probably just easier to work around it.
     * 
     * @param string $table Name of table.
     * @param array $key_values Array of column names and values to update.
     * @param object $criteria TfishCriteria object used to build conditional database query.
     */
    public static function updateAll($table, $key_values, $criteria = false)
    {
        $clean_table = self::validateTableName($table);
        $clean_keys = self::validateKeys($key_values);
        
        if ($criteria) {
            $clean_criteria = self::validateCriteriaObject($criteria);
        } else {
            $clean_criteria = false;
        }
        
        return self::_updateAll($clean_table, $clean_keys, $clean_criteria);
    }

    /** @internal */
    private static function _updateAll($table, $key_values, $criteria)
    {
        // Set table.
        $sql = "UPDATE " . self::addBackticks($table) . " SET ";

        // Set key values.
        foreach ($key_values as $key => $value) {
            $sql .= self::addBackticks($key) . " = :" . $key . ", ";
        }
        
        $sql = rtrim($sql, ", ") . " ";

        // Set WHERE criteria.
        if ($criteria) {

            if (!empty($criteria->item)) {
                $sql .= "WHERE ";
            }

            if (TfishFilter::isArray($criteria->item)) {
                $pdo_placeholders = array();
                $sql .= $criteria->renderSQL();
                $pdo_placeholders = $criteria->renderPDO();
            } else {
                trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
                exit;
            }
        }

        // Prepare the statement and bind the values.
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

        return self::executeTransaction($statement);
    }

    /**
     * Helper method to set appropriate PDO predefined constants in bindValue() and bindParam().
     * 
     * Do not use this method for arrays, objects or resources. Note that if you pass in an
     * unexpected data type (ie. one that clashes with a column type definition) PDO will throw
     * an error.
     * 
     * @param mixed $data Input data to be type set.
     * @return int PDO data type constant.
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
     * Renders a JOIN component of an SQL query for tagged content.
     * 
     * If the $criteria for a query include tag(s), the object table must have a JOIN to the
     * taglinks table in order to sort the content.
     * 
     * @internal
     * @param string $table Name of table.
     * @return string $sql SQL query fragment.
     */
    private static function _renderTagJoin($table)
    {
        $sql = "INNER JOIN `taglink` ON `t1`.`id` = `taglink`.`content_id` ";

        return $sql;
    }

    /**
     * Validates the properties of a TfishCriteria object to be used in constructing a query.
     * 
     * @param object $criteria TfishCriteria object.
     * @return object Validated TfishCriteria object.
     */
    public static function validateCriteriaObject($criteria)
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

    /**
     * Validate and escape column names to be used in constructing a database query.
     * 
     * @param array $columns Array of unescaped column names.
     * @return array Array of valid, escaped column names
     */
    public static function validateColumns($columns)
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

    /**
     * Validates and sanitises an ID to be used in constructing a database query.
     * 
     * @param int $id Input ID to be tested.
     * @return int $id Validated ID.
     */
    public static function validateId($id)
    {
        $clean_id = (int) $id;
        if (TfishFilter::isInt($clean_id, 1)) {
            return $clean_id;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            exit;
        }
    }

    /**
     * Validate and escapes keys to be used in constructing a database query.
     * 
     * Keys may only consist of alphanumeric and underscore characters. SQLite identifier delimiters
     * are escaped.
     * 
     * @param array $key_values Array of unescaped keys.
     * @return array Array of valid and escaped keys.
     */
    public static function validateKeys($key_values)
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

    /**
     * Validate and escape a table name to be used in constructing a database query.
     * 
     * @param string $table_name Table name to be checked.
     * @return string Valid and escaped table name.
     */
    public static function validateTableName($table_name)
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
