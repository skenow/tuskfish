<?php

/**
 * Admin controller script for the Contacts module.
 *
 * 
 * @copyright   Simon Wilkinson 2018+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     contacts
 */
// Enable strict type declaration.
declare(strict_types=1);

// Boot! Set file paths, preferences and connect to database.
require_once "../mainfile.php";
require_once TFISH_ADMIN_PATH . "tfAdminHeader.php";
require_once TFISH_MODULE_PATH . "contacts/tfContactsHeader.php";
require_once TFISH_MODULE_PATH . "content/tfContentHeader.php";

/**
 * Configuration
 * 
 * Set the ID of the collections that holds your activity / country tags. These are used to build
 * the activity / country select boxes.
 */
$activityCollection = 2;
$countryCollection = 3;
$targetFileName = 'contacts';
$tfTemplate->targetFileName = $targetFileName;
$contactHandler = $contactHandlerFactory->getHandler();
$tagHandler = $contentHandlerFactory->getHandler('tag');

/**
 * Validate input parameters here.
 **/
$cleanId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$cleanStart = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$cleanTag = isset($_GET['tagId']) ? (int) $_GET['tagId'] : 0;
$cleanCountry = isset($_GET['countryId']) ? (int) $_GET['countryId'] : 0;
$cleanYear = isset($_GET['year']) ? (int) $_GET['year'] : 0;
$cleanToken = isset($_POST['token']) ? $tfValidator->trimString($_POST['token']) : '';
$op = isset($_REQUEST['op']) ? $tfValidator->trimString($_REQUEST['op']) : false;

// Specify the admin theme and the template to be used to preview content (user side template).
if ($op === 'view') {
    $tfTemplate->setTheme('default');
} else {
    $tfTemplate->setTheme('admin');
}

// Permitted options.
$optionsWhitelist = array(
    'add',
    'confirmDelete',
    'delete',
    'edit',
    'submit',
    'update',
    'view',
    false);

