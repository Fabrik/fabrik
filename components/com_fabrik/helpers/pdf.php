<?php
/**
 * PDF Set up helper
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');

/**
 * PDF Set up helper
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       3.1rc3
 */

class FabrikPDFHelper
{
	/**
	 * Set up DomPDF engine
	 *
	 * @return  bool
	 */

	public static function iniDomPdf()
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		$file = JPATH_LIBRARIES . '/dompdf/dompdf_config.inc.php';

		if (!JFile::exists($file))
		{
			return false;
		}

		if (!defined('DOMPDF_ENABLE_REMOTE'))
		{
			define('DOMPDF_ENABLE_REMOTE', true);
		}

		$config = JFactory::getConfig();

		if (!defined('DOMPDF_FONT_CACHE'))
		{
			define('DOMPDF_FONT_CACHE', $config->get('tmp_path'));
		}

		if (!defined('DOMPDF_TEMP_DIR'))
		{
			define('DOMPDF_TEMP_DIR', $config->get('tmp_path'));
		}

		require_once $file;

		return true;
	}

	/**
	 * Parse relative images a hrefs and style sheets to full paths
	 *
	 * @param   string  &$data  data
	 *
	 * @return  void
	 */

	public static function fullPaths(&$data)
	{
		$data = str_replace('xmlns=', 'ns=', $data);
		libxml_use_internal_errors(true);

		try
		{
			$ok = new SimpleXMLElement($data);

			if ($ok)
			{
				$uri = JUri::getInstance();
				$base = $uri->getScheme() . '://' . $uri->getHost();
				$imgs = $ok->xpath('//img');

				foreach ($imgs as &$img)
				{
					if (!strstr($img['src'], $base))
					{
						$img['src'] = $base . $img['src'];
					}
				}

				// Links
				$as = $ok->xpath('//a');

				foreach ($as as &$a)
				{
					if (!strstr($a['href'], $base))
					{
						$a['href'] = $base . $a['href'];
					}
				}

				// CSS files.
				$links = $ok->xpath('//link');

				foreach ($links as &$link)
				{
					if ($link['rel'] == 'stylesheet' && !strstr($link['href'], $base))
					{
						$link['href'] = $base . $link['href'];
					}
				}

				$data = $ok->asXML();
			}
		}
		catch (Exception $err)
		{
			// Oho malformed html - if we are debugging the site then show the errors
			// otherwise continue, but it may mean that images/css/links are incorrect
			$errors = libxml_get_errors();
			$config = JComponentHelper::getParams('com_fabrik');

			// Don't show the errors if we want to debug the actual pdf html
			if (JDEBUG && $config->get('pdf_debug', true) === true)
			{
				echo "<pre>";
				print_r($errors);
				echo "</pre>";
				exit;
			}
			//Create the full path via general str_replace
			else
			{
				$uri = JUri::getInstance();
				$base = $uri->getScheme() . '://' . $uri->getHost();
				$data = str_replace('href="/', 'href="'.$base.'/', $data);
				$data = str_replace('src="/', 'src="'.$base.'/', $data);
				$data = str_replace("href='/", "href='".$base.'/', $data);
				$data = str_replace("src='/", "src='".$base.'/', $data);
			}
		}
	}
}
