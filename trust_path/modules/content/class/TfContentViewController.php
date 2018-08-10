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
 * Prepares single and multiple objects for display.
 * 
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.0
 * @package     content
 */
class TfContentViewController
{
    
    use TfMagicMethods;
    
    // Utilities.
    protected $validator;
    protected $db;
    protected $criteriaFactory;
    protected $contentHandlerFactory;
    protected $paginationFactory;
    protected $template;
    protected $metadata;
    protected $preference;
    
    // Configuration.
    protected $id;
    protected $start;
    protected $tag;
    protected $type;
    protected $targetFileName;
    protected $online;
    
    public function __construct(TfValidator $validator, TfDatabase $db,
            TfCriteriaFactory $criteriaFactory, TfContentHandlerFactory $contentHandlerFactory,
            TfPaginationControlFactory $paginationFactory, TfTemplate $template,
            TfMetadata $metadata, TfPreference $preference)
    {
        $this->validator = $validator;
        $this->db = $db;
        $this->criteriaFactory = $criteriaFactory;
        $this->contentHandlerFactory = $contentHandlerFactory;
        $this->paginationFactory = $paginationFactory;
        $this->template = $template;
        $this->metadata = $metadata;
        $this->preference = $preference;
    }
    
    public function displaySingleObject()
    {        
        $contentHandler = $this->contentHandlerFactory->getHandler('content');
        $content = $contentHandler->getObject($this->id);
        
        if (!is_object($content)) {
            $this->template->tfMainContent = TFISH_ERROR_NO_SUCH_CONTENT;
            return;
        }

        $this->template->content = $content;

        // Prepare meta information for display.
        $contentInfo = array();

        if ($content->creator) $contentInfo[] = $content->escapeForXss('creator');

        if ($content->date) $contentInfo[] = $content->escapeForXss('date');

        if ($content->counter) {
            switch ($content->type) {
                case "TfDownload": // Display 'downloads' after the counter.
                    $contentInfo[] = $content->escapeForXss('counter') . ' '
                        . TFISH_DOWNLOADS;
                    break;

                // Display 'downloads' after the counter if there is an attached media
                // file; otherwise 'views'.
                case "TfCollection":
                    if ($content->media) {
                        $contentInfo[] = $content->escapeForXss('counter') . ' '
                                . TFISH_DOWNLOADS;
                        break;
                    }
                    break;

                default: // Display 'views' after the counter.
                    $contentInfo[] = $content->escapeForXss('counter') . ' ' . TFISH_VIEWS;
            }
        }

        if ($content->format)
            $contentInfo[] = '.' . $content->escapeForXss('format');

        if ($content->fileSize)
            $contentInfo[] = $content->escapeForXss('fileSize');

        // For a content type-specific page use $content->tags, $content->template.
        if ($content->tags) {
            $tags = $contentHandler->makeTagLinks($content->tags);
            $tags = TFISH_TAGS . ': ' . implode(', ', $tags);
            $contentInfo[] = $tags;
        }

        $this->template->contentInfo = implode(' | ', $contentInfo);

        if ($content->metaTitle) $this->metadata->setTitle($content->metaTitle);

        if ($content->metaDescription) $this->metadata->setDescription($content->metaDescription);

        // Check if has a parental object; if so display a thumbnail and teaser / link.
        if (!empty($content->parent)) {
            $parent = $contentHandler->getObject($content->parent);

            if (is_object($parent) && $parent->online) {
                $this->template->parent = $parent;
            }
        }

        // Initialise criteria object.
        $criteria = $this->criteriaFactory->getCriteria();
        $criteria->setOrder('date');
        $criteria->setOrderType('DESC');
        $criteria->setSecondaryOrder('submissionTime');
        $criteria->setSecondaryOrderType('DESC');

        // If object is a collection check if has child objects; if so display
        // thumbnails and teasers / links.
        if ($content->type === 'TfCollection') {
            $criteria->add($this->criteriaFactory->getItem('parent', $content->id));
            $criteria->add($this->criteriaFactory->getItem('online', 1));

            if ($this->start) $criteria->setOffset($this->start);

            $criteria->setLimit($this->preference->userPagination);
        }

        // If object is a tag, then a different method is required to call the related
        // content.
        if ($content->type === 'TfTag') {
            if ($this->start) $criteria->setOffset($this->start);

            $criteria->setLimit($this->preference->userPagination);
            $criteria->setTag(array($content->id));
            $criteria->add($this->criteriaFactory->getItem('online', 1));
        }

        // Prepare pagination control.
        if ($content->type === 'TfCollection' || $content->type === 'TfTag') {
            $pagination = $this->paginationFactory->getPaginationControl();
            $pagination->setUrl($this->targetFileName);
            $pagination->setCount($contentHandler->getCount($criteria));
            $pagination->setLimit($this->preference->userPagination);
            $pagination->setStart($this->start);
            $pagination->setTag(0);
            $pagination->setExtraParams(array('id' => $this->id));
            $this->template->collectionPagination = $pagination->renderPaginationControl();

            // Retrieve content objects and assign to template.
            $firstChildren = $contentHandler->getObjects($criteria);

            if (!empty($firstChildren)) {
                $this->template->firstChildren = $firstChildren;
            }
        }

        // Render template.
        $this->template->tfMainContent = $this->template->render($content->template);
    }
    
