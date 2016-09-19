<?php
/**
 * Slideshow viz Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.slideshow
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
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
	 * Get slideshow HTML container markup
	 *
	 * @return string
	 */

	public function getSlideshow()
	{
		$id = 'foo_for_now_fix_this';
		$return = "
			<div id=\"$id\" class=\"slideshow\">
				<div class=\"slideshow-images\">
					<a><img /></a>
					<div class=\"slideshow-loader\"></div>
				</div>
				<div class=\"slideshow-captions\"></div>
				<div class=\"slideshow-controller\"></div>
				<div class=\"slideshow-thumbnails\"></div>
			</div>
		";

		return $return;
	}

	/**
	 * Get playlist
	 *
	 * @return string
	 */

	public function getPlaylist()
	{
		$params = $this->getParams();
		$mediaElement = $params->get('media_media_elementList');
		$mediaElement .= '_raw';
		$titleElement = $params->get('media_title_elementList', '');
		$imageElement = $params->get('media_image_elementList', '');

		if (!empty($imageElement))
		{
			$imageElement .= '_raw';
		}

		$infoElement = $params->get('media_info_elementList', '');
		$noteElement = $params->get('media_note_elementList', '');

		$listid = $params->get('media_table');

		$listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
		$listModel->setId($listid);
		$list = $listModel->getTable();
		$form = $listModel->getFormModel();
		/* remove filters?
		 * $$$ hugh - remove pagination BEFORE calling render().  Otherwise render() applies
		 * session state/defaults when it calls getPagination, which is then returned as a cached
		 * object if we call getPagination after render().  So call it first, then render() will
		 * get our cached pagination, rather than vice versa.
		 */
		$nav = $listModel->getPagination(0, 0, 0);
		$listModel->render();
		$alldata = $listModel->getData();
		$document = JFactory::getDocument();
		$str = "<?xml version=\"1.0\" encoding=\"" . $document->_charset . "\"?>\n";
		$str .= "<playlist version=\"1\" xmlns = \"http://xspf.org/ns/0/\">\n";
		$str .= "	<title>" . $list->label . "</title>\n";
		$str .= "	<trackList>\n";

		foreach ($alldata as $data)
		{
			foreach ($data as $row)
			{
				if (!isset($row->$mediaElement))
				{
					continue;
				}

				$location = $row->$mediaElement;

				if (empty($location))
				{
					continue;
				}

				$location = str_replace('\\', '/', $location);
				$location = JString::ltrim($location, '/');
				$location = COM_FABRIK_LIVESITE . $location;
				$str .= "		<track>\n";
				$str .= "			<location>" . $location . "</location>\n";

				if (!empty($titleElement))
				{
					$title = $row->$titleElement;
					$str .= "			<title>" . $title . "</title>\n";
				}

				if (!empty($imageElement))
				{
					$image = $row->$imageElement;

					if (!empty($image))
					{
						$image = str_replace('\\', '/', $image);
						$image = JString::ltrim($image, '/');
						$image = COM_FABRIK_LIVESITE . $image;
						$str .= "			<image>" . $image . "</image>\n";
					}
				}

				if (!empty($noteElement))
				{
					$note = $row->$noteElement;
					$str .= "			<annotation>" . $note . "</annotation>\n";
				}

				if (!empty($infoElement))
				{
					$link = $row->$titleElement;
					$str .= "			<info>" . $link . "</info>\n";
				}
				else
				{
					$link = JRoute::_('index.php?option=com_' . $this->package . '&view=form&formid=' . $form->getId() . '&rowid=' . $row->__pk_val);
					$str .= "			<info>" . $link . "</info>\n";
				}

				$str .= "		</track>\n";
			}
		}

		$str .= "	</trackList>\n";
		$str .= "</playlist>\n";

		return $str;
	}

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
					$picData = $pic->$slideshow_viz_file;
				}

				$picData = str_replace("\\", "/", $picData);
				$pic_opts = array();

				if (!empty($slideshow_viz_caption) && isset($pic->$slideshow_viz_caption))
				{
					// Force it to a string for json_encode
					$pic_opts['caption'] = $pic->$slideshow_viz_caption . ' ';
				}


				/**
				 * AJAX uploads will (hopefully!) have been CONCAT'ed into the parent element
				 * with the //..*..// group splitter.
				 */
				foreach (explode(GROUPSPLITTER, $picData) as $path)
				{
					$tmp = json_decode($path);
					$k = $tmp == false ? $path : $tmp[0];
					$pic_opts['href'] = $slideElement->getStorage()->getFileUrl($k, 0);
					$this->addThumbOpts($pic_opts);

					if (!empty($k))
					{
						$js_opts->$k = $pic_opts;
					}
				}
			}
		}

		$this->totalPics = count($js_opts);

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
		$opts->slideshow_data = $slideshow_data = $this->getImageJSData();
		$opts->id = $viz->id;
		$opts->html_id = $html_id = 'slideshow_viz_' . $viz->id;
		$opts->slideshow_type = (int) $params->get('slideshow_viz_type', 1);
		$opts->slideshow_width = (int) $params->get('slideshow_viz_width', 400);
		$opts->slideshow_height = (int) $params->get('slideshow_viz_height', 300);
		$opts->slideshow_delay = (int) $params->get('slideshow_viz_delay', 5000);
		$opts->slideshow_duration = (int) $params->get('slideshow_viz_duration', 2000);
		$opts->slideshow_zoom = (int) $params->get('slideshow_viz_zoom', 50);
		$opts->slideshow_pan = (int) $params->get('slideshow_viz_pan', 20);
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
