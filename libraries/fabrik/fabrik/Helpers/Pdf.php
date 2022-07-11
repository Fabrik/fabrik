<?php
/**
 * PDF Set up helper
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Helpers;

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

class Pdf
{
	/**
	 * @param        $html
	 * @param string $size
	 * @param string $orientation
	 */
	public static function renderPdf($html, $size = 'A4', $orientation = 'portrait')
	{
		$config = \JComponentHelper::getParams('com_fabrik');

		if ($config->get('fabrik_pdf_lib', 'dompdf') === 'dompdf')
		{
			$pdf = self::renderDomPdf($html, $size, $orientation);
		}
		else
		{
			$pdf = self::renderMPdf($html, $size, $orientation);
		}

		return $pdf;
	}

	public static function renderMPdf($html, $size = 'A4', $orientation = 'portrait')
	{
		$size = ucfirst($size);

		switch ($orientation)
		{
			case 'landscape':
				$orientation = 'L';
				$size .= '-' . $orientation;
				break;
			case 'portrait':
			default:
				$orientation = 'P';
				break;
		}

		Pdf::fullPaths($html);
		$html = mb_convert_encoding($html,'HTML-ENTITIES','UTF-8');
		try
		{
			$mpdf = new \Mpdf\Mpdf(
				[
					'tempDir'     => \JFactory::getConfig()->get('tmp_path', JPATH_ROOT . '/tmp'),
					'mode'        => 'utf-8',
					'format'      => $size,
					'orientation' => $orientation
				]
			);
			$mpdf->WriteHTML($html);
			return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
		}
		catch (\Mpdf\MpdfException $e)
		{
			// mmmphh
			return '';
		}

	}

	public static function renderDomPdf($html, $size = 'A4', $orientation = 'portrait')
	{
		Pdf::fullPaths($html);
		$html = mb_convert_encoding($html,'HTML-ENTITIES','UTF-8');
		$domPdf = self::iniDomPdf(true);
		$domPdf->set_paper($size, $orientation);
		$domPdf->load_html($html);
		$domPdf->render();
		return $domPdf->output();
	}

	/**
	 * Init selected PDF
	 */
	public static function iniPdf()
	{
		$config = \JComponentHelper::getParams('com_fabrik');

		if ($config->get('fabrik_pdf_lib', 'dompdf') === 'dompdf')
		{
			return self::iniDomPdf(false);
		}
	}

	/**
	 * Set up DomPDF engine
	 *
	 * @param  bool  $puke  throw exception if not installed (true) or just return false
	 *
	 * @return  bool
	 */

	public static function iniDomPdf($puke = false)
	{
		if (!Worker::canPdf($puke))
		{
			return false;
		}

		$config = \JFactory::getConfig();

		$options = new Options();
		$options->set('isRemoteEnabled', true);
		/**
		 * need to enable HTML5 parser to work around a bug in DOMPDF:
		 * https://github.com/dompdf/dompdf/issues/1494#issuecomment-332116978
		 */
		$options->setIsHtml5ParserEnabled(true);
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
			$doc = new \DOMDocument();
			$doc->strictErrorChecking = FALSE;

			// prepend encoding, otherwise UTF-8 will get munged into special chars
            $data = '<?xml version="1.0" encoding="UTF-8"?>' . $data;

            // load the document
			$doc->loadHTML($data);

			// img tags
			$imgs = $doc->getElementsByTagName('img');

			foreach ($imgs as $img)
			{
				$src = $img->getAttribute('src');

				if (!strstr($src, $schemeString))
				{
					$base = empty($subdir) || strstr($src, $subdir) ? $base_root : $base_root . $subdir;
					$src = $base . ltrim($src,'/');
					$img->setAttribute('src', $src);
				}
			}

			// a tags
			$as = $doc->getElementsByTagName('a');

			foreach ($as as $a)
			{
				$href = $a->getAttribute('href');

				if (!strstr($href, $schemeString) && !strstr($href, 'mailto:') && !strstr($href, 'tel:'))
				{
					$base = empty($subdir) || strstr($href, $subdir) ? $base_root : $base_root . $subdir;
					$href = $base . ltrim($href,'/');
					$a->setAttribute('href', $href);
				}
			}

			// link tags
			$links = $doc->getElementsByTagName('link');

			foreach ($links as $link)
			{
				$rel  = $link->getAttribute('rel');
				$href = $link->getAttribute('href');

				if ($rel == 'stylesheet' && !strstr($href, $schemeString))
				{
					$base = empty($subdir) || strstr($href, $subdir) ? $base_root : $base_root . $subdir;
					$href = $base . ltrim($href,'/');
					$link->setAttribute('href', $href);
				}
			}

			$data = $doc->saveHTML();

			/*
			$ok = simplexml_import_dom($doc);

			//$ok = new \SimpleXMLElement($data);

			if ($ok)
			{
				$imgs = $ok->xpath('//img');

				foreach ($imgs as &$img)
				{
					if (!strstr($img['src'], $schemeString))
					{
						$base = empty($subdir) || strstr($img['src'], $subdir) ? $base_root : $base_root . $subdir;
						$img['src'] = $base . ltrim($img['src'],'/');
					}
				}

				// Links
				$as = $ok->xpath('//a');

				foreach ($as as &$a)
				{
					if (!strstr($a['href'], $schemeString) && !strstr($a['href'], 'mailto:'))
					{
                        $base = empty($subdir) || strstr($a['href'], $subdir) ? $base_root : $base_root . $subdir;
						$a['href'] = $base . ltrim($a['href'],'/');
					}
				}

				// CSS files.
				$links = $ok->xpath('//link');

				foreach ($links as &$link)
				{
					if ($link['rel'] == 'stylesheet' && !strstr($link['href'], $schemeString))
					{
						$base = empty($subdir) || strstr($link['href'], $subdir) ? $base_root : $base_root . $subdir;
						$link['href'] = $base . ltrim($link['href'],'/');
					}
				}

				$data = $ok->asXML();
			}
			*/
		}
		catch (\Exception $err)
		{
			// Oho malformed html - if we are debugging the site then show the errors
			// otherwise continue, but it may mean that images/css/links are incorrect
			$errors = libxml_get_errors();
			$config = \JComponentHelper::getParams('com_fabrik');

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
