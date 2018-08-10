<?php
/**
 * TfContentControllerFactory class file.
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
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */
     
class TfContentControllerFactory
{
    
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $contentHandlerFactory;
    protected $paginationFactory;
    protected $template;
    protected $preference;
    protected $cache;
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfContentHandlerFactory $contentHandlerFactory,
            TfPaginationControlFactory $paginationFactory, TfTemplate $template,
            TfMetadata $metadata, TfPreference $preference, TfCache $cache)
    {
        $this->validator = $validator;
        $this->db = $db;
        $this->criteriaFactory = $criteriaFactory;
        $this->contentHandlerFactory = $contentHandlerFactory;
        $this->paginationFactory = $paginationFactory;
        $this->template = $template;
        $this->metadata = $metadata;
        $this->preference = $preference;
        $this->cache = $cache;
    }
    
    public function getController(string $type)
    {
        $cleanType = $this->validator->trimString($type);
        
        if ($cleanType === 'admin') {
            return new TfContentObjectController($this->validator, $this->db, $this->criteriaFactory,
                    $this->contentHandlerFactory, $this->template, $this->preference,
                    $this->cache);
        }
        
        if ($cleanType === 'view') {
            return new TfContentViewController($this->validator, $this->db, $this->criteriaFactory,
                    $this->contentHandlerFactory, $this->paginationFactory, $this->template,
                    $this->metadata, $this->preference);
        }
        
        return false;
    }
}
