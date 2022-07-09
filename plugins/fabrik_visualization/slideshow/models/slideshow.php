<?php
/**
 * Slideshow viz Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.slideshow
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/visualization.php';

/**
 * Slideshow viz Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.slideshow
 * @since       3.0
 */

class FabrikModelSlideshow extends FabrikFEModelVisualization
{
	/**
	 * Get image js data
	 *
	 * @return stdClass
	 */

	public function getImageJSData()
	{
		$params = $this->getParams();
		$listModel = $this->getSlideListModel();
		$table = $listModel->getTable();
		$listModel->getPagination(0, 0, 0);
		//$listModel->render();
		$alldata = $listModel->getData();

		$slideElement = $this->getSlideElement();

		$slideshow_viz_file = $params->get('slideshow_viz_file', '');

		/**
		 * For AJAX upload, paths will be in non-raw, joined by GROUPSPLITTER,
		 * with the join ID's being in the non raw.  For simple uploads, we need
		 * unformatted simple path from _raw.
		 */
		//$slideshow_viz_file .= $slideElement->isJoin() ? '' : '_raw';
		$slideshow_viz_file_raw = $slideshow_viz_file . '_raw';
		$slideshow_viz_file_id = $slideshow_viz_file . '_id';

		$slideshow_viz_caption = $params->get('slideshow_viz_caption', '');

		$js_opts = new stdClass;

		foreach ($alldata as $data)
		{
			foreach ($data as $pic)
			{
				if (!isset($pic->$slideshow_viz_file) && !isset($pic->$slideshow_viz_file_raw))
				{
					//throw new InvalidArgumentException($params->get('slideshow_viz_file', '') . ' not found - is it set to show in the list view?');
					continue;
				}

				$picData = '';

				if (!$slideElement->isJoin())
				{
					$picData = $pic->$slideshow_viz_file_raw;
				}
				else
				{
					$picData = $pic->$slideshow_viz_file_id;
				}

				if (FabrikWorker::isJSON($picData))
				{
					$picData = json_decode($picData);

					if (is_array($picData))
					{
						$picData = array_pop($picData);
					}

					if (is_object($picData) && isset($picData->file))
					{
						$picData = $picData->file;
					}
					else
					{
						continue;
					}
				}
				$picData = str_replace("\\", "/", $picData);

				$pic_opts = array();

				if (!empty($slideshow_viz_caption) && isset($pic->$slideshow_viz_caption))
				{
					// Force it to a string for json_encode
					$pic_opts['caption'] = $pic->$slideshow_viz_caption . ' ';
				}
				else
				{
					$pic_opts['caption'] = '';
				}


				/**
				 * AJAX uploads will (hopefully!) have been CONCAT'ed into the parent element
				 * with the //..*..// group splitter.
				 */
				foreach (explode(GROUPSPLITTER, $picData) as $path)
				{
					$tmp = json_decode($path);
					$k = $tmp == false ? $path : $tmp[0];

					// just in case ...
					if (!$slideElement->getStorage()->exists($k))
					{
						continue;
					}

					$pic_opts['href'] = $slideElement->getStorage()->getFileUrl($k, 0);
					$this->addThumbOpts($pic_opts);
					$pic_opts['fabrik_view_url'] = $pic->fabrik_view_url;
					$pic_opts['fabrik_edit_url'] = $pic->fabrik_edit_url;

					if (!empty($k))
					{
						$js_opts->$k = $pic_opts;
					}
				}
			}
		}

		$this->totalPics = count((array)$js_opts);

		return $js_opts;
	}

	/**
	 * Get the slide list model
	 *
	 * @since   3.0.6
	 *
	 * @return  object  list model
	 */

	protected function getSlideListModel()
	{
		if (!isset($this->listModel))
		{
			$params = $this->getParams();
			$listid = $params->get('slideshow_viz_table');
			$this->listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
			$this->listModel->setId($listid);
		}

		return $this->listModel;
	}

	/**
	 * Get the slide fileupload element
	 *
	 * @since   3.0.6
	 *
	 * @return  object  element model
	 */

	protected function getSlideElement()
	{
		if (!isset($this->slideElement))
		{
			$params = $this->getParams();
			$listModel = $this->getSlideListModel();
			$form = $listModel->getFormModel();
			$this->slideElement = $form->getElement($params->get('slideshow_viz_file', ''));
		}

		return $this->slideElement;
	}

	/**
	 * Add in the thumb src
	 *
	 * @param   array  &$pic_opts  picture options
	 *
	 * @since   3.0.6
	 *
	 * @return  void
	 */

	protected function addThumbOpts(&$pic_opts)
	{
		$params = $this->getParams();

		if ($params->get('slideshow_viz_thumbnails', false))
		{
			$slideElement = $this->getSlideElement();
			$pic_opts['thumbnail'] = $slideElement->getStorage()->_getThumb(str_replace(COM_FABRIK_LIVESITE, '', $pic_opts['href']));
		}
	}

	/**
	 * Get JS
	 *
	 * @return string
	 */

	public function getJS()
	{
		$params = $this->getParams();
		$viz = $this->getVisualization();

		$use_thumbs = $params->get('slideshow_viz_thumbnails', 0);
		$use_captions = $params->get('slideshow_viz_caption', '') == '' ? false : true;
		$opts = new stdClass;
		//$opts->slideshow_data = $slideshow_data = $this->getImageJSData();
		$opts->id = $viz->id;
		$opts->html_id = $html_id = 'slideshow_viz_' . $viz->id;
		$opts->slideshow_width = (int) $params->get('slideshow_viz_width', 400);
		$opts->slideshow_height = (int) $params->get('slideshow_viz_height', 300);
		$opts->slideshow_delay = (int) $params->get('slideshow_viz_delay', 5000);
		$opts->slideshow_duration = (int) $params->get('slideshow_viz_duration', 2000);
		$opts->slideshow_options = $params->get('slideshow_viz_options', '{}');
		$opts->slideshow_thumbnails = $use_thumbs ? true : false;
		$opts->slideshow_captions = $use_captions ? true : false;
		$opts->container = "slideshow_viz_" . $this->getVisualization()->id;
		$opts->liveSite = COM_FABRIK_LIVESITE;
		$opts = json_encode($opts);
		$ref = $this->getJSRenderContext();
		$html = array();
		$html[] = "$ref = new FbSlideshowViz('" . $html_id . "', $opts)\n";
		$html[] = "\n" . "Fabrik.addBlock('$ref', $ref);";
		$html[] = $this->getFilterJs();

		return implode("\n", $html);
	}

	/**
	 * Set an array of list id's whose data is used inside the visualization
	 *
	 * @return  void
	 */

	protected function setListIds()
	{
		if (!isset($this->listids))
		{
			$params = $this->getParams();
			$this->listids = (array) $params->get('slideshow_viz_table');
		}
	}
}
