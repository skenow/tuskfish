<?php

/**
 * TfCriteriaFactory class file.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1
 * @package     database
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * TfCriteriaFactory class file.
 * 
 * Factory for instantiating TfCriteria objects and injecting dependencies. Use this class to
 * delegate construction of TfCriteria objects. See the Tuskfish Developer Guide for a full
 * explanation and examples.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1
 * @package     database
 * @var   TfValidator $validator Instance of the Tuskfish data validator class.
 */
class TfCriteriaFactory
{
    protected $validator;
    
    public function __construct(TfValidator $validator)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator;
        }
    }
    
    /**
     * Factory method to instantiate and return a TfCriteria object.
     * 
     * @return \TfCriteria Instance of a TfCriteria object.
     */
    public function getCriteria()
    {
        return new TfCriteria($this->validator);
    }
}
