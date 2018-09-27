<?php

/**
 * TfContentFactory class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1
 * @package     content
 */
// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/** 
 * Factory class that handles instantiation of content objects, handlers and controllers.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1
 * @package     content
 * @var         TfValidator $validator Instance of the Tuskfish data validator class.
 * @var         TfDatabase $db Instance of the Tuskfish database class.
 * @var         TfCriteriaFactory $criteriaFactory Instance of the Tuskfish criteria factory class.
 * @var         TfFileHandler $fileHandler Instance of the Tuskfish file handler class.
 * @var         TfTaglinkHandler $taglinkHandler Instance of the Tuskfish taglink handler class.
 */

class TfContentFactory
{

    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $fileHandler;
    protected $taglinkHandler;
    
    public function __construct(TfValidator $validator, TfDatabase $db, TfCriteriaFactory
            $criteriaFactory, TfFileHandler $fileHandler, TfTaglinkHandler $taglinkHandler)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator;
        } else {
            trigger_error(TFISH_ERROR_NOT_VALIDATOR, E_USER_ERROR);
        }
        
        if (is_a($db, 'TfDatabase')) {
            $this->db = $db;
        } else {
            trigger_error(TFISH_ERROR_NOT_DATABASE, E_USER_ERROR);
        }
        
        if (is_a($criteriaFactory, 'TfCriteriaFactory')) {
            $this->criteriaFactory = $criteriaFactory;
        } else {
            trigger_error(TFISH_ERROR_NOT_CRITERIA_FACTORY, E_USER_ERROR);
        }
        
        if (is_a($fileHandler, 'TfFileHandler')) {
            $this->fileHandler = $fileHandler;
        } else {
            trigger_error(TFISH_ERROR_NOT_FILE_HANDLER, E_USER_ERROR);
        }
        
        if (is_a($taglinkHandler, 'TfTaglinkHandler')) {
            $this->taglinkHandler = $taglinkHandler;
        } else {
            trigger_error(TFISH_ERROR_NOT_TAGLINK_HANDLER, E_USER_ERROR);
        }
    }
    
    /**
     * Returns a new content object or subclass.
     * 
     * @param string $type Type of content object.
     * @return obj Content object subclass. 
     */
    public function getContentObject(string $type)
    {
        $cleanType = $this->validator->trimString($type);
        
        $allowedTypes = array(
            'TfArticle',
            'TfAudio',
            'TfBlock',
            'TfCollection',
            'TfDownload',
            'TfImage',
            'TfStatic',
            'TfTag',
            'TfVideo');
        
        if (in_array($cleanType, $allowedTypes)) {
            return new $cleanType($this->validator);
        } else {
            echo $cleanType;
            trigger_error(TFISH_ERROR_ILLEGAL_TYPE, E_USER_ERROR);
        }
    }
    
    /**
     * Instantiates and returns the requested TfishContentHandler subclass.
     * 
     * @param string $type Name of the requested handler.
     * @return \TfCollectionHandler|\TfContentHandler|\TfTagHandler
     */
    public function getContentHandler(string $type)
    {        
        $cleanType = $this->validator->trimString($type);
        
        // Generic handler that manipulates content objects that do not have dedicated handlers.
        if ($cleanType === 'content') {
            return new TfContentHandler($this->validator, $this->db, $this->criteriaFactory,
                    $this->fileHandler, $this->taglinkHandler);
        }
        
        if ($cleanType === 'block') {
            return new TfBlockHandler($this->validator, $this->db, $this->criteriaFactory,
                    $this->fileHandler, $this->taglinkHandler);
        }
        
        if ($cleanType === 'collection') {
            return new TfCollectionHandler($this->validator, $this->db, $this->criteriaFactory,
                    $this->fileHandler, $this->taglinkHandler);
        }
        
        if ($cleanType === 'tag') {
            return new TfTagHandler($this->validator, $this->db, $this->criteriaFactory,
                    $this->fileHandler, $this->taglinkHandler);
        }
        
        trigger_error(TFISH_ERROR_NO_SUCH_HANDLER, E_USER_ERROR);
    }
    
}