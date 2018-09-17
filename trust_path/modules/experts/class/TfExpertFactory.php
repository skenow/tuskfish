<?php

/**
 * TfExpertFactory class file.
 * 
 * @copyright   Simon Wilkinson 2018+(https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@tuskfish.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     experts
 */
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Handles instantiation of expert handlers (TfExpertHandler) and controllers (TfExpertController).
 *
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     experts
 */
class TfExpertFactory
{
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $fileHandler;
    protected $expertHandler;
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
        
        $this->expertHandler = $this->getExpertHandler();
        
        if (is_a($cache, 'TfCache')) {
            $this->cache = $cache;
        } else {
            trigger_error(TFISH_ERROR_NOT_CACHE, E_USER_ERROR);
        }
        
        if (is_a($template, 'TfTemplate')) {
            $this->template = $template;
        } else {
            trigger_error(TFISH_ERROR_NOT_TEMPLATE_OBJECT, E_USER_ERROR);
        }
    }
    
    public function getExpert()
    {
        return new TfExpert($this->validator);
    }
    
    public function getExpertHandler()
    {
        return new TfExpertHandler($this->validator, $this->db, $this->criteriaFactory, 
                $this->fileHandler);
    }
    
    public function getExpertController()
    {
        return new TfExpertController($this->validator, $this->db, $this->criteriaFactory, 
                $this->expertHandler, $this->cache, $this->template);
    }
    
}
