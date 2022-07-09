<?php
/**
 * Fileupload adaptor to render audio play
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Fileupload adaptor to render audio play
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       3.0
 */
class AudioRenderModel extends FabModel
{
	/**
	 * Render output
	 *
	 * @var  string
	 */
	public $output = '';

	/**
	 * Render audio in the list view
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
	 * Render audio in the form view
	 *
	 * @param   object  &$model   Element model
	 * @param   object  &$params  Element params
	 * @param   string  $file     Row data for this element
	 *
	 * @return  void
	 */
	public function render(&$model, &$params, $file)
	{
		$layout = $model->getLayout('audio');
		$displayData = new stdClass;
		$displayData->file = $model->getStorage()->getFileUrl($file);

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
		$id .= '_audio_carousel';

		if (!empty($data))
		{
			$rendered = '
			<div id="' . $id . '"></div>
			';
			$input = $this->app->input;

			if ($input->get('format') != 'raw')
			{
				$js = '
				jwplayer("' . $id . '").setup({
					width: "250",
					height: "30",
					playlist: [
				';
				$files = array();

				foreach ($data as $file)
				{
					$files[] .= '
						{
							"file": "' . COM_FABRIK_LIVESITE . $file . '"
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
}
