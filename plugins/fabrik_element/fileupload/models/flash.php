<?php
/**
 * Fileupload - Plugin element to render Flash files
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Fileupload - Plugin element to render Flash files
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       3.0
 */
class FlashRenderModel extends FabModel
{
	/**
	 * Render output
	 *
	 * @var  string
	 */
	public $output = '';

	/**
	 * Render flash in the list view
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
	 * Render flash in the form view
	 *
	 * @param   object  &$model   Element model
	 * @param   object  &$params  Element params
	 * @param   string  $file     Row data for this element
	 *
	 * @return  void
	 */
	public function render(&$model, &$params, $file)
	{
		$getID3 = FabrikWorker::getID3Instance();

		if ($getID3 === false)
		{
			$this->output = FText::_('COM_FABRIK_LIBRARY_NOT_INSTALLED');

			return;
		}

		$fbConfig = JComponentHelper::getParams('com_fabrik');

		// Analyse file and store returned data in $ThisFileInfo
		$relPath = str_replace("\\", "/", JPATH_SITE . $file);
		$thisFileInfo = $getID3->analyze($relPath);

		$w = $params->get('fu_main_max_width', 0);
		$h = $params->get('fu_main_max_height', 0);

		if ($thisFileInfo && array_key_exists('swf', $thisFileInfo))
		{
			if ($thisFileInfo['swf']['header']['frame_width'] < $w || $thisFileInfo['swf']['header']['frame_height'] < $h)
			{
				$w = $thisFileInfo['swf']['header']['frame_width'];
				$h = $thisFileInfo['swf']['header']['frame_height'];
			}
		}

		if ($w <= 0 || $h <= 0)
		{
			$w = 800;
			$h = 600;
		}

		$layout = $model->getLayout('flash');
		$displayData = new stdClass;
		$displayData->useThumbs = !$model->inDetailedView && $fbConfig->get('use_mediabox', true) && $params->get('make_thumbnail', false);
		$displayData->width = $w;
		$displayData->height = $h;
		$displayData->inDetailedView = $model->inDetailedView;
		$displayData->file = $file;

		if ($displayData->useThumbs)
		{
			// @TODO - work out how to do thumbnails
			$thumb_dir = $params->get('thumb_dir');

			if (!empty($thumb_dir))
			{
				$file = str_replace("\\", "/", $file);
				$pathinfo = pathinfo($file);

				// $$$ hugh - apparently filename constant only added in PHP 5.2
				if (!isset($pathinfo['filename']))
				{
					$pathinfo['filename'] = explode('.', $pathinfo['basename']);
					$pathinfo['filename'] = $pathinfo['filename'][0];
				}

				$thumb_path = COM_FABRIK_BASE . $thumb_dir . '/' . $pathinfo['filename'] . '.png';

				if (JFile::exists($thumb_path))
				{
					$thumb_file = COM_FABRIK_LIVESITE . $thumb_dir . '/' . $pathinfo['filename'] . '.png';
				}
				else
				{
					$thumb_file = COM_FABRIK_LIVESITE . "media/com_fabrik/images/flash.jpg";
				}
			}
			else
			{
				$thumb_file = COM_FABRIK_LIVESITE . "media/com_fabrik/images/flash.jpg";
			}

			$file = str_replace("\\", "/", COM_FABRIK_LIVESITE . $file);
			$displayData->thumb = $thumb_file;
			$displayData->file = $file;
		}

		$this->output = $layout->render($displayData);
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
