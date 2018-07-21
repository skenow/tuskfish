<?php

/**
 * TfishSearchContent class file.
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
 * Searches database for content objects.
 * 
 * Provides search functionality for the content module.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.03
 * @package     content
 */
class TfishSearchContent
{
    protected $validator;
    protected $db;
    protected $preference;
    protected $search_terms;
    protected $escaped_search_terms;
    protected $limit;
    protected $offset;
    protected $operator; // and / or / exact
    
    public function __construct(TfishDataValidator $tfish_validator,
            TfishDatabase $tfish_database, TfishPreference $tfish_preference)
    {
        $this->validator = $tfish_validator;
        $this->db = $tfish_database;
        $this->preference = $tfish_preference;
        $this->search_terms = array();
        $this->escaped_search_terms = array();
        $this->limit = $tfish_preference->search_pagination;
        $this->offset = 0;
        $this->operator = 'AND';
    }
    
    public function setSearchTerms(string $search_terms)
    {
        $clean_search_terms = $escaped_search_terms = $clean_escaped_search_terms = array();
        
        // Create an escaped copy that will be used to search the HTML teaser and description fields.
        $escaped_search_terms = htmlspecialchars($search_terms, ENT_NOQUOTES, "UTF-8");

        if ($this->operator === 'AND' || $this->operator === 'OR') {
            $search_terms = explode(" ", $search_terms);
            $escaped_search_terms = explode(" ", $escaped_search_terms);
        } else {
            $search_terms = array($search_terms);
            $escaped_search_terms = array($escaped_search_terms);
        }
        
        // Trim search terms and discard any that are less than the minimum search length characters.
        foreach ($search_terms as $term) {
            $term = $this->validator->trimString($term);
            
            if (!empty($term) && mb_strlen($term, 'UTF-8') >= $this->preference->min_search_length) {
                $clean_search_terms[] = (string) $term;
            }
        }
        
        $this->search_terms = $clean_search_terms;
        
        foreach ($escaped_search_terms as $escaped_term) {
            $escaped_term = $this->validator->trimString($escaped_term);
            
            if (!empty($escaped_term) && mb_strlen($escaped_term, 'UTF-8')
                    >= $this->preference->min_search_length) {
                $clean_escaped_search_terms[] = (string) $escaped_term;
            }
        }
        
        $this->escaped_search_terms = $escaped_search_terms;
    }
    
    public function setLimit(int $limit)
    {
        $this->limit = $this->validator->isInt($limit, 0) ? (int) $limit : 0;
    }
    
    public function setOffset(int $offset)
    {
        $this->offset = $this->validator->isInt($offset, 0) ? (int) $offset : 0;
    }
    
    public function setOperator(string $operator)
    {
        $this->operator = in_array($operator, array('AND', 'OR', 'exact'))
                ? $this->validator->trimString($operator) : 'AND';
    }
    
    /**
     * Provides global search functionality for content objects.
     * 
     * Escaping of search terms is handled through use of a PDO prepared statement with named 
     * placeholders; search terms are inserted indirectly by binding them to the placeholders.
     * Search terms must NEVER be inserted into a query directly (creates an SQL injection
     * vulnerability), otherwise do us all a favour and go shoot yourself now.
     * 
     * Search terms have entity encoding (htmlspecialchars) applied on the teaser and description
     * fields (only) to ensure consistency with the entity encoding treatment that these HTML fields
     * have been subjected to, otherwise searches involving entities will not return results.
     */
    public function searchContent()
    {
        $sql = $count = '';
        $search_term_placeholders = $escaped_term_placeholders = $results = array();
        
        $sql_count = "SELECT count(*) ";
        $sql_search = "SELECT * ";
        $result = array();

        $sql = "FROM `content` ";
        $count = count($this->search_terms);
        
        if ($count) {
            $sql .= "WHERE ";
            
            for ($i = 0; $i < $count; $i++) {
                $search_term_placeholders[$i] = ':search_term' . (string) $i;
                $escaped_term_placeholders[$i] = ':escaped_search_term' . (string) $i;
                $sql .= "(";
                $sql .= "`title` LIKE " . $search_term_placeholders[$i] . " OR ";
                $sql .= "`teaser` LIKE " . $escaped_term_placeholders[$i] . " OR ";
                $sql .= "`description` LIKE " . $escaped_term_placeholders[$i] . " OR ";
                $sql .= "`caption` LIKE " . $search_term_placeholders[$i] . " OR ";
                $sql .= "`creator` LIKE " . $search_term_placeholders[$i] . " OR ";
                $sql .= "`publisher` LIKE " . $search_term_placeholders[$i];
                $sql .= ")";
                
                if ($i != ($count - 1)) {
                    $sql .= " " . $andor . " ";
                }
            }
        }
        
        $sql .= " AND `online` = 1 AND `type` != 'TfishBlock' ";
        $sql .= "ORDER BY `date` DESC, `submission_time` DESC ";
        $sql_count .= $sql;
        
        // Bind the search term values and execute the statement.
        $statement = $this->db->preparedStatement($sql_count);
        
        if ($statement) {
            for ($i = 0; $i < $count; $i++) {
                $statement->bindValue($search_term_placeholders[$i], "%" . $this->search_terms[$i] . "%",
                        PDO::PARAM_STR);
                $statement->bindValue($escaped_term_placeholders[$i], "%" . $this->escaped_search_terms[$i] . "%",
                        PDO::PARAM_STR);
            }
        } else {
            return false;
        }

        // Execute the statement.
        $statement->execute();

        $row = $statement->fetch(PDO::FETCH_NUM);
        $result[0] = reset($row);
        unset($statement);

        // Retrieve the subset of objects actually required.
        if (!$this->limit) {
            $limit = $this->preference->search_pagination;
        }
        
        $sql .= "LIMIT :limit ";
        
        if ($this->offset) {
            $sql .= "OFFSET :offset ";
        }

        $sql_search .= $sql;
        $statement = $this->db->preparedStatement($sql_search);
        
        if ($statement) {
            for ($i = 0; $i < $count; $i++) {
                $statement->bindValue($search_term_placeholders[$i], "%" . $this->search_terms[$i] . "%",
                        PDO::PARAM_STR);
                $statement->bindValue($escaped_term_placeholders[$i], "%" . $this->escaped_search_terms[$i]
                        . "%", PDO::PARAM_STR);
                $statement->bindValue(":limit", (int) $this->limit, PDO::PARAM_INT);
                
                if ($this->offset) {
                    $statement->bindValue(":offset", (int) $this->offset, PDO::PARAM_INT);
                }
            }
        } else {
            return false;
        }

        // Execute the statement, fetch rows into the appropriate class type as determined by the
        // first column of the table.
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_CLASSTYPE | PDO::FETCH_PROPS_LATE);
        
        while ($object = $statement->fetch()) {
            $result[$object->id] = $object;
        }
        
        return $result;
    }
}
