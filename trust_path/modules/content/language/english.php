<?php
/**
 * Tuskfish content module language constants (English).
 * 
 * Translate this file to convert the content module to another language. To actually use a 
 * translated language file, edit /trust_path/masterfile.php and change the TFISH_DEFAULT_LANGUAGE
 * constant to point at your translated language file.
 *
 * @copyright   Simon Wilkinson 2013-2017 (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     language
 */
// Supported content types
define("TFISH_TYPE_ARTICLE", "Article");
define("TFISH_TYPE_AUDIO", "Audio");
define("TFISH_TYPE_BLOCK", "Block");
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
define("TFISH_TYPE_AUDIO_FILES", "Audio files");
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

// Base content object properties. Some reusable terms are defined in the Tuskfish language file.
define("TFISH_FORMAT", "Format");
define("TFISH_CREATOR", "Author");
define("TFISH_IMAGE", "Image");
define("TFISH_CAPTION", "Caption");
define("TFISH_MEDIA", "Media");
define("TFISH_SELECT_FILE", "Select file for upload");
define("TFISH_PARENT", "Parent");
define("TFISH_LANGUAGE", "Language");
define("TFISH_RIGHTS", "Rights");
define("TFISH_PUBLISHER", "Publisher");
define("TFISH_DELETE_IMAGE", "Delete");
define("TFISH_DELETE_MEDIA", "Delete");

// Actions.
define("TFISH_CURRENT_CONTENT", "Current content");
define("TFISH_ADD_CONTENT", "Add content");
define("TFISH_EDIT_CONTENT", "Edit content");

// Related and parent works.
define("TFISH_PARENT_WORK", "Parent work");
define("TFISH_RELATED", "Related");
define("TFISH_RELATED_WORKS", "Related works");
define("TFISH_IN_THIS_COLLECTION", "In this collection");

// Miscellaneous.
define("TFISH_DOWNLOAD", "Download");
define("TFISH_DOWNLOADS", "Downloads");

// Errors.
define("TFISH_MEDIA_NOT_COMPATIBLE", "The selected media file is not compatible with the current "
        . "content type. Inline media players will not display.");
define("TFISH_ERROR_TAGLINK_UPDATE_FAILED", "Attempt to update references to a non-extant tag"
        . " failed");
define("TFISH_ERROR_PARENT_UPDATE_FAILED", "Attempt to update references to a non-extant collection"
        . " failed.");
define("TFISH_ERROR_NO_SUCH_HANDLER", "No such content handler.");

// Dependency errors.
define("TFISH_ERROR_NOT_CONTENT_OBJECT", "Not a content object.");
define("TFISH_ERROR_NOT_COLLECTION_OBJECT", "Not a collection object");
define("TFISH_ERROR_NOT_TAGLINK_HANDLER", "Not a taglink handler object.");
define("TFISH_ERROR_NOT_CONTENT_FACTORY", "Not a content factory object.");
