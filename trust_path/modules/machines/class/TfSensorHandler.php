<?php

/**
 * TfSensorHandler class file.
 * 
 * @copyright   Simon Wilkinson 2018+(https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@tuskfish.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Manipulates sensor objects (TfSensor).
 *
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
class TfSensorHandler
{
    
    use TfSensorTypes;
    use TfDataProtocols;
    
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $fileHandler;
    
    /**
     * Constructor.
     * 
     * @param TfValidator $validator Instance of the Tuskfish validator class.
     * @param TfDatabase $db Instance of the Tuskfish database class.
     * @param TfCriteriaFactory $criteriaFactory Instance of the Tuskfish criteria factory class.
     * @param TfFileHandler $fileHandler Instance of the Tuskfish file handler class.
     * @param TfTaglinkHandler $taglinkHandler Instance of the Tuskfish taglink handler class.
     */
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfFileHandler $fileHandler)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator; 
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (is_a($db, 'TfDatabase')) {
            $this->db = $db; 
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (is_a($criteriaFactory, 'TfCriteriaFactory')) {
            $this->criteriaFactory = $criteriaFactory; 
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (is_a($fileHandler, 'TfFileHandler')) {
            $this->fileHandler = $fileHandler; 
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
    }
    
    public function insert(TfMachine $machine)
    {
        
    }
    
    public function delete(int $id)
    {
        
    }
    
    public function getObject()
    {
        
    }
    
    public function getObjects()
    {
        
    }    
    
}
