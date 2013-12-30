<?php
/**
 * Plugin element to render fileuploads of file type
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Plugin element to render fileuploads of file type
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       3.0
 */

class FileRender
{
	/**
	 * Render output
	 *
	 * @var  string
	 */
	public $output = '';

	/**
	 * Render a file in list view, stored data in $this->output
	 *
	 * @param   object  &$model   Element model
	 * @param   object  &$params  Element params
	 * @param   string  $file     Row data for this element
	 * @param   object  $thisRow  All row's data
	 *
	 * @return  void
	 */

	public function renderListData(&$model, &$params, $file, $thisRow)
	{
		$this->render($model, $params, $file);
	}

	/**
	 * Render a file in form/details view, stored data in $this->output
	 *
	 * @param   object  &$model   Element model
	 * @param   object  &$params  Element params
	 * @param   string  $file     Row data for this element
	 *
	 * @return  void
	 */

	public function render(&$model, &$params, $file)
	{
		jimport('joomla.filesystem.file');
		$filename = basename($file);
		$filename = strip_tags($filename);
		$ext = JFile::getExt($filename);

		if (!strstr($file, 'http://') && !strstr($file, 'https://'))
		{
			// $$$rob only add in livesite if we don't already have a full url (e.g. from amazons3)

			// Trim / or \ off the start of $file
			$file = JString::ltrim($file, '/\\');
			$file = COM_FABRIK_LIVESITE . $file;
		}

		$file = str_replace("\\", "/", $file);
		$file = $model->storage->preRenderPath($file);
		$thumb_path = COM_FABRIK_BASE . 'media/com_fabrik/images/' . $ext . '.png';

		// $$$ hugh - using 'make_thumbnail' to mean 'use default $ext.png as an icon
		// instead of just putting the filename.
		if ($params->get('make_thumbnail', false) && JFile::exists($thumb_path))
		{
			$thumb_file = COM_FABRIK_LIVESITE . "media/com_fabrik/images/" . $ext . ".png";
			$this->output .= "<a class=\"download-archive fabrik-filetype-$ext\" title=\"$file\" href=\"$file\">
			<img src=\"$thumb_file\" alt=\"$filename\"></a>";
		}
		else
		{
			$this->output .= "<a class=\"download-archive fabrik-filetype-$ext\" title=\"$file\" href=\"$file\">" . $filename . "</a>";
		}
	}

	/**
	 * Build Carousel HTML
	 *
	 * @param   string  $id       Widget HTML id
	 * @param   array   $data     Images to add to the carousel
	 * @param   object  $model    Element model
	 * @param   object  $params   Element params
	 * @param   object  $thisRow  All rows data
	 *
	 * @return  string  HTML
	 */

	public function renderCarousel($id = 'carousel', $data = array(), $model = null, $params = null, $thisRow = null)
	{
		$rendered = '';
		/**
		 * @TODO - build it!
		 */
		return $rendered;
	}
}
