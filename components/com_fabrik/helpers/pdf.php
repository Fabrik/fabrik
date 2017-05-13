<?php
/**
 * PDF Set up helper
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');

use Dompdf\Dompdf;
use Dompdf\Options;

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
	 * @param  bool  $puke  throw exception if not installed (true) or just return false
	 *
	 * @return  bool
	 */

	public static function iniDomPdf($puke = false)
	{
		if (!FabrikWorker::canPdf($puke))
		{
			return false;
		}

		$config = JFactory::getConfig();

		$options = new Options();
		$options->set('isRemoteEnabled', true);
		$options->set('fontCache', $config->get('tmp_path'));
		$options->set('tempDir', $config->get('tmp_path'));

		return new Dompdf($options);
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

		$base_root = COM_FABRIK_LIVESITE_ROOT . '/'; // scheme, host, port, without trailing /,add it
		$subdir = str_replace(COM_FABRIK_LIVESITE_ROOT,'',COM_FABRIK_LIVESITE); // subdir /xx/
		$subdir = ltrim($subdir,'/');

		$schemeString = '://'; //if no schemeString found assume path is relative

		try
		{
			$ok = new SimpleXMLElement($data);

			if ($ok)
			{
				$imgs = $ok->xpath('//img');

				foreach ($imgs as &$img)
				{
					if (!strstr($img['src'], $schemeString))
					{
						$base = strstr($img['src'], $subdir) ? $base_root : $base_root . $subdir;
						$img['src'] = $base . ltrim($img['src'],'/');
					}
				}

				// Links
				$as = $ok->xpath('//a');

				foreach ($as as &$a)
				{
					if (!strstr($a['href'], $schemeString))
					{
						$base = strstr($a['href'], $subdir) ? $base_root : $base_root . $subdir;
						$a['href'] = $base . ltrim($a['href'],'/');
					}
				}

				// CSS files.
				$links = $ok->xpath('//link');

				foreach ($links as &$link)
				{
					if ($link['rel'] == 'stylesheet' && !strstr($link['href'], $schemeString))
					{
						$base = strstr($link['href'], $subdir) ? $base_root : $base_root . $subdir;
						$link['href'] = $base . ltrim($link['href'],'/');
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
			if (JDEBUG && $config->get('pdf_debug', false) === true)
			{
				echo "<pre>";
				print_r($errors);
				echo "</pre>";
				exit;
			}
			//Create the full path via general str_replace
			//todo: relative URL starting without /
			else
			{
				$base = $base_root . $subdir;
				$data = str_replace('href="/', 'href="' . $base, $data);
				$data = str_replace('src="/',  'src="'  . $base, $data);
				$data = str_replace("href='/", "href='" . $base, $data);
				$data = str_replace("src='/",  "src='"  . $base, $data);
			}
		}
	}
}
