<?php

/**
 * TfContentObjectController class file.
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
 * Controls basic content object operations (add, edit, delete and update). It encapsulates the
 * admin controller script functionality.
 * 
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @uses        TfContentTypes Whitelist of sanctioned TfishContentObject subclasses.
 * @var         TfValidator $validator Instance of the Tuskfish data validator class.
 * @var         TfDatabase $db Instance of the Tuskfish database class.
 * @var         TfCriteriaFactory $criteriaFactory Instance of the Tuskfish criteria factory class.
 * @var         TfFileHandler $fileHandler Instance of the Tuskfish file handler class.
 * @var         TfTaglinkHandler $taglinkHandler Instance of the Tuskfish taglink handler class.
 */

class TfContentObjectController
{
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $contentHandlerFactory;
    protected $template;
    protected $preference;
    protected $cache;
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfContentHandlerFactory $contentHandlerFactory,
            TfTemplate $template, TfPreference $preference, TfCache $cache)
    {
        $this->validator = $validator;
        $this->db = $db;
        $this->criteriaFactory = $criteriaFactory;
        $this->contentHandlerFactory = $contentHandlerFactory;
        $this->template = $template;
        $this->preference = $preference;
        $this->cache = $cache;
    }
    
    public function addContent()
    {
        $content = new TfContentObject($this->validator);
        $contentHandler = $this->contentHandlerFactory->getHandler('content');
        $collectionHandler = $this->contentHandlerFactory->getHandler('collection');
        
        $this->template->pageTitle = TFISH_ADD_CONTENT;
        $this->template->op = 'submit'; // Critical to launch correct form submission action.
        $this->template->contentTypes = $contentHandler->getTypes();
        $this->template->rights = $content->getListOfRights();
        $this->template->languages = $this->preference->getListOfLanguages();
        $this->template->tags = $contentHandler->getTagList(false);

        // Make a parent tree select box options.
        $collections = $collectionHandler->getObjects();
        $parentTree = new TfAngryTree($collections, 'id', 'parent');
        $this->template->parentSelectOptions = $parentTree->makeParentSelectBox();

        $this->template->allowedProperties = $content->getPropertyWhitelist();
        $this->template->zeroedProperties = array(
            'image' => array('image'),
            'tags' => array(
                'creator',
                'language',
                'rights',
                'publisher',
                'tags')
        );
        $this->template->form = TFISH_CONTENT_MODULE_FORM_PATH . "dataEntry.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function confirmDelete(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        $contentHandler = $this->contentHandlerFactory->getHandler('content');

        $this->template->pageTitle = TFISH_CONFIRM_DELETE;
        $this->template->content = $contentHandler->getObject($cleanId);
        $this->template->form = TFISH_CONTENT_MODULE_FORM_PATH . "confirmDelete.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function deleteContent(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
                
        $contentHandler = $this->contentHandlerFactory->getHandler('content');
        $result = $contentHandler->delete($cleanId);
                
        if ($result) {
            $this->cache->flushCache();
            $this->template->pageTitle = TFISH_SUCCESS;
            $this->template->alertClass = 'alert-success';
            $this->template->message = TFISH_OBJECT_WAS_DELETED;
        } else {
            $this->template->pageTitle = TFISH_FAILED;
            $this->template->alertClass = 'alert-danger';
            $this->template->message = TFISH_OBJECT_DELETION_FAILED;
        }

        $this->template->backUrl = 'admin.php';
        $this->template->form = TFISH_FORM_PATH . "response.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function editContent(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
            
        $criteria = $this->criteriaFactory->getCriteria();
        $criteria->add($this->criteriaFactory->getItem('id', $cleanId));
        $statement = $this->db->select('content', $criteria);

        if (!$statement) {
            trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_NOTICE);
            header("Location: admin.php");
        }

        // Build the content object.
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $contentHandler = $this->contentHandlerFactory->getHandler('content');
        $content = $contentHandler->convertRowToObject($row, false);

        // Make a parent tree select box options.
        $collectionHandler = $this->contentHandlerFactory->getHandler('collection');
        $collections = $collectionHandler->getObjects();
        $parentTree = new TfAngryTree($collections, 'id', 'parent');            

        // Assign to template.
        $this->template->pageTitle = TFISH_EDIT_CONTENT;
        $this->template->op = 'update'; // Critical to launch correct submission action.
        $this->template->action = TFISH_UPDATE;
        $this->template->content = $content;
        $this->template->contentTypes = $contentHandler->getTypes();
        $this->template->rights = $content->getListOfRights();
        $this->template->languages = $this->preference->getListOfLanguages();
        $this->template->tags = $contentHandler->getTagList(false);
        $this->template->parentSelectOptions = 
                $parentTree->makeParentSelectBox((int) $row['parent']);
        $this->template->form = TFISH_CONTENT_MODULE_FORM_PATH . "dataEdit.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function submitContent(array $formData)
    {
        if (!isset($formData['type'])) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }

        $cleanType = $this->validator->trimString($formData['type']);
        $contentHandler = $this->contentHandlerFactory->getHandler('content');
        $typeWhitelist = $contentHandler->getTypes();

        if (!array_key_exists($cleanType, $typeWhitelist)) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }

        $content = new $cleanType($this->validator);
        $content->loadPropertiesFromArray($_REQUEST);

        $result = $contentHandler->insert($content);

        if ($result) {
            $this->cache->flushCache();
            $this->template->pageTitle = TFISH_SUCCESS;
            $this->template->alertClass = 'alert-success';
            $this->template->message = TFISH_OBJECT_WAS_INSERTED;
        } else {
            $this->template->title = TFISH_FAILED;
            $this->template->alertClass = 'alert-danger';
            $this->template->message = TFISH_OBJECT_INSERTION_FAILED;
        }

        $this->template->backUrl = 'admin.php';
        $this->template->form = TFISH_FORM_PATH . "response.html";
        $this->template->tfMainContent = $this->template->render('form');
    }

    public function toggleOnlineStatus(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 0)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        $contentHandler = $this->contentHandlerFactory->getHandler('content');
        $result = $contentHandler->toggleOnlineStatus($cleanId);

        if ($result) {
            $this->cache->flushCache();
            $this->template->pageTitle = TFISH_SUCCESS;
            $this->template->alertClass = 'alert-success';
            $this->template->message = TFISH_OBJECT_WAS_UPDATED;
        } else {
            $this->template->pageTitle = TFISH_FAILED;
            $this->template->alertClass = 'alert-danger';
            $this->template->message = TFISH_OBJECT_UPDATE_FAILED;
        }

        $this->template->backUrl = 'admin.php';
        $this->template->form = TFISH_FORM_PATH . "response.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function updateContent(array $formData)
    {
        if (!isset($formData['type'])) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }

        $type = $this->validator->trimString($formData['type']);
        $contentHandler = $this->contentHandlerFactory->getHandler('content');
        $typeWhitelist = $contentHandler->getTypes();

        if (!array_key_exists($type, $typeWhitelist)) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }

        $content = new $type($this->validator);
        $content->loadPropertiesFromArray($formData);

        // As this object is being sent to storage, need to decode some entities that were encoded
        // for display.
        $fieldsToDecode = array('title', 'creator', 'publisher', 'caption');

        foreach ($fieldsToDecode as $field) {
            if (isset($content->field)) {
                $content->$field = htmlspecialchars_decode($content->field, ENT_NOQUOTES);
            }
        }

        // Properties that are used within attributes must have quotes encoded.
        $fieldsToDecode = array('metaTitle', 'seo', 'metaDescription');

        foreach ($fieldsToDecode as $field) {
            if (isset($content->field)) {
                $content->$field = htmlspecialchars_decode($content->field, ENT_QUOTES);
            }
        }

        // Update the database row and display a response.
        $result = $contentHandler->update($content);

        if ($result) {
            $this->cache->flushCache();
            $this->template->pageTitle = TFISH_SUCCESS;
            $this->template->alertClass = 'alert-success';
            $this->template->message = TFISH_OBJECT_WAS_UPDATED;
            $this->template->id = $content->id;
        } else {
            $this->template->pageTitle = TFISH_FAILED;
            $this->template->alertClass = 'alert-danger';
            $this->template->message = TFISH_OBJECT_UPDATE_FAILED;
        }

        $this->template->backUrl = 'admin.php';
        $this->template->form = TFISH_CONTENT_MODULE_FORM_PATH . "responseEdit.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
   
}
