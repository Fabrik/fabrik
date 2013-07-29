<?php
/**
 * @package     Joomla.Framework
 * @subpackage  Document
 * @copyright   Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

require_once JPATH_LIBRARIES . '/joomla/document/html/html.php';

/**
 * DocumentPDF class, provides an easy interface to parse and display a pdf document
 *
 * @package     Joomla.Framework
 * @subpackage  Document
 * @since       1.5
 */
class JDocumentpdf extends JDocumentHTML
{
	private $engine = null;

	private $name = 'joomla';

	/**
	 * Class constructor
	 *
	 * @param   array  $options  Associative array of options
	 */

	public function __construct($options = array())
	{
		parent::__construct($options);

		$config = JComponentHelper::getParams('com_fabrik');
		if ($config->get('pdf_debug', false))
		{
			$this->setMimeEncoding('text/html');
			$this->_type = 'html';
		}
		else
		{
			// Set mime type
			$this->_mime = 'application/pdf';

			// Set document type
			$this->_type = 'pdf';
		}
		if (!$this->iniDomPdf())
		{
			JError::raiseError(500, JText::_('COM_FABRIK_ERR_NO_PDF_LIB_FOUND'));
		}
	}

	/**
	 * Set up DomPDF engine
	 *
	 * @return  bool
	 */

	protected function iniDomPdf()
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
		require_once $file;

		// Default settings are a portrait layout with an A4 configuration using millimeters as units
		$this->engine = new DOMPDF;
		return true;
	}

	/**
	 * Set the paper size and orientation
	 * Note if too small for content then the pdf renderer will bomb out in an infinite loop
	 * Legal seems to be more leiniant than a4 for example
	 * If doing landscape set large paper size
	 *
	 * @param   string  $size         Paper size E.g A4,legal
	 * @param   string  $orientation  Paper orientation landscape|portrait
	 *
	 * @since 3.0.7
	 *
	 * @return  void
	 */

	public function setPaper($size = 'A4', $orientation = 'landscape')
	{
		$size = strtoupper($size);
		$this->engine->set_paper($size, $orientation);
	}

	/**
	 * Sets the document name
	 *
	 * @param   string  $name  Document name
	 *
	 * @return  void
	 */

	public function setName($name = 'joomla')
	{
		$this->name = $name;
	}

	/**
	 * Returns the document name
	 *
	 * @return	string
	 */

	public function getName()
	{
		return $this->name;
	}

	/**
	 * Render the document.
	 *
	 * @param   boolean  $cache   If true, cache the output
	 * @param   array    $params  Associative array of attributes
	 *
	 * @return	string
	 */

	public function render($cache = false, $params = array())
	{
		$pdf = $this->engine;
		$data = parent::render();
		$this->fullPaths($data);
		$pdf->load_html($data);
		$config = JComponentHelper::getParams('com_fabrik');
		if ($config->get('pdf_debug', true))
		{
			return $pdf->output_html();
		}
		else
		{
			$pdf->render();
			$pdf->stream($this->getName() . '.pdf');
		}
		return '';
	}

	/**
	 * Get the contents of a document include
	 *
	 * @param   string  $type     The type of renderer
	 * @param   string  $name     The name of the element to render
	 * @param   array   $attribs  Associative array of remaining attributes.
	 *
	 * @return  The output of the renderer
	 */

	public function getBuffer($type = null, $name = null, $attribs = array())
	{
		if ($type == 'head' || $type == 'component')
		{
			return parent::getBuffer($type, $name, $attribs);
		}
		else
		{
			return '';
		}
	}

	/**
	 * Parse relative images a hrefs and style sheets to full paths
	 *
	 * @param   string  &$data  data
	 *
	 * @return  void
	 */

	private function fullPaths(&$data)
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
		}

	}

}
