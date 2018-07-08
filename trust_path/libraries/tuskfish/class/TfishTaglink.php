<?php

/**
 * TfishTaglink class file.
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
 * Taglink object class.
 * 
 * Taglink objects are used to create relationships between content objects and tag objects, thereby
 * facilitating retrieval of related content. Taglinks are stored in their own table.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @property    int $id ID of this taglink object
 * @property    int $tag_id ID of the tag object
 * @property    string $content_type type of content object
 * @property    string $handler The handler for taglink objects
 */
class TfishTaglink extends TfishBaseObject
{

    /** @var array Array holding the values of taglink object properties, accessed via magic methods. */
    protected $id;
    protected $tag_id;
    protected $content_type;
    protected $content_id;
    
    public function setContentId(int $id)
    {
        $clean_id = (int) $id;
        if (TfishDataValidator::isInt($clean_id, 1)) {
            $this->content_id = $clean_id;
        }
    }
    
    public function setContentType(string $content_type)
    {
        $clean_content_type = TfishDataValidator::trimString($content_type);
        $content_handler = new TfishContentHandler();

        if ($content_handler->isSanctionedType($clean_content_type)) {
            $this->content_type = $clean_content_type;
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_TYPE, E_USER_ERROR);
        }
    }   
    
    public function setId(int $id)
    {
        $clean_id = (int) $id;
        if (TfishDataValidator::isInt($clean_id, 0)) {
            $this->id = $clean_id;
        }
    }

    public function setTagId(int $id)
    {
        $clean_id = (int) $id;
        
        if (TfishDataValidator::isInt($clean_id, 1)) {
            $this->tag_id = $clean_id;
        }
    }

}