if (in_array($op, $optionsWhitelist)) {
    
    // Cross-site request forgery check
    if (!in_array($op, array('confirmDelete', 'edit', 'view', false))) {
        TfSession::validateToken($cleanToken);
    }
    
    switch ($op) {
        case "add":
            $tfTemplate->pageTitle = TFISH_ADD_CONTACT;
            $tfTemplate->op = 'submit'; // Critical to launch correct form submission action.
            $tfTemplate->titles = $contactHandler->getTitles();
            
            // Build activities (tag) select box.
            $criteria = $tfCriteriaFactory->getCriteria();
            $criteria->add($tfCriteriaItemFactory->getItem('parent', $activityCollection));
            $taglist = $tagHandler->getListOfObjectTitles($criteria);
            $tfTemplate->tags = array(0 => '---') + $taglist;
            unset($criteria);
            
            // Build countries (tag) select box.
            $criteria = $tfCriteriaFactory->getCriteria();
            $criteria->add($tfCriteriaItemFactory->getItem('parent', $countryCollection));
            $countryList = $tagHandler->getListOfObjectTitles($criteria);
            asort($countryList);
            $tfTemplate->countryList = array(0 => '---') + $countryList;
            
            $contact = $contactFactory->getContact();            
            $tfTemplate->form = TFISH_CONTACTS_MODULE_FORM_PATH . "contactEntry.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            break;
        
        case "confirmDelete":
            if (isset($_REQUEST['id'])) {
                $cleanId = (int) $_REQUEST['id'];
                
                if ($tfValidator->isInt($cleanId, 1)) {
                    $tfTemplate->pageTitle = TFISH_CONFIRM_DELETE;
                    $tfTemplate->contact = $contactHandler->getContact($cleanId);
                    $tfTemplate->form = TFISH_CONTACTS_MODULE_FORM_PATH . "confirmDeleteContact.html";
                    $tfTemplate->tfMainContent = $tfTemplate->render('form');
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;
        
        case "delete":
            if (isset($_REQUEST['id'])) {
                $cleanId = (int) $_REQUEST['id'];
                $result = $contactHandler->delete($cleanId);
                
                if ($result) {
                    $tfCache->flushCache();
                    $tfTemplate->pageTitle = TFISH_SUCCESS;
                    $tfTemplate->alertClass = 'alert-success';
                    $tfTemplate->message = TFISH_OBJECT_WAS_DELETED;
                } else {
                    $tfTemplate->pageTitle = TFISH_FAILED;
                    $tfTemplate->alertClass = 'alert-danger';
                    $tfTemplate->message = TFISH_OBJECT_DELETION_FAILED;
                }
                
                $tfTemplate->backUrl = 'contacts.php';
                $tfTemplate->form = TFISH_CONTACTS_MODULE_FORM_PATH . "response.html";
                $tfTemplate->tfMainContent = $tfTemplate->render('form');
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;
        
        case "edit":
            if (isset($_REQUEST['id'])) {
                $cleanId = (int) $_REQUEST['id'];
                
                if ($tfValidator->isInt($cleanId, 1)) {
                    $criteria = $tfCriteriaFactory->getCriteria();
                    $criteria->add($tfCriteriaItemFactory->getItem('id', $cleanId));
                    $statement = $tfDatabase->select('contact', $criteria);
                    
                    if (!$statement) {
                        trigger_error(TFISH_ERROR_NO_SUCH_OBJECT, E_USER_NOTICE);
                        header("Location: contact.php");
                    }
                    
                    $row = $statement->fetch(PDO::FETCH_ASSOC);

                    $contact = $contactFactory->getContact();
                    $contact->loadPropertiesFromArray($row);
                    
                    // Build titles select box.
                    $tfTemplate->titles = $contactHandler->getTitles();

                    // Build activities (tag) select box.
                    $criteria = $tfCriteriaFactory->getCriteria();
                    $criteria->add($tfCriteriaItemFactory->getItem('parent', $activityCollection));
                    $tfTemplate->tags = array(0 => '---') + $tagHandler->getListOfObjectTitles($criteria);

                    // Build countries (tag) select box.
                    $criteria = $tfCriteriaFactory->getCriteria();
                    $criteria->add($tfCriteriaItemFactory->getItem('parent', $countryCollection));
                    $countryList = $tagHandler->getListOfObjectTitles($criteria);
                    asort($countryList);
                    $tfTemplate->countryList = array(0 => '---') + $countryList;

                    // Assign to template.
                    $tfTemplate->pageTitle = TFISH_CONTACTS_EDIT_CONTACT;
                    $tfTemplate->op = 'update'; // Critical to launch correct submission action.
                    $tfTemplate->action = TFISH_UPDATE;
                    $tfTemplate->contact = $contact;
                    $tfTemplate->form = TFISH_CONTACTS_MODULE_FORM_PATH . "contactEdit.html";
                    $tfTemplate->tfMainContent = $tfTemplate->render('form');
                } else {
                    trigger_error(TFISH_ERROR_NOT_INT, E_USER_ERROR);
                }
            } else {
                trigger_error(TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET, E_USER_ERROR);
            }
            break;
        
        case "submit":
            // Note that object does internal validation of data. This is why the data is passed
            // into an instance of the contact object before insertion to the database
            $contact = $contactFactory->getContact();
            $contact->loadPropertiesFromArray($_REQUEST);
            $result = $contactHandler->insert($contact);
 
            if ($result) {
                $tfCache->flushCache();
                $tfTemplate->pageTitle = TFISH_SUCCESS;
                $tfTemplate->alertClass = 'alert-success';
                $tfTemplate->message = TFISH_OBJECT_WAS_INSERTED;
            } else {
                $tfTemplate->title = TFISH_FAILED;
                $tfTemplate->alertClass = 'alert-danger';
                $tfTemplate->message = TFISH_OBJECT_INSERTION_FAILED;
            }
            
            $tfTemplate->backUrl = 'contacts.php';
            $tfTemplate->form = TFISH_CONTACTS_MODULE_FORM_PATH . "response.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            break;
        
        case "update":
            $contact = $contactFactory->getContact();
            $contact->loadPropertiesFromArray($_REQUEST); // Contact validates data internally.
            
            // As this object is being sent to storage, need to decode some entities that got
            // encoded for display.
            $fieldsToDecode = array('firstname', 'midname', 'lastname', 'job', 'businessUnit',
                'organisation', 'email', 'state', 'mobile');

            foreach ($fieldsToDecode as $field) {
                if (isset($contact->field)) {
                    $contact->$field = htmlspecialchars_decode($contact->field,
                            ENT_NOQUOTES);
                }
            }

            // Update the database row and display a response.
            $result = $contactHandler->update($contact);

            if ($result) {
                $tfCache->flushCache();
                $tfTemplate->pageTitle = TFISH_SUCCESS;
                $tfTemplate->alertClass = 'alert-success';
                $tfTemplate->message = TFISH_OBJECT_WAS_UPDATED;
                $tfTemplate->id = $contact->id;
            } else {
                $tfTemplate->pageTitle = TFISH_FAILED;
                $tfTemplate->alertClass = 'alert-danger';
                $tfTemplate->message = TFISH_OBJECT_UPDATE_FAILED;
            }

            $tfTemplate->backUrl = 'contacts.php';
            $tfTemplate->form = TFISH_CONTACTS_MODULE_FORM_PATH . "responseEditContact.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            break;
        
        case "view":
            if ($cleanId) {
                $contact = $contactHandler->getContact($cleanId);
                
                if (is_object($contact)) {
                    $tfTemplate->contact = $contact;
                    $tfTemplate->titles = $contactHandler->getTitles();
                    $criteria = $tfCriteriaFactory->getCriteria();
                    $criteria->add($tfCriteriaItemFactory->getItem('parent', $countryCollection));
                    $countryList = $tagHandler->getListOfObjectTitles($criteria);
                    asort($countryList);
                    $tfTemplate->countryList = array(0 => '---') + $countryList;
                    
                    // Get the related training course details.
                    if ($contact->tags) {
                        $activity = $tagHandler->getContact($contact->tags);
                        $tfTemplate->activity = $activity;
                    }

                    // Render template.
                    $tfTemplate->tfMainContent
                            = $tfTemplate->render($contact->template);
                } else {
                    $tfTemplate->tfMainContent = TFISH_ERROR_NO_SUCH_CONTENT;
                }
            }
            break;
        
        default:
            $criteria = $tfCriteriaFactory->getCriteria();
            $criteria->setOrder('lastname');
            $criteria->setOrdertype('ASC');
            $criteria->setSecondaryOrder('firstname');
            $criteria->setSecondaryOrdertype('ASC');
            $activities = array();
            $rows = array();
            
            /** Set legal parameter combos. **/
            // $cleanTag takes precedence over $cleanYear, as it is more specific.
            if ($cleanTag) {
                $cleanYear = 0;
                $criteria->add($tfCriteriaItemFactory->getItem('tags', $cleanTag));
            }
            
            // $cleanCountry can be used as complementary, independent filter to the others.
            if ($cleanCountry && !$cleanYear) {
                $criteria->add($tfCriteriaItemFactory->getItem('country', $cleanCountry));
            }
            
            // $cleanYear can be used as a complementary filter to $cleanCountry.
            if ($cleanYear) {
                $timezones = TfUtils::getListOfTimezones();
                $timezone = $timezones[$tfPreference->siteTimezone];
                $startYear = $cleanYear . '-01-01';
                $endYear = $cleanYear . '-12-31';
                
                // Get a list of activities that lie within this time period.
                $activityCriteria = $tfCriteriaFactory->getCriteria();            
                $activityCriteria->add($tfCriteriaItemFactory->getItem('type', 'TfishTag'));
                $activityCriteria->add($tfCriteriaItemFactory->getItem('parent', 11));
                $activityCriteria->add($tfCriteriaItemFactory->getItem('date', $startYear, '>'));
                $activityCriteria->add($tfCriteriaItemFactory->getItem('date', $endYear, '<'));
                $activities = array_keys($tagHandler->getListOfObjectTitles($activityCriteria));
                unset($activityCriteria);

                $i = 1;
                $count = count($activities);
            }
            
            // $cleanYear but no $cleanCountry.
            if ($cleanYear && !$cleanCountry) {

                foreach ($activities as $key => $value) {
                    if ($i < $count) {
                        $criteria->add($tfCriteriaItemFactory->getItem('tags', $value), "OR");
                    } else {
                        $criteria->add($tfCriteriaItemFactory->getItem('tags', $value));
                    }
                    
                    $i++;
                }
            }
            
            // $cleanCountry + $cleanYear requires custom query for this case.
            if ($cleanCountry && $cleanYear) {
                $sql = "SELECT `id`, `title`, `firstname`, `lastname`, `gender`, `job`,
                    `organisation`, `country`, `email` ";
                $sql .= "FROM `contact` WHERE (`country` = :country ";
                
                if (count($activities) > 0) {
                    $sql .= "AND `tags` IN (";                    
                    for ($i = 0; $i < count($activities); $i++) {
                        $sql .= ":tags" . $i . ",";
                    }
                    $sql = rtrim($sql, ',');
                    $sql .= ') ';
                }
                $sql .= ") ";
                
                // Order and order type.
                $sql .= "ORDER BY `lastname` ASC, `lastUpdated` DESC ";
                
                // Offset and limit.
                $sql .= " LIMIT :limit OFFSET :offset";
                
                // Prepare the statement and bind the placeholders to values.
                $statement = $tfDatabase->preparedStatement($sql);

                if ($statement) {
                    $statement->bindValue(":country", $cleanCountry, PDO::PARAM_INT);
                    for ($i = 0; $i < count($activities); $i++) {
                        $statement->bindValue(":tags" . $i, $activities[$i], PDO::PARAM_INT);
                    }
                    $statement->bindValue(":limit", $tfPreference->adminPagination, PDO::PARAM_INT);
                    $statement->bindValue(":offset", $cleanStart, PDO::PARAM_INT);
                    
                    $statement->execute();
                    
                    // Extract rows from PDOStatement object.
                    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                        $rows[] = $row;
                    }
                    unset($statement);

                }
            } else { // Base case - no filters. Show the last entered records.
                $criteria->setOffset($cleanStart);
                $criteria->setLimit($tfPreference->adminPagination);
                $criteria->setOrder('lastUpdated');
                $criteria->setOrdertype('DESC');
                $columns = array('id', 'title', 'firstname', 'lastname', 'gender', 'job',
                    'organisation', 'country', 'email');

                $result = $tfDatabase->select('contact', $criteria, $columns);

                if ($result) {
                    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    trigger_error(TFISH_ERROR_NO_RESULT, E_USER_ERROR);
                }
            }

            // Pagination control.
            // 1. Need to run a count seperately for the country + year case.
            // 2. Need to pass in extra parameters, which are country + year.
            if ($cleanCountry && $cleanYear) {
                $sql = "SELECT COUNT(*) FROM `contact` WHERE (`country` = :country ";
                if (count($activities) > 0) {
                    $sql .= "AND `tags` IN (";                    
                    for ($i = 0; $i < count($activities); $i++) {
                        $sql .= ":tags" . $i . ",";
                    }
                    $sql = rtrim($sql, ',');
                    $sql .= ') ';
                }
                $sql .= ") ";
                
                // Prepare the statement and bind the placeholders to values.
                $statement = $tfDatabase->preparedStatement($sql);
                $statement->bindValue(":country", $cleanCountry, PDO::PARAM_INT);
                for ($i = 0; $i < count($activities); $i++) {
                    $statement->bindValue(":tags" . $i, $activities[$i], PDO::PARAM_INT);
                }
                
                // Execute the statement and return the row count (integer) by retrieving the row.
                $statement->execute();
                $count = $statement->fetch(PDO::FETCH_NUM);
                $count = (int) reset($count);
            } else {
                $count = $tfDatabase->selectCount('contact', $criteria);
            }
            
            $extraParams = array();
            if ($cleanCountry) {
                $extraParams['countryId'] = $cleanCountry;
            }
            if ($cleanYear) {
                $extraParams['year'] = $cleanYear;
            }
            
            // Prepare pagination control.
            $pagination = new TfPaginationControl($tfValidator, $tfPreference);
            $pagination->setCount($count);
            $pagination->setStart($cleanStart);
            $pagination->setLimit($tfPreference->adminPagination);
            $pagination->setUrl('contacts');
            $pagination->setTag($cleanTag);
            $pagination->setExtraParams($extraParams);
            $tfTemplate->pagination = $pagination->getPaginationControl();
            unset($criteria);
            
            // Country select filter.
            $criteria = $tfCriteriaFactory->getCriteria();
            $criteria->add($tfCriteriaItemFactory->getItem('parent', $countryCollection));
            $countryList = $tagHandler->getListOfObjectTitles($criteria);
            asort($countryList);
            $countrySelect = $tagHandler->getArbitraryTagSelectBox($cleanCountry, $countryList,
                    'countryId', '-- All countries --');
            unset($criteria);
            
            // Activity select filter.
            $criteria = $tfCriteriaFactory->getCriteria();
            $criteria->add($tfCriteriaItemFactory->getItem('parent', $activityCollection));
            $activityList = $tagHandler->getListOfObjectTitles($criteria);
            $activitySelect = $tagHandler->getArbitraryTagSelectBox($cleanTag, $activityList,
                    'tagId', '-- All activities --');
            
            // Year select filter. Retrieve dates for activities (using existing $criteria),
            // compute years. Remove duplicates and sort chronologically.
            $activityObjects = $tagHandler->getObjects($criteria);
            unset($criteria);
            $years = array();
            
            foreach ($activityObjects as $activity) {
                $years[] = date("Y", strtotime($activity->date));
            }
            $years = array_unique($years);
            $yearSelect = '<select class="form-control custom-select" name="year" id="year" '
                    . 'onchange="this.form.submit()">';
            if (!$cleanYear) {
                $yearSelect .= '<option value="0" selected>-- All years --</option>';
            } else {
                $yearSelect .= '<option value="0">-- All years --</option>';
            }
            foreach ($years as $year) {
                $yearSelect .= ($cleanYear == $year) ? '<option value="' . $year . '" selected>'
                        . $year . '</option>' : '<option value="' . $year . '">' . $year . '</option>';
            }
            $yearSelect .= '</select>';
            $tfTemplate->selectAction = 'contacts.php';
            $tfTemplate->countrySelect = $countrySelect;
            $tfTemplate->activitySelect = $activitySelect;
            $tfTemplate->yearSelect = $yearSelect;
            $tfTemplate->selectFiltersForm = $tfTemplate->render('contactFilters');
            $tfTemplate->countryList = $countryList;

            // Assign to template.
            $tfTemplate->pageTitle = TFISH_CONTACTS_MODULE;
            $tfTemplate->rows = $rows;
            $tfTemplate->titles = $contactHandler->getTitles();
            $tfTemplate->form = TFISH_CONTACTS_MODULE_FORM_PATH . "contactTable.html";
            $tfTemplate->tfMainContent = $tfTemplate->render('form');
            break;
    }
} else {
    trigger_error(TFISH_ERROR_ILLEGAL_VALUE, E_USER_ERROR);
    exit;
}

/**
 * Override page template here (otherwise default site metadata will display).
 */
// $tfMetadata->setTitle('');
// $tfMetadata->setDescription('');
// $tfMetadata->setAuthor('');
// $tfMetadata->setCopyright('');
// $tfMetadata->setGenerator('');
// $tfMetadata->setSeo('');
// $tfMetadata->setRobots('noindex,nofollow');

// Include page template and flush buffer
require_once TFISH_PATH . "tfFooter.php";