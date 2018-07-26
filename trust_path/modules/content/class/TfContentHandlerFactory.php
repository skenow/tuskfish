<?php

/**
 * TfContentHandlerFactory class file.
 * 
 * Instantiates TfContentHandler objects and handles dependency injection.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @var         TfValidator $validator Instance of the Tuskfish data validator class.
 * @var         TfDatabase $db Instance of the Tuskfish database class.
 * @var         TfCriteriaFactory $criteriaFactory Instance of the Tuskfish criteria factory class.
 * @var         TfCriteriaItemFactory $itemFactory Instance of the Tuskfish criteria item factory.
 * @var         TfFileHandler $fileHandler Instance of the Tuskfish file handler class.
 * @var         TfTaglinkHandler $taglinkHandler Instance of the Tuskfish taglink handler class.
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfContentHandlerFactory
{
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $itemFactory;
    protected $fileHandler;
    protected $taglinkHandler;
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfCriteriaItemFactory $itemFactory,
            TfFileHandler $fileHandler)
    {
        $this->validator = $validator;
        $this->db = $db;
        $this->criteriaFactory = $criteriaFactory;
        $this->itemFactory = $itemFactory;
        $this->fileHandler = $fileHandler;
        $this->taglinkHandler = new TfTaglinkHandler($validator, $db, $criteriaFactory,
                $itemFactory);
    }
    
    public function getHandler(string $type)
    {        
        $cleanType = $this->validator->trimString($type);
        
        if ($cleanType === 'content') {
            return new TfContentHandler($this->validator, $this->db, $this->criteriaFactory,
                    $this->itemFactory, $this->fileHandler, $this->taglinkHandler);
        }
        
        if ($cleanType === 'collection') {
            return new TfCollectionHandler($this->validator, $this->db, $this->criteriaFactory,
                    $this->itemFactory, $this->fileHandler, $this->taglinkHandler);
        }
        
        if ($cleanType === 'tag') {
            return new TfTagHandler($this->validator, $this->db, $this->criteriaFactory,
                    $this->itemFactory, $this->fileHandler, $this->taglinkHandler);
        }
        
        trigger_error(TFISH_ERROR_NO_SUCH_HANDLER, E_USER_ERROR);
    }
}
