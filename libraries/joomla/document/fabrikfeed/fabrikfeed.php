<?php
/**
 * @version		$Id: feed.php 10381 2008-06-01 03:35:53Z pasamio $
 * @package		Joomla.Framework
 * @subpackage	Document
 * @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * DocumentFeed class, provides an easy interface to parse and display any feed document
 *
 * @author		Johan Janssens <johan.janssens@joomla.org>
 * @package		Joomla.Framework
 * @subpackage	Document
 * @since		1.5
 */

class JDocumentFabrikfeed extends JDocument
{
	/**
	 * Syndication URL feed element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $syndicationURL = "";

	/**
	 * Image feed element
	 *
	 * optional
	 *
	 * @var		object
	 * @access	public
	 */
	var $image = null;

	/**
	 * Copyright feed elememnt
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $copyright = "";

	/**
	 * Published date feed element
	 *
	 *  optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $pubDate = "";

	/**
	 * Lastbuild date feed element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $lastBuildDate = "";

	/**
	 * Editor feed element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $editor = "";

	/**
	 * Docs feed element
	 *
	 * @var		string
	 * @access	public
	 */
	var $docs = "";

	/**
	 * Editor email feed element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $editorEmail = "";

	/**
	 * Webmaster email feed element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $webmaster = "";

	/**
	 * Category feed element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $category = "";

	/**
	 * TTL feed attribute
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $ttl = "";

	/**
	 * Rating feed element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $rating = "";

	/**
	 * Skiphours feed element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $skipHours = "";

	/**
	 * Skipdays feed element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $skipDays = "";

	/**
	 * The feed items collection
	 *
	 * @var array
	 * @access public
	 */
	var $items = array();

	/**
	 * Class constructor
	 *
	 * @access protected
	 * @param   array	$options Associative array of options
	 */
	function __construct($options = array())
	{
		parent::__construct($options);

		//set document type
		$this->_type = 'fabrikfeed';
	}

	/**
	 * Outputs the document
	 *
	 * @param   boolean  $cache   If true, cache the output
	 * @param   array    $params  Associative array of attributes
	 *
	 * @return  The rendered data
	 *
	 * @since   11.1
	 */
	public function render($cache = false, $params = array())
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$option = $input->get('option');

		// Get the feed type
		$type = $input->get('type', 'rss');

		/*
		 * Cache TODO In later release
		 */
		$cache = 0;
		$cache_time = 3600;
		$cache_path = JPATH_BASE . '/cache';

		// Set filename for rss feeds
		$file = strtolower(str_replace('.', '', $type));
		$file = $cache_path . '/' . $file . '_' . $option . '.xml';

		// Instantiate feed renderer and set the mime encoding
		$renderer = $this->loadRenderer(($type) ? $type : 'rss');

		if (!is_a($renderer, 'JDocumentRenderer'))
		{
			throw new RuntimeException('Resource Not Found', 404);
		}

		$this->setMimeEncoding($renderer->getContentType());

		// Generate prolog
		$data = "<?xml version=\"1.0\" encoding=\"" . $this->_charset . "\"?>\n";
		$data .= "<!-- generator=\"" . $this->getGenerator() . "\" -->\n";

		// Generate stylesheet links
		foreach ($this->_styleSheets as $src => $attr)
		{
			$data .= "<?xml-stylesheet href=\"$src\" type=\"" . $attr['mime'] . "\"?>\n";
		}

		// Render the feed
		$data .= $renderer->render('fabrik');

		parent::render();
		return $data;
	}

	/**
	 * Adds an JFabrikfeedItem to the feed.
	 *
	 * @param object JFabrikfeedItem $item The feeditem to add to the feed.
	 * @access public
	 */
	function addItem(&$item)
	{
		$item->source = $this->link;
		$this->items[] = $item;
	}
}

