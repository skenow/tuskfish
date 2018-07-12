<?php

/**
 * TfishLanguage trait file.
 * 
 * Provides common access to system languages.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */

/**
 * TfishLanguage trait.
 * 
 * Provides common access to system languages.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.03
 * @package     core
 * 
 */
trait TfishLanguage
{
    
    /**
     * Returns a list of languages for the content object submission form.
     * 
     * In the interests of brevity and sanity a full list is not provided. Add entries that you
     * want to use to the array using ISO 639-1 two-letter language codes, which you can find at:
     * https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes. Be aware that deleting entries that
     * are in use by your content objects will cause errors.
     * 
     * @return array Array of languages in ISO 639-1 code => name format.
     */
    public function getListOfLanguages()
    {
        return array(
            "en" => "English",
            "th" => "Thai",
        );
    }
    
}
