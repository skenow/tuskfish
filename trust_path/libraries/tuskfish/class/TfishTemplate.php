<?php

/**
 * TfishTemplate class file.
 * 
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Tuskfish template object.
 * 
 * Used to hold template variables and to render templates for display. A template object is
 * automatically made available on every page via tfish_header.php.
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
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
    protected $__data = array(
        'theme' => 'default'
    );

    /**
     * Get the value of a property.
     * 
     * Intercepts direct calls to access an object property. This method can be modified to impose
     * processing logic to the value before returning it.
     * 
     * @param string $property Name of Property.
     * @return mixed|null $property Value of property if it is set; otherwise null.
     */
    public function __get($property)
    {
        $clean_property = TfishFilter::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            return $this->__data[$clean_property];
        } else {
            return null;
        }
    }

    /**
     * Check if a property is set.
     * 
     * Intercepts isset() calls to correctly read object properties. Can be modified to add
     * processing logic to specific properties.
     * 
     * @param string $property Name of property.
     * @return bool True if set, otherwise false.
     */
    public function __isset($property)
    {
        $clean_property = TfishFilter::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            return true;
        } else {
            return false;
        }
    }

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
    public function render($template)
    {
        $template = TfishFilter::trimString($template);
        
        // Check for directory traversals and null byte injection.
        if (TfishFilter::hasTraversalorNullByte($template)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
        }
        
        extract($this->__data);
        if (file_exists(TFISH_THEMES_PATH . $this->__data['theme'] . '/' . $template
                . '.html')) {
            ob_start();
            include TFISH_THEMES_PATH . $this->__data['theme'] . '/' . $template
                    . '.html';
            return ob_get_clean();
        } else {
            echo $this->__data['theme'] . '/' . $template . '.html'; // Helps debug.
            trigger_error(TFISH_ERROR_TEMPLATE_DOES_NOT_EXIST, E_USER_ERROR);
        }
    }

    /**
     * Set the value of an object property.
     * 
     * Do not declare variables named $theme or it will disrupt this method.
     * 
     * @param string $property Name of property.
     * @param mixed $value Value to assign to property.
     */
    public function __set($property, $value)
    {
        $clean_property = TfishFilter::trimString($property);
        
        if ($clean_property === 'theme') {
            $this->setTheme($value);
            return;
        }
        
        $this->__data[$clean_property] = $value;
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
    public function setTheme($theme)
    {
        // Check for directory traversals and null byte injection.
        if (TfishFilter::hasTraversalorNullByte($theme)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
        }
        
        if (TfishFilter::isAlnumUnderscore($theme)) {
            $clean_theme = TfishFilter::trimString($theme);
            $this->__data['theme'] = $clean_theme;
        }
    }

    /**
     * Unsets an object property.
     * 
     * Intercepts unset() calls to correctly unset object properties. Can be modified to add 
     * processing logic for specific properties.
     * 
     * @param string $property Name of property.
     * @return bool True on success, false on failure.
     */
    public function __unset($property)
    {
        $clean_property = TfishFilter::trimString($property);
        
        if (isset($this->__data[$clean_property])) {
            unset($this->__data[$clean_property]);
            return true;
        } else {
            return false;
        }
    }

}
