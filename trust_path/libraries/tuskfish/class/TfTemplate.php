<?php

/**
 * TfTemplate class file.
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
 * automatically made available on every page via tfHeader.php.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @var         TfValidator $validator Instance of the Tuskfish data validator class.
 * @var         string $theme The theme (template set) in use on this page.
 */
class TfTemplate
{
    // Note that the data validator is *required* to escape data within the scope of templates.
    protected $validator;
    protected $theme = 'default';  
    
    public function __construct(TfValidator $validator)
    {
        $this->validator = $validator;
    }
    
    /**
     * Retrieve the name of the theme in use on this page.
     * 
     * @return string Returns the the name of the theme in use.
     */
    public function getTheme()
    {
        if (isset($this->theme)) {
            return $this->theme;
        } else {
            return 'default';
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
    public function render(string $template)
    {
        // Make the data validator available within scope of the templates.
        $validator = $this->validator;
        
        $template = $this->validator->trimString($template);
        
        // Check for directory traversals and null byte injection.
        if ($this->validator->hasTraversalorNullByte($template)) {
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
     * object (if content were assigned to $tfTemplate->setTheme() it would mess things up). 
     * 
     * @param string $theme Name of theme (alphanumeric and underscore characters only).
     */
    public function setTheme(string $theme)
    {
        // Check for directory traversals and null byte injection.
        if ($this->validator->hasTraversalorNullByte($theme)) {
            trigger_error(TFISH_ERROR_TRAVERSAL_OR_NULL_BYTE, E_USER_ERROR);
        }
        
        if ($this->validator->isAlnumUnderscore($theme)) {
            $clean_theme = $this->validator->trimString($theme);
            $this->theme = $clean_theme;
        }
    }

}
