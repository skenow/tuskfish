<?php

/**
* Tuskfish rss class
*
* @copyright	Simon Wilkinson (Crushdepth) 2013-2016
* @license		http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL) V3 or any higher version
* @since		1.0
* @author		Simon Wilkinson (Crushdepth) <simon@isengard.biz>
* @package		core
*/

class TfishArticle extends TfishContentObject
{
	// CHANNEL
	// =======
	// Required
	// --------
	// title (name of the channel
	// link (to HTML webstite corresponding to the channel)
	// description (sentence describing the channel)
	// 
	// Optional
	// --------
	// language http://www.w3.org/TR/REC-html40/struct/dirlang.html#langcodes
	// copyright
	// managingEditor (email of editor)
	// webMaster (email of technical admin)
	// pubDate (as per RFC 822)
	// lastBuildDate (last time the content changed)
	// category (tag??)
	// generator (tuskfish)
	// docs (URL pointing to the doucmentation for the format used in the field)
	// cloud (hmmm....not needed?)
	// ttl (time to live; how long a channel can be cached before refreshing from source)
	// image (gif, jpeg or png that can be displayed with the channel
	// rating (PICS rating for the channel)
	// textInput (text input box that can be displayed with the channel)
	// skipHours (tells aggregators which hours they can skip)
	// skipDays (tells aggregators which days they can skip)
	//
	// ITEM (all optional, but either title or description must be included)
	// ====
	// title
	// link (URL of item)
	// description (teaser)
	// author (email address of the author)
	// category
	// comments (URL of comments page for this item)
	// enclosure (media object attached to this item)
	// guid (string that uniquely identifies the item, a permalink)
	// pubDate (when the item was published)
	// source (RSS channel that the item came from)
	
	public function __construct()
	{
	}
	
	
}