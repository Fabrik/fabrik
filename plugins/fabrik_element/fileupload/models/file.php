<?php
/**
 * Plugin element to render fileuploads of file type
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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

class FileRenderModel
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

		/*
		 * $$$ hugh - TESTING - if $file is empty, we're going to just build an empty bit of DOM
		 * which can then be filled in with the selected image using HTML5 in browser.
		 */
		if (empty($file))
		{
			if ($params->get('make_thumbnail', false))
			{
				$maxWidth = $params->get('thumb_max_width', 125);
				$maxHeight = $params->get('thumb_max_height', 125);
				$this->output .= '<img style="width: ' . $maxWidth . 'px;" src="" alt="" />';
			}
		}
		else
		{
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


			$layout = $model->getLayout('file');
			$displayData = new stdClass;
			$ext_icon = 'media/com_fabrik/images/' . $ext . '.png';
			$displayData->thumb =  COM_FABRIK_LIVESITE . $ext_icon;
			$displayData->useThumb = $params->get('make_thumbnail', false) && JFile::exists(COM_FABRIK_BASE . $ext_icon);
			$displayData->ext = $ext;
			$displayData->filename = $filename;
			$displayData->file = $file;

			$this->output = $layout->render($displayData);
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
	 * @param   bool    $nav      Render a navbar on carousel
	 *
	 * @return  string  HTML
	 */
	public function renderCarousel($id = 'carousel', $data = array(), $model = null, $params = null, $thisRow = null, $nav = true)
	{
		$rendered = '';
		/**
		 * @TODO - build it!
		 */
		return $rendered;
	}
}
