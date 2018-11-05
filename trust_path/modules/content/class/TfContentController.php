<?php
/**
 * TfContentController class file.
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
 * Controls basic content object operations (add, edit, delete, toggle and update). It encapsulates
 * the admin controller script functionality for these operations.
 * 
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 * @var         TfValidator $validator Instance of the Tuskfish data validator class.
 * @var         TfDatabase $db Instance of the Tuskfish database class.
 * @var         TfCriteriaFactory $criteriaFactory Instance of the Tuskfish criteria factory class.
 * @var         TfContentFactory $contentFactory Instance of the Tuskfish content handler factory class.
 * @var         TfTemplate $template Instance of the Tuskfish template object class.
 * @var         TfPreference $preference Instance of the Tuskfish site preferences class.
 * @var         TfCache $cache Instance of the Tuskfish site cache class.
 */
class TfContentController
{
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $contentFactory;
    protected $template;
    protected $preference;
    protected $cache;
    
    /**
     * Constructor.
     * 
     * @param TfValidator $validator Instance of the validator class.
     * @param TfDatabase $db Instance of the database class.
     * @param TfCriteriaFactory $criteriaFactory Instance of the criteria factory class.
     * @param TfContentFactory $contentFactory Instance of the content handler class.
     * @param TfTemplate $template Instance of the template class.
     * @param TfPreference $preference Instance of the site preferences class.
     * @param TfCache $cache Instance of the cache class.
     */
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfContentFactory $contentFactory,
            TfTemplate $template, TfPreference $preference, TfCache $cache)
    {
        if (is_a($validator, 'TfValidator')) {
            $this->validator = $validator; 
        } else {
            trigger_error(TFISH_ERROR_NOT_VALIDATOR, E_USER_ERROR);
        }
        
        if (is_a($db, 'TfDatabase')) {
            $this->db = $db; 
        } else {
            trigger_error(TFISH_ERROR_NOT_DATABASE, E_USER_ERROR);
        }
        
        if (is_a($criteriaFactory, 'TfCriteriaFactory')) {
            $this->criteriaFactory = $criteriaFactory; 
        } else {
            trigger_error(TFISH_ERROR_NOT_CRITERIA_FACTORY, E_USER_ERROR);
        }
        
        if (is_a($contentFactory, 'TfContentFactory')) {
            $this->contentFactory = $contentFactory;
        }  else {
            trigger_error(TFISH_ERROR_NOT_CONTENT_FACTORY, E_USER_ERROR);
        }
        
        if (is_a($template, 'TfTemplate')) {
            $this->template = $template;
        }  else {
            trigger_error(TFISH_ERROR_NOT_TEMPLATE_OBJECT, E_USER_ERROR);
        }
        
        if (is_a($preference, 'TfPreference')) {
            $this->preference = $preference;
        }  else {
            trigger_error(TFISH_ERROR_NOT_PREFERENCE, E_USER_ERROR);
        }
        
        if (is_a($cache, 'TfCache')) {
            $this->cache = $cache;
        }  else {
            trigger_error(TFISH_ERROR_NOT_CACHE, E_USER_ERROR);
        }
    }
    
    /**
     * Add a content object to the site.
     */
    public function addContent()
    {
        $content = new TfContentObject($this->validator);
        $contentHandler = $this->contentFactory->getContentHandler('content');
        $collectionHandler = $this->contentFactory->getContentHandler('collection');
        
        $this->template->pageTitle = TFISH_ADD_CONTENT;
        $this->template->op = 'submit'; // Critical to launch correct form submission action.
        $this->template->contentTypes = $contentHandler->getTypes();
        $this->template->rights = $content->getListOfRights();
        $this->template->languages = $this->preference->getListOfLanguages();
        $this->template->tags = $contentHandler->getTagList(false);
        
        // Make a parent tree select box options.
        $collections = $collectionHandler->getObjects();
        $parentTree = new TfTree($collections, 'id', 'parent');
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
    
    /**
     * Request confirmation to delete a content object from the site.
     * 
     * Actual deletion is carried out in the deleteContent() option, not here. This is just the
     * sanity check.
     * 
     * @param int $id ID of the content object to confirm deletion of.
     */
    public function confirmDelete(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        $contentHandler = $this->contentFactory->getContentHandler('content');
        $this->template->pageTitle = TFISH_CONFIRM_DELETE;
        $this->template->content = $contentHandler->getObject($cleanId);
        $this->template->form = TFISH_CONTENT_MODULE_FORM_PATH . "confirmDelete.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    /**
     * Delete a content object from the database.
     * 
     * @param int $id Id of the content object to be deleted.
     */
    public function deleteContent(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
                
        $contentHandler = $this->contentFactory->getContentHandler('content');
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
    
    /**
     * Edit a content object.
     * 
     * Opens a data entry form pre-filled with the object's data, for editing.
     * 
     * @param int $id ID of the content object to be edited.
     */
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
        $contentHandler = $this->contentFactory->getContentHandler('content');
        $content = $contentHandler->convertRowToObject($row);
        
        // Make a parent tree select box options.
        $collectionHandler = $this->contentFactory->getContentHandler('collection');
        $collections = $collectionHandler->getObjects();
        $parentTree = new TfTree($collections, 'id', 'parent');
        
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
    
    /**
     * Submit a new content object for insertion into the database.
     * 
     * @param array $formData Data from the content data entry form.
     */
    public function submitContent(array $formData)
    {
        if (!isset($formData['type'])) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }
        
        $cleanType = $this->validator->trimString($formData['type']);
        $contentHandler = $this->contentFactory->getContentHandler('content');
        $typeWhitelist = $contentHandler->getTypes();
        
        if (!array_key_exists($cleanType, $typeWhitelist)) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }
        
        $content = $this->contentFactory->getContentObject($cleanType);
        $content->loadPropertiesFromArray($_REQUEST, true);
        $content->loadImage($content->getPropertyWhitelist());        
        $content->loadMedia($content->getPropertyWhitelist());
        
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
    
    /**
     * Toggle a content object online or offline.
     * 
     * Offline content objects are not available on the front end of the site and are not returned
     * in search results or in RSS feeds. Exception is tags, which are always online. Marking a tag
     * as offline will remove it from the front end tag select box, to keep it uncluttered.
     * 
     * @param int $id ID of the content object to toggle on/offline.
     */
    public function toggleOnlineStatus(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 0)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        $contentHandler = $this->contentFactory->getContentHandler('content');
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
    
    /**
     * Update an existing content object.
     * 
     * @param array $formData Data from the edit content form.
     */
    public function updateContent(array $formData)
    {
        if (!isset($formData['type'])) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }
        
        $type = $this->validator->trimString($formData['type']);
        $contentHandler = $this->contentFactory->getContentHandler('content');
        $typeWhitelist = $contentHandler->getTypes();
        
        if (!array_key_exists($type, $typeWhitelist)) {
            trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            exit;
        }
        
        $content = $this->contentFactory->getContentObject($type);
        $content->loadPropertiesFromArray($formData, true);
        $content->loadImage($content->getPropertyWhitelist());        
        $content->loadMedia($content->getPropertyWhitelist());
        
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
