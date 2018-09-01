<?php

/**
 * TfMachineFactory class file.
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
 * Handles instantiation of machine handlers (TfMachineHandler) and controllers (TfMachineController).
 *
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     machines
 */
class TfMachineFactory
{
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $fileHandler;
    protected $machineHandler;
    protected $cache;
    protected $template;
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfFileHandler $fileHandler, TfCache $cache,
            TfTemplate $template)
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
        
        $this->machineHandler = $this->getMachineHandler();
        
        if (is_a($cache, 'TfCache')) {
            $this->cache = $cache;
        } else {
            trigger_error(TFISH_ERROR_NOT_MACHINE_HANDLER, E_USER_ERROR);
        }
        
        if (is_a($template, 'TfTemplate')) {
            $this->template = $template;
        } else {
            trigger_error(TFISH_ERROR_NOT_TEMPLATE_OBJECT, E_USER_ERROR);
        }
    }
    
    public function getMachine()
    {
        return new TfMachine($this->validator);
    }
    
    public function getMachineHandler()
    {
        return new TfMachineHandler($this->validator, $this->db, $this->criteriaFactory, 
                $this->fileHandler);
    }
    
    public function getMachineController()
    {
        return new TfMachineController($this->validator, $this->db, $this->criteriaFactory, 
                $this->machineHandler, $this->cache, $this->template);
    }
    
}
