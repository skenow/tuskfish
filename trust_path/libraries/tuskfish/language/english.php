<?php

/**
* Tuskfish core language constants (English)
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/
define("TFISH_WELCOME", "Welcome to Tuskfish CMS");

// admin/login.php
define("TFISH_LOGIN", "Login");
define("TFISH_LOGIN_DESCRIPTION", "Login to the administrative interface of Tuskfish.");
define("TFISH_LOGOUT", "Logout");
define("TFISH_PASSWORD", "Password");
define("TFISH_EMAIL", "Email");
define("TFISH_ACTION", "Action");
define("TFISH_YOU_ARE_ALREADY_LOGGED_IN", "You are already logged in.");

// Admin.
define("TFISH_ADMIN", "Admin");
define("TFISH_CURRENT_CONTENT", "Current content");
define("TFISH_ADD_CONTENT", "Add content");
define("TFISH_EDIT_CONTENT", "Edit content");
define("TFISH_SELECT_TAGS", "--- Select tags ---");
define("TFISH_META_TAGS", "Meta tags");
define("TFISH_DELETE", "Delete");
define("TFISH_EDIT", "Edit");

// Preferences.
define("TFISH_PREFERENCES", "Preferences");
define("TFISH_PREFERENCE", "Preference");
define("TFISH_PREFERENCE_EDIT_PREFERENCES", "Edit preferences");
define("TFISH_PREFERENCE_VALUE", "Value");
define("TFISH_PREFERENCE_SITE_NAME", "Site name");
define("TFISH_PREFERENCE_SITE_EMAIL", "Site email");
define("TFISH_PREFERENCE_CLOSE_SITE", "Close site");
define("TFISH_PREFERENCE_SERVER_TIMEZONE", "Server timezone");
define("TFISH_PREFERENCE_SITE_TIMEZONE", "Site timezone");
define("TFISH_PREFERENCE_MIN_SEARCH_LENGTH", "Min. search length");
define("TFISH_PREFERENCE_SEARCH_PAGINATION", "Search pagination");
define("TFISH_PREFERENCE_ADMIN_PAGINATION", "Admin-side pagination");
define("TFISH_PREFERENCE_SESSION_NAME", "Session name");
define("TFISH_PREFERENCE_SESSION_TIMEOUT", "Session timeout");
define("TFISH_PREFERENCE_SESSION_DOMAIN", "Session domain");
define("TFISH_PREFERENCE_DEFAULT_LANGUAGE", "Default language");
define("TFISH_PREFERENCE_DATE_FORMAT", "Date format (see <a href=\"http://php.net/manual/en/function.date.php\">PHP manual)</a>");
define("TFISH_PREFERENCE_PAGINATION_ELEMENTS", "Max. pagination elements");
define("TFISH_PREFERENCE_USER_PAGINATION", "User-side pagination");
define("TFISH_PREFERENCE_SITE_DESCRIPTION", "Site description");
define("TFISH_PREFERENCE_SITE_AUTHOR", "Site author / publisher");
define("TFISH_PREFERENCE_SITE_COPYRIGHT", "Site copyright");

// Search
define("TFISH_SEARCH", "Search");
define("TFISH_SEARCH_DESCRIPTION", "Search the contents of this website");
define("TFISH_SEARCH_ENTER_TERMS", "Enter search terms");
define("TFISH_SEARCH_ALL", "All (AND)");
define("TFISH_SEARCH_ANY", "Any (OR)");
define("TFISH_SEARCH_EXACT", "Exact match");
define("TFISH_SEARCH_NO_RESULTS", "No results.");
define("TFISH_SEARCH_RESULTS", "results");

// RSS
define("TFISH_RSS", "RSS");

// Permalinks
define("TFISH_TYPE_PERMALINKS", "Permalinks");

// Supported content types
define("TFISH_TYPE_ARTICLE", "Article");
define("TFISH_TYPE_AUDIO", "Audio");
define("TFISH_TYPE_COLLECTION", "Collection");
define("TFISH_TYPE_DOWNLOAD", "Download");
define("TFISH_TYPE_EVENT", "Event");
define("TFISH_TYPE_IMAGE", "Image");
define("TFISH_TYPE_PARTNER", "Partner");
define("TFISH_TYPE_PODCAST", "Podcast");
define("TFISH_TYPE_PROJECT", "Project");
define("TFISH_TYPE_QUOTE", "Quote");
define("TFISH_TYPE_STATIC", "Static");
define("TFISH_TYPE_TAG", "Tag");
define("TFISH_TYPE_VIDEO", "Video");

// Plural terms
define("TFISH_TYPE_ARTICLES", "Articles");
define("TFISH_TYPE_AUDIO _FILES", "Audio files");
define("TFISH_TYPE_COLLECTIONS", "Collections");
define("TFISH_TYPE_DOWNLOADS", "Downloads");
define("TFISH_TYPE_EVENTS", "Events");
define("TFISH_TYPE_IMAGES", "Images");
define("TFISH_TYPE_PARTNERS", "Partners");
define("TFISH_TYPE_PODCASTS", "Podcasts");
define("TFISH_TYPE_PROJECTS", "Projects");
define("TFISH_TYPE_QUOTES", "Quotes");
define("TFISH_TYPE_STATIC_PAGES", "Static pages");
define("TFISH_TYPE_TAGS", "Tags");
define("TFISH_TYPE_VIDEOS", "Videos");

// Pagination controls
define("TFISH_PAGINATION_FIRST", "First");
define("TFISH_PAGINATION_LAST", "Last");

// Base content object properties.
define("TFISH_ID", "ID");
define("TFISH_TYPE", "Type");
define("TFISH_TITLE", "Title");
define("TFISH_TEASER", "Teaser");
define("TFISH_DESCRIPTION", "Description");
define("TFISH_FORMAT", "Format");
define("TFISH_FILE_SIZE", "Bytes");
define("TFISH_CREATOR", "Author");
define("TFISH_IMAGE", "Image");
define("TFISH_CAPTION", "Caption");
define("TFISH_MEDIA", "Media");
define("TFISH_SELECT_FILE", "Select file for upload");
define("TFISH_DATE", "Date");
define("TFISH_PARENT", "Parent");
define("TFISH_LANGUAGE", "Language");
define("TFISH_RIGHTS", "Rights");
define("TFISH_PUBLISHER", "Publisher");
define("TFISH_TAGS", "Tags");
define("TFISH_ONLINE_STATUS", "Status");
define("TFISH_ONLINE", "Online");
define("TFISH_OFFLINE", "Offline");
define("TFISH_SUBMISSION_TIME", "Submitted");
define("TFISH_COUNTER", "Counter");
define("TFISH_VIEWS", "views"); // Alternative representation of counter, which may be more approppriate in some contexts.
define("TFISH_META_TITLE", "Title");
define("TFISH_META_DESCRIPTION", "Description");
define("TFISH_SEO", "SEO");

// Base intellectual property licenses.
define("TFISH_RIGHTS_COPYRIGHT", "Copyright, all rights reserved");
define("TFISH_RIGHTS_ATTRIBUTION", "Creative Commons Attribution");
define("TFISH_RIGHTS_ATTRIBUTION_SHARE_ALIKE", "Creative Commons Attribution-ShareAlike");
define("TFISH_RIGHTS_ATTRIBUTION_NO_DERIVS", "Creative Commons Attribution-NoDerivs");
define("TFISH_RIGHTS_ATTRIBUTION_NON_COMMERCIAL", "Creative Commons Attribution-NonCommercial");
define("TFISH_RIGHTS_ATTRIBUTION_NON_COMMERCIAL_SHARE_ALIKE", "Creative Commons Attribution-NonCommercial-ShareAlike");
define("TFISH_RIGHTS_ATTRIBUTION_NON_COMMERCIAL_NO_DERIVS", "Creative Commons Attribution-NonCommercial-NoDerivs");
define("TFISH_RIGHTS_GPL2", "GNU General Public License Version 2");
define("TFISH_RIGHTS_GPL3", "GNU General Public License Version 3");
define("TFISH_RIGHTS_PUBLIC_DOMAIN", "Public domain");

// Confirmation messages.
define("TFISH_SUBMIT", "Submit");
define("TFISH_UPDATE", "Update");
define("TFISH_CONFIRM_DELETE", "Are you sure?");
define("TFISH_DO_YOU_WANT_TO_DELETE", "Do you want to delete");
define("TFISH_YES", "Yes");
define("TFISH_NO", "No");
define("TFISH_CANCEL", "Cancel");
define("TFISH_BACK", "Back");
define("TFISH_SUCCESS", "Success");
define("TFISH_FAILED", "Failed");
define("TFISH_OBJECT_WAS_INSERTED", "The object was successfully inserted.");
define("TFISH_OBJECT_INSERTION_FAILED", "Object insertion failed.");
define("TFISH_OBJECT_WAS_DELETED", "The object was successfully deleted.");
define("TFISH_OBJECT_DELETION_FAILED", "Object deletion failed");
define("TFISH_OBJECT_WAS_UPDATED", "The object was successfully updated.");
define("TFISH_OBJECT_UPDATE_FAILED", "Object update failed.");
define("TFISH_PREFERENCES_WERE_UPDATED", "Preferences were successfully updated.");
define("TFISH_PREFERENCES_UPDATE_FAILED", "Preference update failed.");

// ERROR MESSAGES.
define("TFISH_ERROR_NO_SUCH_OBJECT", "Object does not exist.");
define("TFISH_ERROR_NO_SUCH_PROPERTY", "Trying to set value of non-existant property");
define("TFISH_ERROR_NO_RESULT", "Database query did not return a statement; query failed.");
define("TFISH_ERROR_NOT_ALPHA", "Illegal characters: Non-alpha.");
define("TFISH_ERROR_NOT_ALNUM", "Illegal characters: Non-alnum.");
define("TFISH_ERROR_NOT_ALNUMUNDER", "Illegal characters: Non-alnumunder.");
define("TFISH_ERROR_NOT_CRITERIA_OBJECT", "Not a TfishCriteria object.");
define("TFISH_ERROR_NOT_DIGIT", "Illegal characters: Non-digit.");
define("TFISH_ERROR_NOT_IP", "Not an IP address.");
define("TFISH_ERROR_INSERTION_FAILED", "Insertion to the database failed.");
define("TFISH_ERROR_NOT_URL", "Not a valid URL.");
define("TFISH_ERROR_NOT_ARRAY", "Not an array.");
define("TFISH_ERROR_NOT_ARRAY_OR_EMPTY", "Not an array, or array empty.");
define("TFISH_ERROR_REQUIRED_PROPERTY_NOT_SET", "Required object property not set.");
define("TFISH_ERROR_COUNT_MISMATCH", "Count mismatch.");
define("TFISH_ERROR_NOT_CRITERIA_ITEM_OBJECT", "Not a TfishCriteriaItem object.");
define("TFISH_ERROR_ILLEGAL_TYPE", "Illegal data type (not whitelisted).");
define("TFISH_ERROR_TYPE_MISMATCH", "Data type mismatch.");
define("TFISH_ERROR_ILLEGAL_VALUE", "Illegal value (not whitelisted).");
define("TFISH_ERROR_NOT_INT", "Not an integer, or integer range violation.");
define("TFISH_ERROR_NOT_BOOL", "Not a boolean.");
define("TFISH_ERROR_NOT_FLOAT", "Not a float.");
define("TFISH_ERROR_NOT_EMAIL", "Not an email.");
define("TFISH_ERROR_NOT_OBJECT", "Not an object, or illegal object type.");
define("TFISH_ERROR_UNKNOWN_MIMETYPE", "Unknown mimetype.");
define("TFISH_ERROR_NO_STATEMENT", "No statement object.");
define("TFISH_ERROR_REQUIRED_PARAMETER_NOT_SET", "Required parameter not set.");
define("TFISH_ERROR_FILE_UPLOAD_FAILED", "File upload failed.");
define("TFISH_ERROR_IMAGE_UPLOAD_FAILED", "Image file upload failed.");
define("TFISH_ERROR_MEDIA_UPLOAD_FAILED", "Media file upload failed.");
define("TFISH_ERROR_FAILED_TO_APPEND_FILE", "Failed to append to file.");
define("TFISH_ERROR_FAILED_TO_SEND_DOWNLOAD", "Failed to initiate download stream.");
define("TFISH_ERROR_BAD_PATH", "Bad file path.");
define("TFISH_ERROR_NOT_AN_UPLOADED_FILE", "Not an uploaded file, possible upload attack.");
define("TFISH_ERROR_NOT_TEMPLATE_OBJECT", "Not a template object.");
define("TFISH_ERROR_TEMPLATE_DOES_NOT_EXIST", "Template file does not exist.");
define("TFISH_CANNOT_OVERWRITE_TEMPLATE_VARIABLE", "Cannot overwrite template variable.");
define("TFISH_ERROR_PAGINATION_PARAMETER_ERROR", "Pagination control parameter error.");
define("TFISH_ERROR_NO_SUCH_CONTENT", "Sorry, this content is not available.");

// File upload error messages.
define("TFISH_ERROR_UPLOAD_ERR_INI_SIZE", "Upload failed: File exceeds maximimum permitted .ini size.");
define("TFISH_ERROR_UPLOAD_ERR_FORM_SIZE", "Upload failed: File exceeds maximum size permitted in form.");
define("TFISH_ERROR_UPLOAD_ERR_PARTIAL", "Upload failed: File upload incomplete (partial).");
define("TFISH_ERROR_UPLOAD_ERR_NO_FILE", "Upload failed: No file to upload.");
define("TFISH_ERROR_UPLOAD_ERR_NO_TMP_DIR", "Upload failed: No temporary upload directory.");
define("TFISH_ERROR_UPLOAD_ERR_CANT_WRITE", "Upload failed: Can't write to disk.");

/*
 * Record any new, changed or deleted language constants below by version, to aid translation.
 */