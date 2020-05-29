<?php
/**
 * FabrikFeed Document class
 *
 * @package     Joomla
 * @subpackage  Fabrik.Documents
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();


 /**
 * JDocumentRenderer_RSS is a feed that implements RSS 2.0 Specification
 *
 * @author	Johan Janssens <johan.janssens@joomla.org>
 *
 * @package 	Joomla.Framework
 * @subpackage		Document
 * @see http://www.rssboard.org/rss-specification
 * @since	1.5
 */

class JDocumentRendererrss extends JDocumentRenderer
{
	/**
	 * Renderer mime type
	 *
	 * @var		string
	 * @access	private
	 */
	var $_mime = "application/rss+xml";

	/**
	 * Renders a script and returns the results as a string
	 *
	 * @param   string  $name     The name of the element to render
	 * @param   array   $params   Array of values
	 * @param   string  $content  Override the output of the renderer
	 *
	 * @return  string  The output of the script
	 *
	 * @since   11.1
	 */
	public function render($name, $params = null, $content = null)
	{
		$now = JFactory::getDate();
		$data = $this->_doc;
		$uri = JURI::getInstance();
		$url = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
		$feed = "<rss version=\"2.0\" ";
		//ADD in any special name spaces we have used in view.fabrikfeed.php
		$feed.= $data->_namespace;
		$feed.= ">\n";
		$feed.= "	<channel>\n";
		$feed.= "		<title>" . $data->title . "</title>\n";
		$feed.= "		<description>" . $data->description . "</description>\n";
		$feed.= "		<link>" . $url . $data->link . "</link>\n";
		$feed.= "		<lastBuildDate>" . htmlspecialchars($now->toRFC822(), ENT_COMPAT, 'UTF-8') . "</lastBuildDate>\n";
		$feed.= "		<generator>" . $data->getGenerator() . "</generator>\n";

		if ($data->image != null)
		{
			$feed.= "		<image>\n";
			$feed.= "			<url>" . $data->image->url . "</url>\n";
			$feed.= "			<title>" . htmlspecialchars($data->image->title, ENT_COMPAT, 'UTF-8') . "</title>\n";
			$feed.= "			<link>" . $data->image->link . "</link>\n";

			if ($data->image->width != "")
			{
				$feed.= "			<width>" . $data->image->width . "</width>\n";
			}

			if ($data->image->height != "")
			{
				$feed.= "			<height>" . $data->image->height . "</height>\n";
			}

			if ($data->image->description!="")
			{
				$feed.= "			<description><![CDATA[" . $data->image->description . "]]></description>\n";
			}

			$feed.= "		</image>\n";
		}

		if ($data->language != "")
		{
			$feed.= "		<language>" . $data->language . "</language>\n";
		}

		if ($data->copyright != "")
		{
			$feed.= "		<copyright>" . htmlspecialchars($data->copyright,ENT_COMPAT, 'UTF-8') . "</copyright>\n";
		}

		if ($data->editor != "")
		{
			$feed.= "		<managingEditor>" . htmlspecialchars($data->editor, ENT_COMPAT, 'UTF-8') . "</managingEditor>\n";
		}

		if ($data->webmaster != "")
		{
			$feed.= "		<webMaster>" . htmlspecialchars($data->webmaster, ENT_COMPAT, 'UTF-8') . "</webMaster>\n";
		}

		if ($data->pubDate != "") {
			$pubDate = JFactory::getDate($data->pubDate);
			$feed.= "		<pubDate>" . htmlspecialchars($pubDate->toRFC822(),ENT_COMPAT, 'UTF-8') . "</pubDate>\n";
		}

		if ($data->category != "")
		{
			$feed.= "		<category>" . htmlspecialchars($data->category, ENT_COMPAT, 'UTF-8') . "</category>\n";
		}

		if ($data->docs != "")
		{
			$feed.= "		<docs>" . htmlspecialchars($data->docs, ENT_COMPAT, 'UTF-8') . "</docs>\n";
		}

		if ($data->ttl != "")
		{
			$feed.= "		<ttl>" . htmlspecialchars($data->ttl, ENT_COMPAT, 'UTF-8') . "</ttl>\n";
		}

		if ($data->rating != "")
		{
			$feed.= "		<rating>" . htmlspecialchars($data->rating, ENT_COMPAT, 'UTF-8') . "</rating>\n";
		}

		if ($data->skipHours != "")
		{
			$feed.= "		<skipHours>".htmlspecialchars($data->skipHours, ENT_COMPAT, 'UTF-8') . "</skipHours>\n";
		}

		if ($data->skipDays != "")
		{
			$feed.= "		<skipDays>" . htmlspecialchars($data->skipDays, ENT_COMPAT, 'UTF-8') . "</skipDays>\n";
		}

		for ($i = 0; $i < count($data->items); $i++)
		{
			/**
			 * Run text through html_entity_decode before htmlspecialchars, in case it contains non XML entities like &eacute
			 */

			$feed.= "		<item>\n";
			$feed.= "			<title><![CDATA[" .  htmlspecialchars(strip_tags(html_entity_decode($data->items[$i]->title, ENT_COMPAT, 'UTF-8')), ENT_COMPAT, 'UTF-8') . "]]></title>\n";
			$feed.= "			<link>" . $url . $data->items[$i]->link . "</link>\n";
			//$feed.= "			<description><![CDATA[" . htmlspecialchars(html_entity_decode($this->_relToAbs($data->items[$i]->description), ENT_COMPAT, 'UTF-8'), ENT_COMPAT, 'UTF-8') . "]]></description>\n";
			$feed.= "			<description><![CDATA[" . html_entity_decode($this->_relToAbs($data->items[$i]->description), ENT_COMPAT, 'UTF-8') . "]]></description>\n";

			if ($data->items[$i]->author != "")
			{
				$feed.= "			<author>" . htmlspecialchars($data->items[$i]->author, ENT_COMPAT, 'UTF-8') . "</author>\n";
			}

			/*
			// on hold
			if ($data->items[$i]->source!="") {
					$data.= "			<source>".htmlspecialchars($data->items[$i]->source, ENT_COMPAT, 'UTF-8')."</source>\n";
			}
			*/

			if ($data->items[$i]->category != "")
			{
				$feed.= "			<category>" . htmlspecialchars($data->items[$i]->category, ENT_COMPAT, 'UTF-8') . "</category>\n";
			}

			if ($data->items[$i]->comments != "")
			{
				$feed.= "			<comments>" . htmlspecialchars($data->items[$i]->comments, ENT_COMPAT, 'UTF-8') . "</comments>\n";
			}

			if ($data->items[$i]->date != "")
			{
				$itemDate = JFactory::getDate($data->items[$i]->date);
				$feed.= "			<pubDate>" . htmlspecialchars($itemDate->toRFC822(), ENT_COMPAT, 'UTF-8') . "</pubDate>\n";
			}

			if ($data->items[$i]->guid!="")
			{
				$feed.= "			<guid>" . htmlspecialchars($data->items[$i]->guid, ENT_COMPAT, 'UTF-8') . "</guid>\n";
			}

			if ($data->items[$i]->enclosure != NULL)
			{
					$feed.= "			<enclosure url=\"";
					$feed.= $data->items[$i]->enclosure->url;
					$feed.= "\" length=\"";
					$feed.= $data->items[$i]->enclosure->length;
					$feed.= "\" type=\"";
					$feed.= $data->items[$i]->enclosure->type;
					$feed.= "\"/>\n";
			}

			// ADD in some funky new
			foreach ($data->_itemTags as $tagName)
			{
				// $$$ hugh - trying to fix :'s in object names
				if (isset($data->items[$i]->{$tagName}))
				{
					$val = $data->items[$i]->{$tagName};
					$feed.="			$val\n";
				}
			}

			$feed.= "		</item>\n";
		}

		$feed.= "	</channel>\n";
		$feed.= "</rss>\n";

		return $feed;
	}

	/**
	 * Convert links in a text from relative to absolute
	 *
	 * @access public
	 * @return  string
	 */
	function _relToAbs($text)
	{
		$base = JURI::base();
  		$text = preg_replace("/(href|src)=\"(?!http|ftp|https)([^\"]*)\"/", "$1=\"$base\$2\"", $text);

		return $text;
	}
}
