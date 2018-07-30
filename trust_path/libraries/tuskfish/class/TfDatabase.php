<?php

/**
 * TfDatabase class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     database
 */

// Enable strict type declaration.
declare(strict_types=1);

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
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     database
 * @var         PDO $_db Instance of the PDO base class.
 * @var         TfValidator $validator Instance of the Tuskfish data validator.
 * @var         TfFileHandler $fileHandler Instance of the Tuskfish file handler.
 * @var         TfLogger $logger Instance of the Tuskfish error logger.
 */
class TfDatabase
{

    private $_db;
    private $validator;
    private $fileHandler;
    private $logger;
    
    /**
     * 
     * @param TfValidator $validator An instance of the Tuskfish data validator class.
     * @param TfLogger $logger An instance of the Tuskfish error logger class.
     * @param TfFileHandler $fileHandler An instance of the Tuskfish file handler class.
     */
    public function __construct(TfValidator $validator, TfLogger $logger,
            TfFileHandler $fileHandler)
    {
        $this->validator = $validator;
        $this->logger = $logger;
        $this->fileHandler = $fileHandler;
    }

    /** No cloning permitted. */
    final private function __clone()
    {
    }
    
    /** No serialisation. */
    final private function __wakeup()
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
    public function addBackticks(string $identifier)
    {
        return '`' . $identifier . '`';
    }

    /**
     * Close the connection to the database.
     * 
     * @return bool True on success false on failure.
     */
    public function close()
    {
        return $this->_close();
    }

