<?php

/**
 * TfishTemplate class file.
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
 * Tuskfish template object.
 * 
 * Used to hold template variables and to render templates for display. A template object is
 * automatically made available on every page via tfish_header.php.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @property    string $theme The theme (template set) in use on this page.
 */
class TfishTemplate
{
    
    /** @var array $__data Array holding values of this object's properties. */
    protected $theme = 'default';

    /**
     * Renders a HTML template file for display.
     * 
     * Extracts all properties assigned to the template object as variables and includes the
     * designated template file. The extracted variables are used to populate the dynamic sections
     * of the template. Templates can be nested by assigning a rendered child template as a property
     * of a parent template object.
     * 
     * @param string $template Name of the template file in the /themes/sometemplate directory.
     * @return string Rendered HTML template.
     */
    public function render(string $template)
    {
        $template = TfishDataValidator::trimString($template);
        
        // Check for directory traversals and null byte injection.
        if (TfishDataValidator::hasTraversalorNullByte($template)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
        }
        
        extract((array) $this);
        
        if (file_exists(TFISH_THEMES_PATH . $this->theme . '/' . $template
                . '.html')) {
            ob_start();
            include TFISH_THEMES_PATH . $this->theme . '/' . $template
                    . '.html';
            return ob_get_clean();
        } else {
            echo $this->theme . '/' . $template . '.html'; // Helps debug.
            trigger_error(TFISH_ERROR_TEMPLATE_DOES_NOT_EXIST, E_USER_ERROR);
        }
    }

    /**
     * Set the theme (template set) to be used.
     * 
     * The theme must be specified through this method. This is a safety measure to prevent
     * someone accidentally overwriting the template set when assigning a variable to the template
     * object (if content were assigned to $tfish_template->setTheme() it would mess things up). 
     * 
     * @param string $theme Name of theme (alphanumeric and underscore characters only).
     */
    public function setTheme(string $theme)
    {
        // Check for directory traversals and null byte injection.
        if (TfishDataValidator::hasTraversalorNullByte($theme)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
        }
        
        if (TfishDataValidator::isAlnumUnderscore($theme)) {
            $clean_theme = TfishDataValidator::trimString($theme);
            $this->theme = $clean_theme;
        }
    }
    
    /**
     * Get the value of a property.
     * 
     * Intercepts direct calls to access an object property. This method can be overridden to impose
     * processing logic to the value before returning it.
     * 
     * @param string $property Name of property.
     * @return mixed|null $property Value of property if it is set; otherwise null.
     */
    public function __get(string $property)
    {
        $clean_property = TfishDataValidator::trimString($property);
        
        if (isset($clean_property)) {
            return $this->$clean_property;
        } else {
            return null;
        }
    }

}