    public function displayMultipleObjects()
    {
        $criteria = $this->criteriaFactory->getCriteria();
        $contentHandler = $this->contentHandlerFactory->getHandler('content');

        // Select box filter input.
        if ($this->tag) $criteria->setTag(array($this->tag));

        if (isset($this->online) && $this->validator->isInt($this->online, 0, 1)) {
            $criteria->add($this->criteriaFactory->getItem('online', $this->online));
        }

        if ($this->type) {
            if (array_key_exists($this->type, $contentHandler->getTypes())) {
                $criteria->add($this->criteriaFactory->getItem('type', $this->type));
            } else {
                trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
            }
        }

        // Other criteria.
        $criteria->setOffset($this->start);
        $criteria->setLimit($this->preference->adminPagination);
        $criteria->setOrder('submissionTime');
        $criteria->setOrderType('DESC');
        $columns = array('id', 'type', 'title', 'submissionTime', 'counter', 'online');
        $result = $this->db->select('content', $criteria, $columns);

        if ($result) {
            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        } else {
            trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
        }

        foreach ($rows as &$row) {
            $row['submissionTime']
                    = date($this->preference->dateFormat, (int) $row['submissionTime']);
        }

        $typelist = $contentHandler->getTypes();

        // Pagination control.
        $extraParams = array();
        
        if (isset($this->online)) {
            $extraParams['online'] = $this->online;
        }
        
        if (isset($this->type) && !empty($this->type)) {
            $extraParams['type'] = $this->type;
        }

        $paginationControl = $this->paginationFactory->getPaginationControl();
        $paginationControl->setUrl($this->targetFileName);
        $paginationControl->setCount($this->db->selectCount('content', $criteria));
        $paginationControl->setLimit($this->preference->adminPagination);
        $paginationControl->setStart($this->start);
        $paginationControl->setTag($this->tag);
        $paginationControl->setExtraParams($extraParams);
        $this->template->pagination = $paginationControl->renderPaginationControl();

        // Prepare select filters.
        $tagHandler = $this->contentHandlerFactory->getHandler('tag');
        $tagSelectBox = $tagHandler->getTagSelectBox($this->tag);
        $typeSelectBox = $contentHandler->getTypeSelectBox($this->type);
        $onlineSelectBox = $contentHandler->getOnlineSelectBox($this->online);
        $this->template->selectAction = 'admin.php';
        $this->template->tagSelect = $tagSelectBox;
        $this->template->typeSelect = $typeSelectBox;
        $this->template->onlineSelect = $onlineSelectBox;
        $this->template->selectFiltersForm = $this->template->render('adminSelectFilters');

        // Assign to template.
        $this->template->pageTitle = TFISH_CURRENT_CONTENT;
        $this->template->rows = $rows;
        $this->template->typelist = $contentHandler->getTypes();
        $this->template->form = TFISH_CONTENT_MODULE_FORM_PATH . "contentTable.html";
        $this->template->tfMainContent = $this->template->render('form');
    }
    
    public function setId(int $id)
    {
        $cleanId = (int) $id;
        
        if ($this->validator->isInt($cleanId, 0))
        {
            $this->id = $cleanId;
        } else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setStart(int $start)
    {
        $cleanStart = (int) $start;
        
        if ($this->validator->isInt($cleanStart, 0)) {
            $this->start = $cleanStart;
        }  else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setTag(int $tag)
    {
        $cleanTag = (int) $tag;
        
        if ($this->validator->isInt($cleanTag, 0)) {
            $this->tag = $cleanTag;
        }  else {
            trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
        }
    }
    
    public function setTargetFileName(string $fileName)
    {
        $cleanTargetFileName = $this->validator->trimString($fileName);
        
        if (empty($fileName) || $this->validator->isAlnumUnderscore($fileName)) {
            $this->targetFileName = $cleanTargetFileName;
        } else {
            trigger_error(TFISH_ERROR_NOT_ALNUMUNDER, E_USER_ERROR);
        }
    }
    
    public function setType(string $type)
    {
        $cleanType = $this->validator->trimString($type);
        $contentHandler = $this->contentHandlerFactory->getHandler('content');
        $typeWhitelist = $contentHandler->getTypes();
        
        if (empty($cleanType) || array_key_exists($cleanType, $typeWhitelist)) {
            $this->type = $cleanType;
        } else {
            trigger_error(TFISH_ERROR_ILLEGAL_TYPE, E_USER_ERROR);
        }
    }
    
    public function setOnline(int $online = null)
    {
        if (isset($online)) {
            $cleanOnline = (int) $online;
        
            if ($this->validator->isInt($cleanOnline, 0, 2)) {
                $this->online = $cleanOnline;
            }
        }
    }
    
}
