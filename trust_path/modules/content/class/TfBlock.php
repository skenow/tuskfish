<?php

/**
 * TfBlock class file.
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
 * Static block object class.
 * 
 * Represents a block of content, use it to create HTML blocks to insert into your site layout.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @uses        trait TfLanguage to obtain a list of available translations.
 * @uses        trait TfMagicMethods Prevents direct setting of properties / unlisted properties.
 * @uses        trait TfMimetypes Access a list of known / acceptable file mimetypes.
 * @properties  TfValidator $validator Instance of the Tuskfish data validator class.
 * @properties  int $id Auto-increment, set by database.
 * @properties  string $type Content object type eg. TfArticle etc. [ALPHA]
 * @properties  string $title The name of this content.
 * @properties  string $description The full article or description of the content. [HTML]
 * @properties  array $tags Tag IDs associated with this object; not persistent (stored as taglinks in taglinks table).
 * @properties  int $online Toggle object on or offline.
 * @properties  int $submissionTime Timestamp representing submission time.
 * @properties  string $handler Handler for this object (not persistent).
 * @properties  string $template The template that should be used to display this object (not persistent).
 * @properties  string $module The module that handles this content type (not persistent).
 * @properties  string $icon The Font Awesome icon representing this content type (not persistent).
 */
class TfBlock extends TfContentObject
{
    
    /**
     * Constructor.
     * 
     * @param TfValidator $validator An instance of the Tuskfish data validator class.
     */
    public function __construct(TfValidator $validator)
    {
        // Must call parent constructor first.
        parent::__construct($validator);

        // Declare the type, template, module and icon for this this class
        $this->type = "TfBlock";
        $this->template = "block";
        $this->module = "blocks";
        $this->icon = '<i class="fas fa-cube"></i>';

        // Object definition - unset any properties unused in this subclass.
        $listOfZeroedProperties = $this->getListOfZeroedProperties();
        
        foreach ($listOfZeroedProperties as $property) {
            unset($this->$property);
        }
    }
    
    /**
     * Returns an array of base object properties that are not used by this subclass.
     * 
     * This list is also used in update calls to the database to ensure that unused columns are
     * cleared and reset with default values.
     * 
     * @return array Array of properties that should be zeroed (unset).
     */
    public function getListOfZeroedProperties()
    {
        return array(
            'teaser',
            'creator',
            'parent',
            'rights',
            'publisher',
            'counter',
            'metaTitle',
            'metaDescription',
            'seo'
        );
    }
    
    /**
     * Converts properties to human readable form in preparation for output.
     * 
     * If you have added some custom properties to this content subclass that need to be formatted
     * for output, add a switch above the call to the parent method. Structure it so that any case
     * not explicitly handled will fall through to the parent method, while explicit cases will
     * return a formatted value.
     * 
     * @param string $cleanProperty Name of content object property to be formatted.
     */
    protected function makeDataHumanReadable(string $cleanProperty)
    {
        return parent::makeDataHumanReadable($cleanProperty);
    }
        
    /**
     * Set the value of a whitelisted property.
     * 
     * Intercepts direct calls to set the value of an object property. This method is overridden by
     * child classes to impose data type restrictions and range checks on custom subclass
     * properties.
     * 
     * If you have added some custom properties to this content subclass that need to be type
     * and/or range checked before permitting assignment, add a switch above the call to the parent
     * method. Structure it so that any case not explicitly handled will fall through to the parent
     * method, while explicit cases will be handled here.
     * 
     * @param string $property Name of property.
     * @param mixed $value Value of property.
     */
    public function __set(string $property, $value)
    {
        parent::__set($property, $value);
    }

}
