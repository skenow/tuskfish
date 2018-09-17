<?php

/**
 * Expert handler class file.
 * 
 * @copyright   Simon Wilkinson 2018+(https://isengard.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Your name <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     experts
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Manipulates expert (TfExpert) objects.
 *
 * @copyright   Simon Wilkinson 2018+ (https://isengard.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     experts
 */

class tfExpertHandler
{
    
    public function getCountryList()
    {
        return array(0 => TFISH_ZERO_OPTION);
    }
    
    public function getTagList()
    {
        return array(0 => TFISH_ZERO_OPTION);
    }
    
    /**
     * Returns an array of known / permitted salutations.
     * 
     * @return array List of salutations as key => value pairs.
     */
    public function getSalutationList()
    {
        return array(
            0 => "Dr",
            1 => "Prof.",
            2 => "Mr",
            3 => "Mrs",
            4 => "Ms"
        );
    }
}
