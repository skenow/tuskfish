<?php

/**
 * TfPaginationControlFactory class file.
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
 * Handles instantiation of pagination controls.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.1
 * @package     content
 */
class TfPaginationControlFactory
{
    
    protected $validator;
    protected $preference;
    
    /**
     * Constructor.
     * 
     * @param TfValidator $validator An instance of the Tuskfish data validator class.
     * @param TfPreference $preference An instance of the Tuskfish site preferences class.
     */
    public function __construct(TfValidator $validator, TfPreference $preference)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator; 
        } else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
        
        if (is_a($preference, 'TfPreference')) {
            $this->preference = $preference;
        }  else {
            trigger_error(TFISH_ERROR_NOT_OBJECT, E_USER_ERROR);
        }
    }
    
    public function getPaginationControl()
    {
        return new TfPaginationControl($this->validator, $this->preference);
    }
    
}
