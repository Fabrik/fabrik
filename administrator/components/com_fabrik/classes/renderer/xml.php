<?php
/**
 * @version		$Id: atom.php 11687 2009-03-11 17:49:23Z ian $
 * @package		Joomla.Framework
 * @subpackage	Fabrik Documents
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();



/**
 * @package 	Joomla.Framework
 * @subpackage	Document
 * @since	1.5
 */

class JDocumentRendererXml extends JDocumentRenderer
{
	/**
	 * Document mime type
	 *
	 * @var		string
	 * @access	private
	 */
	var $_mime = "application/xml";

	/**
	 * Render the feed
	 *
	 * @access public
	 * @return string
	 */
	function render()
	{
		$now	= JFactory::getDate();
		$data	=& $this->_doc;

		$uri =& JFactory::getURI();
		$url = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
		$syndicationURL =& JRoute::_('&format=feed&type=atom');

		$start = '<?xml version="1.0" encoding="iso-8859-1" ?>';
		$xml = "$start<root>\n";
		$xml.= "\t<channel>\n";
		if ($data->title !== '') {
			$xml.= "\t\t<title>".htmlspecialchars($data->title, ENT_COMPAT, 'UTF-8')."</title>\n";
		}
		$xml.= "\t\t<link>$url</link>\n";
		if ($data->description !== '') {
			$xml.= "\t\t<description>".htmlspecialchars($data->description, ENT_COMPAT, 'UTF-8')."</description>\n";
		}
		if ($data->copyright !== '') {
			$xml.= "\t\t<copyright>".htmlspecialchars($data->copyright, ENT_COMPAT, 'UTF-8')."</copyright>\n";
		}
		$xml.= "\t\t<date>".htmlspecialchars($now->toFormat('%d/%m/%Y'), ENT_COMPAT, 'UTF-8')."</date>\n";
		$xml.= "\t\t<time>".htmlspecialchars($now->toFormat('%h:%m:%s'), ENT_COMPAT, 'UTF-8')."</time>\n";
		$xml.= "\t</channel>\n";
		for ($i=0;$i<count($data->items);$i++)
		{
			//$xml.= "\t<collection>\n";
			foreach ($data->items[$i] as $collection) {
				$xml.= "\t<row>\n";
				foreach ($collection as $key => $val) {
					if (substr($key, 0, 1) !== '_' && substr($key, 0, 6) !== 'fabrik' && $key !== 'slug'){
						$xml.= "\t\t<$key>".htmlspecialchars($val, ENT_COMPAT, 'UTF-8')."</$key>\n";
					}
				}
				$xml.= "\t</row>\n";
			}
			//$xml.= "\t</collection>\n";
		}
		$xml.= "</root>\n";
		return $xml;
	}
}