    /** @internal */
    private function _close()
    {
        $this->_db = null;
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
    public function connect()
    {
        return $this->_connect();
    }

    /** @internal */
    private function _connect()
    {                
        // SQLite just expects a file name, which was defined as a constant during create()
        $this->_db = new PDO('sqlite:' . TFISH_DATABASE);
        
        if ($this->_db) {
            // Set PDO to throw exceptions every time it encounters an error.
            $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
     * @param string $dbName Database name.
     * @return string|bool Path to database file on success, false on failure.
     */
    public function create(string $dbName)
    {
        // Validate input parameters
        $dbName = $this->validator->trimString($dbName);
        
        if ($this->validator->isAlnumUnderscore($dbName)) {
            return $this->_create($dbName . '.db');
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
            exit;
        }
    }

    /** @internal */
    private function _create(string $dbName)
    {
        // Generate a random prefix for the database filename to make it unpredictable.
        $prefix = mt_rand();

        // Create database file and append a constant with the database path to config.php
        try {
            $dbPath = TFISH_DATABASE_PATH . $prefix . '_' . $dbName;
            $this->_db = new PDO('sqlite:' . $dbPath);
            $db_constant = PHP_EOL . 'if (!defined("TFISH_DATABASE")) define("TFISH_DATABASE", "'
                    . $dbPath . '");';
            $result = $this->fileHandler->appendToFile(TFISH_CONFIGURATION_PATH, $db_constant);
            
            if (!$result) {
                trigger_error(TFISH_ERROR_FAILED_TO_APPEND_FILE, E_USER_NOTICE);
                return false;
            }
            
            return $dbPath;
        } catch (PDOException $e) {
            $this->logger->logError($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
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
    public function createTable(string $table, array $columns, string $primary_key = null)
    {
        // Initialise
        $clean_primary_key = null;
        $clean_columns = array();

        // Validate input parameters
        $clean_table = $this->validateTableName($table);
        
        if ($this->validator->isArray($columns) && !empty($columns)) {
            $typeWhitelist = array("BLOB", "TEXT", "INTEGER", "NULL", "REAL");
            
            foreach ($columns as $key => $value) {
                $key = $this->escapeIdentifier($key);
                
                if (!$this->validator->isAlnumUnderscore($key)) {
                    trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
                    exit;
                }
                
                if (!in_array($value, $typeWhitelist, true)) {
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
        
        if (isset($primary_key)) {
            $primary_key = $this->escapeIdentifier($primary_key);
            
            if (array_key_exists($primary_key, $clean_columns)) {
                $clean_primary_key = $this->validator->isAlnumUnderscore($primary_key)
                        ? $primary_key : null;
            }
            
            if (!isset($clean_primary_key)) {
                trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
                exit;
            }
        }

        // Proceed with the query
        if ($clean_primary_key) {
            return $this->_createTable($clean_table, $clean_columns, $clean_primary_key);
        } else {
            return $this->_createTable($clean_table, $clean_columns);
        }
    }

    /** @internal */
    private function _createTable(string $table_name, array $columns,
            string $primary_key = null)
    {
        if (mb_strlen($table_name, 'UTF-8') > 0 && is_array($columns)) {
            $sql = "CREATE TABLE IF NOT EXISTS `" . $table_name . "` (";
            
            foreach ($columns as $key => $value) {
                $sql .= "`" . $key . "` " . $value . "";
                if (isset($primary_key) && $primary_key === $key) {
                    $sql .= " PRIMARY KEY";
                }
                $sql .= ", ";
            }
            
            $sql = trim($sql, ', ');
            $sql .= ")";
            $statement = $this->preparedStatement($sql);
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
    public function delete(string $table, int $id)
    {
        $clean_table = $this->validateTableName($table);
        $cleanId = $this->validateId($id);
        
        return $this->_delete($clean_table, $cleanId);
    }

    /** @internal */
    private function _delete(string $table, int $id)
    {
        $sql = "DELETE FROM " . $this->addBackticks($table) . " WHERE `id` = :id";
        $statement = $this->preparedStatement($sql);
        
        if ($statement) {
            $statement->bindValue(':id', $id, PDO::PARAM_INT);
        } else {
            return false;
        }
        
        return $this->executeTransaction($statement);
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
     * @param object $criteria TfCriteria object used to build conditional database query.
     * @return bool True on success, false on failure.
     */
    public function deleteAll(string $table, TfCriteria $criteria)
    {
        $clean_table = $this->validateTableName($table);
        $clean_criteria = $this->validateCriteriaObject($criteria);
        
        return $this->_deleteAll($clean_table, $clean_criteria);
    }

    /** @internal */
    private function _deleteAll(string $table, TfCriteria $criteria)
    {
        // Set table.
        $sql = "DELETE FROM " . $this->addBackticks($table) . " ";

        // Set WHERE criteria.
        if ($criteria) {

            if (!empty($criteria->item)) {
                $sql .= "WHERE ";
            }

            if ($this->validator->isArray($criteria->item)) {
                $pdo_placeholders = array();
                $sql .= $this->renderSql($criteria);
                $pdo_placeholders = $this->renderPdo($criteria);
            } else {
                trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
            }

            // Set the order (sort) column and order (default is ascending).
            if ($criteria->order) {
                $sql .= "ORDER BY `t1`." 
                        . $this->addBackticks($this->escapeIdentifier($criteria->order)) . " ";
                $sql .= $criteria->orderType === "DESC" ? "DESC" : "ASC";
                
                if ($criteria->secondaryOrder && ($criteria->secondaryOrder != $criteria->order)) {
                    $sql .= ", `t1`."
                         . $this->addBackticks($this->escapeIdentifier($criteria->secondaryOrder)) . " ";
                    $sql .= $criteria->secondaryOrderType === "DESC" ? "DESC" : "ASC";
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
        $statement = $this->preparedStatement($sql);
        
        if ($criteria) {
            if (!empty($pdo_placeholders)) {
                
                foreach ($pdo_placeholders as $placeholder => $value) {
                    $statement->bindValue($placeholder, $value, $this->setType($value));
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
        
        return $this->executeTransaction($statement);
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
    public function escapeIdentifier(string $identifier)
    {
        $cleanIdentifier = '';
        $identifier = $this->validator->trimString($identifier);
        $identifier = str_replace('"', '""', $identifier);
        $identifier = str_replace('`', '``', $identifier);
        $identifier = str_replace('[', '[[', $identifier);
        $cleanIdentifier = str_replace(']', ']]', $identifier);
        
        return $cleanIdentifier;
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
    public function executeTransaction(object $statement)
    {
        try {
            $this->_db->beginTransaction();
            $statement->execute();
            $this->_db->commit();
        } catch (PDOException $e) {
            $this->_db->rollBack();
            $this->logger->logError($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
        
        return true;
    }

    /**
     * Insert a single row into the database within a transaction.
     * 
     * @param string $table Name of table.
     * @param array $keyValues Column names and values to be inserted.
     * @return bool True on success, false on failure.
     */
    public function insert(string $table, array $keyValues)
    {
        $clean_table = $this->validateTableName($table);
        $cleanKeys = $this->validateKeys($keyValues);
        
        return $this->_insert($clean_table, $cleanKeys);
    }

    /** @internal */
    private function _insert(string $table, array $keyValues)
    {
        $pdo_placeholders = '';
        $sql = "INSERT INTO " . $this->addBackticks($table) . " (";

        // Prepare statement
        foreach ($keyValues as $key => $value) {
            $pdo_placeholders .= ":" . $key . ", ";
            $sql .= $this->addBackticks($key) . ", ";
            unset($key, $value);
        }
        
        $pdo_placeholders = trim($pdo_placeholders, ', ');
        $sql = trim($sql, ', ');
        $sql .= ") VALUES (" . $pdo_placeholders . ")";

        // Prepare the statement and bind the values.
        $statement = $this->_db->prepare($sql);
        
        foreach ($keyValues as $key => $value) {
            $statement->bindValue(":" . $key, $value, $this->setType($value));
            unset($key, $value);
        }
        
        return $this->executeTransaction($statement);
    }

    /**
     * Retrieves the ID of the last row inserted into the database.
     * 
     * Used primarily to grab the ID of newly created content objects so that their accompanying
     * taglinks can be correctly associated to them.
     * 
     * @return int|bool Row ID on success, false on failure.
     */
    public function lastInsertId()
    {
        if ($this->_db->lastInsertId()) {
            return (int) $this->_db->lastInsertId();
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
    public function preparedStatement(string $sql)
    {
        return $this->_preparedStatement($sql);
    }

    /** @internal */
    private function _preparedStatement(string $sql)
    {
        return $this->_db->prepare($sql);
    }
    
    /**
     * Generate an array of PDO placeholders based on criteria items.
     * 
     * Use this function to get a list of placeholders generated by renderSql(). The two functions
     * should be used together; use renderSql() to create a WHERE clause with named placeholders,
     * and renderPdo() to get a list of the named placeholders so that you can bind values to them.
     * 
     * @param TfCriteria $criteria Query composer object containing parameters for building a query.
     * @return array $pdo_placeholders Array of PDO placeholders used for building SQL query.
     */
    private function renderPdo(TfCriteria $criteria)
    {
        $pdo_placeholders = array();
        $count = count($criteria->item);
        
        for ($i = 0; $i < $count; $i++) {
            $pdo_placeholders[":placeholder" . (string) $i] = $criteria->item[$i]->value;
        }

        return $pdo_placeholders;
    }

    /**
     * Generate an SQL WHERE clause with PDO placeholders based on criteria items.
     * 
     * Loop through the criteria items building a list of PDO placeholders together
     * with the SQL. These will be used to bind the values in the statement to prevent
     * SQL injection. Note that values are NOT inserted into the SQL directly.
     * 
     * Enclose column identifiers in backticks to escape them. Link criteria items with AND/OR
     * except on the last iteration ($count-1).
     * 
     * @param TfCriteria $criteria Query composer object containing parameters for building a query.
     * @return string $sql SQL query fragment.
     */
    private function renderSql(TfCriteria $criteria)
    {
        $sql = '';
        $count = count($criteria->item);
        
        if ($count) {
            $sql = "(";
            
            for ($i = 0; $i < $count; $i++) {
                $sql .= "`" . $this->escapeIdentifier($criteria->item[$i]->column) . "` " 
                        . $this->renderOperator($criteria->item[$i]->operator) . " :placeholder"
                        . (string) $i;
                
                if ($i < ($count - 1)) {
                    $sql .= " " . $this->renderAndOr($criteria->condition[$i]) . " ";
                }
            }
            $sql .= ") ";
        }

        return $sql;
    }
    
    /**
     * Validates and renders an "AND" or "OR" for use in a query.
     * 
     * @param string $condition Must be either "AND" or "OR".
     * @return string Validated AND/OR condition.
     */
    private function renderAndOr(string $condition)
    {
        $clean_condition = $this->validator->trimString($condition);
        
        if ($clean_condition === "AND" || $clean_condition === "OR") {
            return $clean_condition;
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
        }
    }
    
    /**
     * Validates and renders an expression operator for use in query.
     * 
     * @param string $operator
     * @return string Validated operator.
     */
    private function renderOperator(string $operator)
    {
        $cleanOperator = $this->validator->trimString($operator);
        $permitted_operators = array('=', '==', '<', '<=', '>', '>=', '!=', '<>', 'LIKE');
        
        if (in_array($cleanOperator, $permitted_operators, true)) {
            return $cleanOperator;
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
        }
        
    }

    /**
     * Generate an SQL WHERE clause with PDO placeholders based on the tag property.
     * 
     * Loop through the criteria->tags building a list of PDO placeholders together
     * with the SQL. These will be used to bind the values in the statement to prevent
     * SQL injection. Note that values are NOT inserted into the SQL directly.
     * 
     * @param TfCriteria $criteria Query composer object containing parameters for building a query.
     * @return string $sql SQL query fragment.
     */
    private function renderTagSql(TfCriteria $criteria)
    {
        $sql = '';
        $count = count($criteria->tag);
        
        if ($count === 1) {
            $sql .= "`taglink`.`tagId` = :tag0 ";
        } elseif ($count > 1) {
            $sql .= "`taglink`.`tagId` IN (";
            
            for ($i = 0; $i < count($criteria->tag); $i++) {
                $sql .= ':tag' . (string) $i . ',';
            }
            
            $sql = rtrim($sql, ',');
            $sql .= ") ";
        }
        
        return $sql;
    }
    
    /**
     * Generate an array of PDO placeholders based on the tag property.
     * 
     * Use this function to get a list of placeholders generated by renderTagSql(). The two
     * functions should be used together; use renderTagSql() to create a WHERE clause with named
     * placeholders, and renderTagPdo() to get a list of the named placeholders so that you can
     * bind values to them.
     * 
     * @param TfCriteria $criteria Query composer object containing parameters for building a query.
     * @return array $tag_placeholders Array of PDO placeholders used for building SQL query.
     */
    private function renderTagPdo(TfCriteria $criteria)
    {
        $tag_placeholders = array();
        
        for ($i = 0; $i < count($criteria->tag); $i++) {
            $tag_placeholders[":tag" . (string) $i] = (int) $criteria->tag[$i];
        }
        
        return $tag_placeholders;
    }

    /**
     * Prepare and execute a select query.
     * 
     * Returns a PDO statement object, from which results can be extracted with standard PDO calls.
     * 
     * @param string $table Name of table.
     * @param TfCriteria $criteria Query composer object used to build conditional database query.
     * @param array $columns Names of database columns to be selected.
     * @return object PDOStatement object on success PDOException on failure.
     */
    public function select(string $table, TfCriteria $criteria = null, array $columns = null)
    {
        $clean_table = $this->validateTableName($table);
        $clean_criteria = isset($criteria) ? $this->validateCriteriaObject($criteria) : null;
        $clean_columns = isset($columns) ? $this->validateColumns($columns) : array();
        
        return $this->_select($clean_table, $clean_criteria, $clean_columns);
    }

    /** @internal */
    private function _select(string $table, TfCriteria $criteria = null, array $columns)
    {
        // Specify operation.
        $sql = "SELECT ";

        // Select columns.
        if ($columns) {
            foreach ($columns as $column) {
                $sql .= '`t1`.' . $this->addBackticks($column) . ", ";
            }
            
            $sql = rtrim($sql, ", ") . " ";
        } else {
            $sql .= "`t1`.* ";
        }

        // Set table.
        $sql .= "FROM " . $this->addBackticks($table) . " AS `t1` ";

        // Check if a tag filter has been applied (JOIN is required).
        if (isset($criteria) && !empty($criteria->tag)) {
            $sql .= $this->_renderTagJoin($table);
        }

        // Set WHERE criteria.
        if (isset($criteria)) {
            if (!empty($criteria->item) || !empty($criteria->tag)) {
                $sql .= "WHERE ";
            }

            if ($this->validator->isArray($criteria->item)) {
                $pdo_placeholders = array();
                $sql .= $this->renderSql($criteria);
                $pdo_placeholders = $this->renderPdo($criteria);
            } else {
                trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
            }

            if (!empty($criteria->item) && !empty($criteria->tag)) {
                $sql .= "AND ";
            }

            // Set tag(s).
            if (!empty($criteria->tag)) {
                $sql .= $this->renderTagSql($criteria);
                $tag_placeholders = $this->renderTagPdo($criteria);
            }

            // Set GROUP BY.
            if ($criteria->groupBy) {
                $sql .= " GROUP BY `t1`."
                        . $this->addBackticks($this->escapeIdentifier($criteria->groupBy));
            }

            // Set the order (sort) column and order (default is ascending).
            if ($criteria->order) {
                $sql .= " ORDER BY `t1`."
                        . $this->addBackticks($this->escapeIdentifier($criteria->order)) . " ";
                $sql .= $criteria->orderType === "DESC" ? "DESC" : "ASC";
                
                if ($criteria->secondaryOrder && ($criteria->secondaryOrder != $criteria->order)) {
                    $sql .= ", `t1`."
                         . $this->addBackticks($this->escapeIdentifier($criteria->secondaryOrder)) . " ";
                    $sql .= $criteria->secondaryOrderType === "DESC" ? "DESC" : "ASC";
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
        $statement = $this->preparedStatement($sql);
        
        if (isset($criteria) && $statement) {
            if (!empty($pdo_placeholders)) {
                foreach ($pdo_placeholders as $placeholder => $value) {
                    $statement->bindValue($placeholder, $value, $this->setType($value));
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
     * @param TfCriteria $criteria Query composer object used to build conditional database query.
     * @param string $column Name of column.
     * @return int|object Row count on success, PDOException object on failure.
     */
    public function selectCount(string $table, TfCriteria $criteria = null, string $column = '')
    {
        $clean_table = $this->validateTableName($table);
        $clean_criteria = isset($criteria) ? $this->validateCriteriaObject($criteria) : null;
        
        if ($column) {
            $column = $this->escapeIdentifier($column);
            
            if ($this->validator->isAlnumUnderscore($column)) {
                $clean_column = $column;
            } else {
                trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
                exit;
            }
        } else {
            $clean_column = "*";
        }
        
        return $this->_selectCount($clean_table, $clean_criteria, $clean_column);
    }

    /** @internal */
    private function _selectCount(string $table, TfCriteria $criteria, string $column)
    {
        // Specify operation and column
        $sql = "SELECT COUNT(";
        $sql .= $column = "*" ? $column : $this->addBackticks($column);
        $sql .= ") ";

        // Set table.
        $sql .= "FROM " . $this->addBackticks($table) . " AS `t1` ";

        // Check if a tag filter has been applied (JOIN is required).
        if (isset($criteria) && !empty($criteria->tag)) {
            $sql .= $this->_renderTagJoin($table);
        }

        // Set WHERE criteria.
        if (isset($criteria)) {

            if (!empty($criteria->item) || !empty($criteria->tag)) {
                $sql .= "WHERE ";
            }

            if ($this->validator->isArray($criteria->item)) {
                $pdo_placeholders = array();
                $sql .= $this->renderSql($criteria);
                $pdo_placeholders = $this->renderPdo($criteria);
            } else {
                trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
                exit;
            }

            if (!empty($criteria->item) && !empty($criteria->tag)) {
                $sql .= "AND ";
            }

            // Set tag(s).
            if (!empty($criteria->tag)) {
                $sql .= $this->renderTagSql($criteria);
                $tag_placeholders = $this->renderTagPdo($criteria);
            }
        }

        // Prepare the statement and bind the values.
        $statement = $this->preparedStatement($sql);
        if (isset($criteria) && $statement) {
            if (!empty($pdo_placeholders)) {
                foreach ($pdo_placeholders as $placeholder => $value) {
                    $statement->bindValue($placeholder, $value, $this->setType($value));
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
     * @param TfCriteria $criteria Query composer object used to build conditional database query.
     * @param array $columns Name of columns to filter results by.
     * @return object PDOStatement on success, PDOException on failure.
     */
    public function selectDistinct(string $table, TfCriteria $criteria = null, array $columns)
    {
        // Validate the tablename (alphanumeric characters only).
        $clean_table = $this->validateTableName($table);
        $clean_criteria = isset($criteria) ? $this->validateCriteriaObject($criteria) : null;
        $clean_columns = !empty($columns) ? $this->validateColumns($columns) : array();
        
        return $this->_selectDistinct($clean_table, $clean_criteria, $clean_columns);
    }

    /** @internal */
    private function _selectDistinct(string $table, TfCriteria $criteria, array $columns)
    {
        // Specify operation
        $sql = "SELECT DISTINCT ";

        // Select columns.
        foreach ($columns as $column) {
            $sql .= '`t1`.' . $this->addBackticks($column) . ", ";
        }
        
        $sql = rtrim($sql, ", ") . " ";

        // Set table.
        $sql .= "FROM " . $this->addBackticks($table) . " AS `t1` ";

        // Set parameters.
        if (isset($criteria)) {

            if (!empty($criteria->item) || !empty($criteria->tag)) {
                $sql .= "WHERE ";
            }

            if ($this->validator->isArray($criteria->item)) {
                $pdo_placeholders = array();
                $sql .= $this->renderSql($criteria);
                $pdo_placeholders = $this->renderPdo($criteria);
            } else {
                trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
                exit;
            }

            if (!empty($criteria->item) && !empty($criteria->tag)) {
                $sql .= "AND ";
            }

            // Set tag(s).
            if (!empty($criteria->tag)) {
                $sql .= $this->renderTagSql($criteria);
                $tag_placeholders = $this->renderTagPdo($criteria);
            }

            // Set GROUP BY.
            if ($criteria->groupBy) {
                $sql .= " GROUP BY `t1`."
                        . $this->addBackticks($this->escapeIdentifier($criteria->groupBy));
            }

            // Set the order (sort) column and type (default is ascending)
            if ($criteria->order) {
                $sql .= " ORDER BY `t1`."
                        . $this->addBackticks($this->escapeIdentifier($criteria->order)) . " ";
                $sql .= $criteria->orderType === "DESC" ? "DESC" : "ASC";
                
                if ($criteria->secondaryOrder && ($criteria->secondaryOrder != $criteria->order)) {
                    $sql .= ", `t1`."
                         . $this->addBackticks($this->escapeIdentifier($criteria->secondaryOrder)) . " ";
                    $sql .= $criteria->secondaryOrderType === "DESC" ? "DESC" : "ASC";
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
        $statement = $this->preparedStatement($sql);
        if (isset($criteria) && $statement) {
            if (!empty($pdo_placeholders)) {
                foreach ($pdo_placeholders as $placeholder => $value) {
                    $statement->bindValue($placeholder, $value, $this->setType($value));
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
    public function toggleBoolean(int $id, string $table, string $column)
    {
        $cleanId = $this->validateId($id);
        $clean_table = $this->validateTableName($table);
        $clean_column = $this->validateColumns(array($column));
        $clean_column = reset($clean_column);
        
        return $this->_toggleBoolean($cleanId, $clean_table, $clean_column);
    }

    /** @internal */
    private function _toggleBoolean(int $id, string $table, string $column)
    {
        $sql = "UPDATE " . $this->addBackticks($table) . " SET " . $this->addBackticks($column)
                . " = CASE WHEN " . $this->addBackticks($column)
                . " = 1 THEN 0 ELSE 1 END WHERE `id` = :id";

        // Prepare the statement and bind the ID value.
        $statement = $this->preparedStatement($sql);
        
        if ($statement) {
            $statement->bindValue(":id", $id, PDO::PARAM_INT);
        }

        return $this->executeTransaction($statement);
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
    public function updateCounter(int $id, string $table, string $column)
    {
        $cleanId = $this->validateId($id);
        $clean_table = $this->validateTableName($table);
        $clean_column = $this->validateColumns(array($column));
        $clean_column = reset($clean_column);
        
        return $this->_updateCounter($cleanId, $clean_table, $clean_column);
    }

    /** @internal */
    private function _updateCounter(int $id, string $table, string $column)
    {
        $sql = "UPDATE " . $this->addBackticks($table) . " SET " . $this->addBackticks($column)
                . " = " . $this->addBackticks($column) . " + 1 WHERE `id` = :id";

        // Prepare the statement and bind the ID value.
        $statement = $this->preparedStatement($sql);
        
        if ($statement) {
            $statement->bindValue(":id", $id, PDO::PARAM_INT);
        }

        return $this->executeTransaction($statement);
    }

    /**
     * Update a single row in the database.
     * 
     * @param string $table Name of table.
     * @param int $id ID of row to update.
     * @param array $keyValues Array of column names and values to update.
     * @return bool True on success, false on failure.
     */
    public function update(string $table, int $id, array $keyValues)
    {
        $clean_table = $this->validateTableName($table);
        $cleanId = $this->validateId($id);
        $cleanKeys = $this->validateKeys($keyValues);
        
        return $this->_update($clean_table, $cleanId, $cleanKeys);
    }

    /** @internal */
    private function _update(string $table, int $id, array $keyValues)
    {
        // Prepare the statement
        $sql = "UPDATE " . $this->addBackticks($table) . " SET ";
        
        foreach ($keyValues as $key => $value) {
            $sql .= $this->addBackticks($key) . " = :" . $key . ", ";
        }
        
        $sql = trim($sql, ", ");
        $sql .= " WHERE `id` = :id";

        // Prepare the statement and bind the values.
        $statement = $this->preparedStatement($sql);
        
        if ($statement) {
            $statement->bindValue(":id", $id, PDO::PARAM_INT);
            
            foreach ($keyValues as $key => $value) {
                $type = gettype($value);
                $statement->bindValue(":" . $key, $value, $this->setType($type));
                unset($type);
            }
        } else {
            return false;
        }
        
        return $this->executeTransaction($statement);
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
     * @param array $keyValues Array of column names and values to update.
     * @param TfCriteria $criteria Query composer object used to build conditional database query.
     */
    public function updateAll(string $table, array $keyValues, TfCriteria $criteria = null)
    {
        $clean_table = $this->validateTableName($table);
        $cleanKeys = $this->validateKeys($keyValues);
        
        if (isset($criteria)) {
            $clean_criteria = $this->validateCriteriaObject($criteria);
        } else {
            $clean_criteria = null;
        }
        
        return $this->_updateAll($clean_table, $cleanKeys, $clean_criteria);
    }

    /** @internal */
    private function _updateAll(string $table, array $keyValues, TfCriteria $criteria)
    {
        // Set table.
        $sql = "UPDATE " . $this->addBackticks($table) . " SET ";

        // Set key values.
        foreach ($keyValues as $key => $value) {
            $sql .= $this->addBackticks($key) . " = :" . $key . ", ";
        }
        
        $sql = rtrim($sql, ", ") . " ";

        // Set WHERE criteria.
        if (isset($criteria)) {

            if (!empty($criteria->item)) {
                $sql .= "WHERE ";
            }

            if ($this->validator->isArray($criteria->item)) {
                $pdo_placeholders = array();
                $sql .= $this->renderSql($criteria);
                $pdo_placeholders = $this->renderPdo($criteria);
            } else {
                trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
                exit;
            }
        }

        // Prepare the statement and bind the values.
        $statement = $this->preparedStatement($sql);
        
        foreach ($keyValues as $key => $value) {
            $statement->bindValue(':' . $key, $value, $this->setType($value));
            unset($key, $value);
        }
        
        if (isset($criteria)) {
            if (!empty($pdo_placeholders)) {
                foreach ($pdo_placeholders as $placeholder => $value) {
                    $statement->bindValue($placeholder, $value, $this->setType($value));
                    unset($placeholder);
                }
            }
        }

        return $this->executeTransaction($statement);
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
    public function setType($data)
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
    private function _renderTagJoin(string $table)
    {
        $sql = "INNER JOIN `taglink` ON `t1`.`id` = `taglink`.`contentId` ";

        return $sql;
    }

    /**
     * Validates the properties of a TfCriteria object to be used in constructing a query.
     * 
     * @param TfCriteria $criteria Query composer object.
     * @return TfCriteria Validated TfCriteria object.
     */
    public function validateCriteriaObject(TfCriteria $criteria)
    {
        
        if ($criteria->item) {
            if (!$this->validator->isArray($criteria->item)) {
                trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
                exit;
            }
            
            if (empty($criteria->condition)) {
                trigger_error(TFISH_ERROR_REQUIRED_PROPERTY_NOT_SET, E_USER_ERROR);
                exit;
            }
            
            if (!$this->validator->isArray($criteria->condition)) {
                trigger_error(TFISH_ERROR_NOT_ARRAY, E_USER_ERROR);
                exit;
            }
            
            if (count($criteria->item) != count($criteria->condition)) {
                trigger_error(TFISH_ERROR_COUNT_MISMATCH, E_USER_ERROR);
                exit;
            }
            
            foreach ($criteria->item as $item) {
                if (!is_a($item, 'TfCriteriaItem')) {
                    trigger_error(TFISH_ERROR_NOT_CRITERIA_ITEM_OBJECT, E_USER_ERROR);
                    exit;
                }
                
                if (!$this->validator->isAlnumUnderscore($item->column)) {
                    trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
                    exit;
                }
                
                if ($item->operator && !in_array($item->operator, $item->getListOfPermittedOperators(),
                        true)) {
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
        
        if ($criteria->groupBy && !$this->validator->isAlnumUnderscore($criteria->groupBy)) {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
            exit;
        }
        
        if ($criteria->limit && !$this->validator->isInt($criteria->limit, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            exit;
        }
        
        if ($criteria->offset && !$this->validator->isInt($criteria->offset, 0)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
            exit;
        }
        
        if ($criteria->order && !$this->validator->isAlnumUnderscore($criteria->order)) {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
            exit;
        }
        
        if ($criteria->orderType &&
                ($criteria->orderType != "ASC" && $criteria->orderType != "DESC")) {
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
    public function validateColumns(array $columns)
    {
        $clean_columns = array();
        
        if ($this->validator->isArray($columns) && !empty($columns)) {
            foreach ($columns as $column) {
                $column = $this->escapeIdentifier($column);
                
                if ($this->validator->isAlnumUnderscore($column)) {
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
    public function validateId(int $id)
    {
        $cleanId = (int) $id;
        if ($this->validator->isInt($cleanId, 1)) {
            return $cleanId;
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
     * @param array $keyValues Array of unescaped keys.
     * @return array Array of valid and escaped keys.
     */
    public function validateKeys(array $keyValues)
    {
        $cleanKeys = array();
        
        if ($this->validator->isArray($keyValues) && !empty($keyValues)) {
            foreach ($keyValues as $key => $value) {
                $key = $this->escapeIdentifier($key);
                
                if ($this->validator->isAlnumUnderscore($key)) {
                    $cleanKeys[$key] = $value;
                } else {
                    trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
                    exit;
                }
                
                unset($key, $value);
            }
            
            return $cleanKeys;
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
    public function validateTableName(string $table_name)
    {
        $table_name = $this->escapeIdentifier($table_name);
        
        if ($this->validator->isAlnum($table_name)) {
            return $table_name;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUM, E_USER_ERROR);
            exit;
        }
    }

}
