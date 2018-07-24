<?php

/**
 * Factory for instantiating TfValidator and configuring its HTMLPurifier dependency.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     security
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Factory for instantiating TfValidator and configuring its HTMLPurifier dependency.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     security
 */
class TfValidatorFactory
{
    
    protected $html_purifier;
    
    public function __construct(array $config_options = array())
    {
        $config = $this->configureHTMLPurifier($config_options);
        $this->html_purifier = new HTMLPurifier($config);
    }
    
    /**
     * Configure HTMLPurifier for use with Tuskfish.
     * 
     * Tuskfish requires HTMLPurifier to use UTF-8 encoding; to allow the ID attribute in HTML,
     * which is required to provide CSS selector targets; and support for HTML5 tags.
     * 
     * By default HTMLPurifier removes ID attributes from HTML markup, as duplicate IDs render
     * markup technically invalid. However, it is widely known that IDs are supposed to be unique
     * and not an issue if you are doing things properly. Removing IDs breaks CSS that uses IDs as 
     * selectors, which *is* an issue. 
     * 
     * @param array $config_options HTMLPurifier configuration options (see HTMLPurifier documentation).
     * @return object HTMLPurifier configuration object.
     */
    private function configureHTMLPurifier(array $config_options)
    {
        // Set default configuration options.
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Attr.EnableID', true);
        $config->set('Attr.ID.HTML5', true);

        // Set optional configuration options.
        if ($config_options) {
            foreach ($config_options as $key => $value) {
                $config->set($key, $value);
            }
        }
        
        return $config;
    }
    
    public function getValidator()
    {
         return new TfValidator($this->html_purifier);
    }
    
}
