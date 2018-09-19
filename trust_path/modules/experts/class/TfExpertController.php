<?php

/**
 * Expert controller class file.
 * 
 * @copyright   Simon Wilkinson 2018+(https://isengard.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Your name <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     experts
 */

// Enable strict type declaration.
declare(strict_types=1);

if (!defined("TFISH_ROOT_PATH")) die("TFISH_ERROR_ROOT_PATH_NOT_DEFINED");

/**
 * Controls basic expert object operations (add, edit, delete, toggle and update). It encapsulates
 * the admin controller script functionality.
 *
 * @copyright   Simon Wilkinson 2018+ (https://yoursite.com)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     experts
 */

class TfExpertController
{
    
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $expertHandler;
    protected $cache;
    protected $template;
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfExpertHandler $expertHandler, TfCache $cache,
            TfTemplate $template)
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
        
        if (is_a($expertHandler, 'TfExpertHandler')) {
            $this->expertHandler = $expertHandler;
        } else {
            trigger_error(TFISH_ERROR_NOT_EXPERT_HANDLER, E_USER_ERROR);
        }
        
        if (is_a($cache, 'TfCache')) {
            $this->cache = $cache;
        } else {
            trigger_error(TFISH_ERROR_NOT_CACHE, E_USER_ERROR);
        }
        
        if (is_a($template, 'TfTemplate')) {
            $this->template = $template;
        } else {
            trigger_error(TFISH_ERROR_NOT_TEMPLATE_OBJECT, E_USER_ERROR);
        }
    }
    
    public function addExpert(TfContentHandler $contentHandler)
    {
        $this->template->pageTitle = TFISH_EXPERTS;
        $this->template->salutationList = $this->expertHandler->getSalutationList();
        $this->template->tagList = $contentHandler->getTagList(false);
        $this->template->countryList = $this->expertHandler->getCountryList();
        $this->template->form = TFISH_EXPERTS_MODULE_FORM_PATH . "expertEntry.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function confirmDelete(int $id)
    {
        $cleanId = (int) $id;

        if ($this->validator->isInt($cleanId, 1)) {
            $this->template->pageTitle = TFISH_CONFIRM_DELETE;
            $this->template->expert = $this->expertHandler->getObject($cleanId);
            $this->template->form = TFISH_EXPERTS_MODULE_FORM_PATH . "confirmDelete.html";
            $this->template->tfMainContent = $this->template->render('form');
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function deleteExpert(int $id)
    {
        $cleanId = (int) $id;
        
        $result = $this->expertHandler->delete($cleanId);

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

        $this->template->backUrl = 'experts.php';
        $this->template->form = TFISH_FORM_PATH . "response.html";
        $this->template->tfMainContent = $this->template->render('form');        
    }
    
    public function editExpert(int $id)
    {        
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($cleanId, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }

        $criteria = $this->criteriaFactory->getCriteria();
        $criteria->add($this->criteriaFactory->getItem('id', $cleanId));
        $statement = $this->db->select('expert', $criteria);

        if (!$statement) {
            trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_NOTICE);
            header("Location: experts.php");
        }
        
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $expert = $this->expertHandler->convertRowToObject($row, false);

        // Assign to template.
        $this->template->pageTitle = TFISH_EXPERTS_EDIT;
        $this->template->op = 'update'; // Critical to launch correct submission action.
        $this->template->action = TFISH_UPDATE;
        $this->template->expert = $expert;
        $this->template->form = TFISH_EXPERTS_MODULE_FORM_PATH . "expertEdit.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function submitExpert(array $formData)
    {
        $expert = new TfExpert($this->validator);
        $expert->loadPropertiesFromArray($formData);
        
        // Insert the object
        $result = $this->expertHandler->insert($expert);

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

        $this->template->backUrl = 'experts.php';
        $this->template->form = TFISH_FORM_PATH . "response.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function toggleOnlineStatus(int $id)
    {
        $cleanId = (int) $id;
        
        if (!$this->validator->isInt($id, 1)) {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
        
        $result = $this->expertHandler->toggleOnlineStatus($cleanId);

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

        $this->template->backUrl = 'experts.php';
        $this->template->form = TFISH_FORM_PATH . "response.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function updateExpert(array $formData)
    {
        $expert = new TfExpert($this->validator);
        $expert->loadPropertiesFromArray($formData);

        // As this object is being sent to storage, need to decode entities that got encoded for
        // display.
        if (isset($expert->title)) {
            $expert->title = htmlspecialchars_decode($expert->title, ENT_NOQUOTES);
        }

        // Properties that are used within attributes must have quotes encoded.
        $fieldsToDecode = array('metaTitle', 'seo', 'metaDescription');

        foreach ($fieldsToDecode as $field) {
            if (isset($expert->field)) {
                $expert->$field = htmlspecialchars_decode($expert->field, ENT_QUOTES);
            }
        }

        // Update the database row and display a response.
        $result = $this->expertHandler->update($expert);

        if ($result) {
            $this->cache->flushCache();
            $this->template->pageTitle = TFISH_SUCCESS;
            $this->template->alertClass = 'alert-success';
            $this->template->message = TFISH_OBJECT_WAS_UPDATED;
            $this->template->id = $expert->id;
        } else {
            $this->template->pageTitle = TFISH_FAILED;
            $this->template->alertClass = 'alert-danger';
            $this->template->message = TFISH_OBJECT_UPDATE_FAILED;
        }

        $this->template->backUrl = 'experts.php';
        $this->template->form = TFISH_EXPERTS_MODULE_FORM_PATH . "responseExpertEdit.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
}
