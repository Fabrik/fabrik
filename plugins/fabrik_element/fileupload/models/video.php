<?php
/**
 * Fileupload adaptor to render uploaded videos
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Fileupload adaptor to render uploaded videos
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       3.0
 */
class VideoRenderModel extends FabModel
{
	/**
	 * Render output
	 *
	 * @var  string
	 */
	public $output = '';

	/**
	 * Render Video in the list view
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
	 * Render Video in the form view
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

		$src = $model->getStorage()->getFileUrl($file);

		// Analyse file and store returned data in $ThisFileInfo
		$relPath = JPATH_SITE . $file;
		$thisFileInfo = $getID3->analyze($relPath);

		if (array_key_exists('video', $thisFileInfo))
		{
			if (array_key_exists('resolution_x', $thisFileInfo['video']))
			{
				$w = $thisFileInfo['video']['resolution_x'];
				$h = $thisFileInfo['video']['resolution_y'];
			}
			else
			{
				// For wmv files
				$w = $thisFileInfo['video']['streams']['2']['resolution_x'];
				$h = $thisFileInfo['video']['streams']['2']['resolution_y'];
			}

			switch ($thisFileInfo['fileformat'])
			{
				// Add in space for controller
				case 'quicktime':
					$h += 16;
					break;
				default:
					$h += 64;
			}
		}

		$displayData = new stdClass;
		$displayData->width = $w;
		$displayData->height = $h;
		$displayData->src = $src;

		switch ($thisFileInfo['fileformat'])
		{
			case 'asf':
				$layout = $model->getLayout('video-asf');
				break;
			default:
				$layout = $model->getLayout('video');
				break;
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
		$id .= '_video_carousel';

		if (!empty($data))
		{
			$rendered = '
			<div id="' . $id . '"></div>
			';
			$input = $this->pp->input;

			if ($input->get('format') != 'raw')
			{
				$js = '
				jwplayer("' . $id . '").setup({
					playlist: [
				';
				$files = array();

				foreach ($data as $file)
				{
					$files[] .= '
						{
							"file": "' . COM_FABRIK_LIVESITE . ltrim($file, '/') . '"
						}
					';
				}

				$js .= implode(',', $files);
				$js .= ']
				});
				';
				FabrikHelperHTML::script('plugins/fabrik_element/fileupload/lib/jwplayer/jwplayer.js', $js);
			}
		}

		return $rendered;
	}

	/**
	 * Get thumb
	 *
	 * @param   string  $video_file  Video SRC
	 *
	 * @return  void
	 */
	private function getThumb($video_file)
	{
	}
}
