<?php

/**
 * Tuskfish template object.
 * 
 * Used to hold template variables and to render templates for display. A template object is
 * automatically made available on every page via tfish_header.php.
 *
 * @copyright	Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license		https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since		1.0
 * @package		core
 */
if (!defined("TFISH_ROOT_PATH"))
    die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

class TfishTemplate
{

    protected $__data = array(
        'template_set' => 'default'
    );

    public function __construct()
    {
        
    }

    /**
     * Get the value of a property.
     * 
     * Intercepts direct calls to access an object property. This method can be modified to impose
     * processing logic to the value before returning it.
     * 
     * @param string $property name
     * @return mixed|null $property value if it is set; otherwise null.
     */
    public function __get($property)
    {
        if (isset($this->__data[$property])) {
            return $this->__data[$property];
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
     * @param string $property name
     * @return bool
     */
    public function __isset($property)
    {
        if (isset($this->__data[$property])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Renders a html template file for display.
     * 
     * Extracts all properties assigned to the template object as variables and includes the
     * designated template file. The extracted variables are used to populate the dynamic sections
     * of the template. Templates can be nested by assigning a rendered child template as a property
     * of a parent template object.
     * 
     * @param string $template name file in the /templates/sometemplate directory.
     * @return string HTML rendered template
     */
    public function render($template)
    {
        /* if (array_key_exists('template_set', $this->__data)) {
          trigger_error(TFISH_CANNOT_OVERWRITE_TEMPLATE_VARIABLE, E_USER_ERROR);
          } */
        extract($this->__data);
        if (file_exists(TFISH_TEMPLATES_PATH . $this->__data['template_set'] . '/' . $template . '.html')) {
            ob_start();
            include TFISH_TEMPLATES_PATH . $this->__data['template_set'] . '/' . $template . '.html';
            return ob_get_clean();
        } else {
            echo TFISH_TEMPLATES_PATH . $this->__data['template_set'] . '/' . $template . '.html';
            trigger_error(TFISH_ERROR_TEMPLATE_DOES_NOT_EXIST, E_USER_ERROR);
        }
    }

    /**
     * Set the value of an object property.
     * 
     * Do not declare variables named $template_set or it will disrupt this method.
     * 
     * @param string $property name
     * @param mixed $value
     * @param return void
     */
    public function __set($property, $value)
    {
        //  if ($property == 'template_set') {
        //      trigger_error(TFISH_CANNOT_OVERWRITE_TEMPLATE_VARIABLE, E_USER_ERROR);
        //  }
        $this->__data[$property] = $value;
    }

    /**
     * Set the template set to be used.
     * 
     * The template_set must be specified through this method. This is a safety measure to prevent
     * someone accidentally overwriting the template set when assigning a variable to the template
     * object (if content were assigned to $tfish_template->template_set it would mess things up). 
     * 
     * @param string $template alphanumeric and underscore characters only.
     * @return void
     */
    public function setTemplate($template)
    {
        if (TfishFilter::isAlnumUnderscore($template)) {
            $clean_template = TfishFilter::trimString($template);
            $this->__data['template_set'] = $clean_template;
        }
    }

    /**
     * Unsets an object property.
     * 
     * Intercepts unset() calls to correctly unset object properties. Can be modified to add 
     * processing logic for specific properties.
     * 
     * @param string $property name
     * @return bool true on success false on failure 
     */
    public function __unset($property)
    {
        if (isset($this->__data[$property])) {
            unset($this->__data[$property]);
            return true;
        } else {
            return false;
        }
    }

}