/**
 * JFabrikfeedItem is an internal class that stores feed item information
 *
 * @author		Johan Janssens <johan.janssens@joomla.org>
 * @package 	Joomla.Framework
 * @subpackage		Document
 * @since	1.5
 */
class JFabrikFeedItem extends JObject
{
	/**
	 * Title item element
	 *
	 * required
	 *
	 * @var		string
	 * @access	public
	 */
	var $title;

	/**
	 * Link item element
	 *
	 * required
	 *
	 * @var		string
	 * @access	public
	 */
	var $link;

	/**
	 * Description item element
	 *
	 * required
	 *
	 * @var		string
	 * @access	public
	 */
	var $description;

	/**
	 * Author item element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $author;

	/**
	 * Author email element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $authorEmail;

	/**
	 * Category element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $category;

	/**
	 * Comments element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $comments;

	/**
	 * Enclosure element
	 *
	 * @var		object
	 * @access	public
	 */
	var $enclosure = null;

	/**
	 * Guid element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $guid;

	/**
	 * Published date
	 *
	 * optional
	 *
	 *  May be in one of the following formats:
	 *
	 *	RFC 822:
	 *	"Mon, 20 Jan 03 18:05:41 +0400"
	 *	"20 Jan 03 18:05:41 +0000"
	 *
	 *	ISO 8601:
	 *	"2003-01-20T18:05:41+04:00"
	 *
	 *	Unix:
	 *	1043082341
	 *
	 * @var		string
	 * @access	public
	 */
	var $pubDate;

	/**
	 * Source element
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $source;

	/**
	 * Set the JFabrikfeedEnclosure for this item
	 *
	 * @access public
	 * @param object $enclosure The JFabrikfeedItem to add to the feed.
	 */
	function setEnclosure($enclosure)
	{
		// $$$ hugh - fixing enclosures ... $enclosure arg is an array,
		// need to instantiate JFabrikFeedEnclosure and pas it the array
		$this->enclosure = new JFabrikFeedEnclosure($enclosure);
	}
}

/**
 * JFabrikfeedEnclosure is an internal class that stores feed enclosure information
 *
 * @author		Johan Janssens <johan.janssens@joomla.org>
 * @package 	Joomla.Framework
 * @subpackage		Document
 * @since	1.5
 */
class JFabrikFeedEnclosure extends JObject
{
	/**
	 * URL enclosure element
	 *
	 * required
	 *
	 * @var		string
	 * @access	public
	 */
	var $url = "";

	/**
	 * Lenght enclosure element
	 *
	 * required
	 *
	 * @var		string
	 * @access	public
	 */
	var $length = "";

	/**
	 * Type enclosure element
	 *
	 * required
	 *
	 * @var		string
	 * @access	public
	 */
	var $type = "";

	// $$$ hugh - added class creator which takes the $enclosure array
	// and stuffs the elements into the class vars
	function __construct($enclosure)
	{
		if (!empty($enclosure))
		{
			$this->url = $enclosure['url'];
			$this->length = $enclosure['length'];
			$this->type = $enclosure['type'];
		}
	}
}

/**
 * JFabrikfeedImage is an internal class that stores feed image information
 *
 * @author		Johan Janssens <johan.janssens@joomla.org>
 * @package 	Joomla.Framework
 * @subpackage		Document
 * @since	1.5
 */
class JFabrikFeedImage extends JObject
{
	/**
	 * Title image attribute
	 *
	 * required
	 *
	 * @var		string
	 * @access	public
	 */
	var $title = "";

	/**
	 * URL image attribute
	 *
	 * required
	 *
	 * @var		string
	 * @access	public
	 */
	var $url = "";

	/**
	 * Link image attribute
	 *
	 * required
	 *
	 * @var		string
	 * @access	public
	 */
	var $link = "";

	/**
	 * witdh image attribute
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $width;

	/**
	 * Title feed attribute
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $height;

	/**
	 * Title feed attribute
	 *
	 * optional
	 *
	 * @var		string
	 * @access	public
	 */
	var $description;
}
