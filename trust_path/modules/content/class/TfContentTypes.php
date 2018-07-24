<?php

/**
 * TfContentTypes trait file.
 * 
 * Provides common content type definition.
 * 
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @since       1.0
 * @package     core
 */

/**
 * Content object types trait.
 * 
 * Provides definition of permitted content object types.
 *
 * @copyright   Simon Wilkinson 2013+ (https://tuskfish.biz)
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License (GPL) V2
 * @author      Simon Wilkinson <simon@isengard.biz>
 * @version     Release: 1.0
 * @since       1.03
 * @package     content
 * 
 */
trait TfContentTypes
{
    /**
     * Returns a whitelist of permitted content object types, ie. descendants of TfContentObject.
     * 
     * Use this whitelist when dynamically instantiating content objects. If you create additional
     * types of content object (which must be descendants of the TfContentObject class) you
     * must add them to the whitelist below. Otherwise their use will be denied in many parts of
     * the Tuskfish system.
     * 
     * @return array Array of whitelisted (permitted) content object types.
     */
    public function getTypes()
    {
        return array(
            'TfArticle' => TFISH_TYPE_ARTICLE,
            'TfAudio' => TFISH_TYPE_AUDIO,
            'TfBlock' => TFISH_TYPE_BLOCK,
            'TfCollection' => TFISH_TYPE_COLLECTION,
            'TfDownload' => TFISH_TYPE_DOWNLOAD,
            'TfImage' => TFISH_TYPE_IMAGE,
            'TfStatic' => TFISH_TYPE_STATIC,
            'TfTag' => TFISH_TYPE_TAG,
            'TfVideo' => TFISH_TYPE_VIDEO,
        );
    }
}
