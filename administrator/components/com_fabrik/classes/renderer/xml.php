<?php
/**
 * Not used
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Not used i think!
 *
 * @package     Joomla.Framework
 * @subpackage  Document
 * @since       1.5
 */

class JDocumentRendererXml extends JDocumentRenderer
{
	/**
	 * Document mime type
	 *
	 * @var		string
	 * @access	private
	 */
	protected $_mime = "application/xml";

	/**
	 * Render the feed
	 *
	 * @access public
	 * @return string
	 */
	public function render()
	{
		$now = JFactory::getDate();
		$data = $this->_doc;

		$uri = JURI::getInstance();
		$url = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
		$syndicationURL =& JRoute::_('&format=feed&type=atom');

		$start = '<?xml version="1.0" encoding="iso-8859-1" ?>';
		$xml = "$start<root>\n";
		$xml .= "\t<channel>\n";

		if ($data->title !== '')
		{
			$xml .= "\t\t<title>" . htmlspecialchars($data->title, ENT_COMPAT, 'UTF-8') . "</title>\n";
		}

		$xml .= "\t\t<link>$url</link>\n";

		if ($data->description !== '')
		{
			$xml .= "\t\t<description>" . htmlspecialchars($data->description, ENT_COMPAT, 'UTF-8') . "</description>\n";
		}

		if ($data->copyright !== '')
		{
			$xml .= "\t\t<copyright>" . htmlspecialchars($data->copyright, ENT_COMPAT, 'UTF-8') . "</copyright>\n";
		}

		$xml .= "\t\t<date>" . htmlspecialchars($now->format('d/m/Y'), ENT_COMPAT, 'UTF-8') . "</date>\n";
		$xml .= "\t\t<time>" . htmlspecialchars($now->format('H:i:s'), ENT_COMPAT, 'UTF-8') . "</time>\n";
		$xml .= "\t</channel>\n";

		for ($i = 0; $i < count($data->items); $i++)
		{
			foreach ($data->items[$i] as $collection)
			{
				$xml .= "\t<row>\n";

				foreach ($collection as $key => $val)
				{
					if (substr($key, 0, 1) !== '_' && JString::substr($key, 0, 6) !== 'fabrik' && $key !== 'slug')
					{
						$xml .= "\t\t<$key>" . htmlspecialchars($val, ENT_COMPAT, 'UTF-8') . "</$key>\n";
					}
				}

				$xml .= "\t</row>\n";
			}
		}

		$xml .= "</root>\n";

		return $xml;
	}
}
