<?php
/**
 * Fileupload adaptor to render uploaded images
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Fileupload adaptor to render uploaded images
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       3.0
 */
class ImageRenderModel
{
	/**
	 * Render output
	 *
	 * @var  string
	 */
	public $output = '';

	/**
	 * In list view
	 *
	 * @var bool
	 */
	protected $inTableView = false;

	/**
	 * Render list data
	 *
	 * @param   object &$model  Element model
	 * @param   object &$params Element params
	 * @param   string $file    Row data for this element
	 * @param   object $thisRow All rows data
	 *
	 * @return  void
	 */

	public function renderListData(&$model, &$params, $file, $thisRow)
	{
		$this->inTableView = true;
		$this->render($model, $params, $file, $thisRow);
	}

	/**
	 * Render uploaded image
	 *
	 * @param   object &$model  Element model
	 * @param   object &$params Element params
	 * @param   string $file    Row data for this element
	 * @param   object $thisRow All row's data
	 *
	 * @return  void
	 */

	public function render(&$model, &$params, $file, $thisRow = null)
	{
		/*
		 * $$$ hugh - added this hack to let people use elementname__title as a title element
		 * for the image, to show in the lightbox popup.
		 * So we have to work out if we're being called from a table or form
		 */
		$formModel = $model->getFormModel();
		$listModel = $model->getListModel();
		$title     = basename($file);

		if ($params->get('fu_title_element') == '')
		{
			$title_name = $model->getFullName(true, false) . '__title';
		}
		else
		{
			$title_name = str_replace('.', '___', $params->get('fu_title_element'));
		}

		if ($this->inTableView)
		{
			if (isset($thisRow->{$title_name}))
			{
				$title = $thisRow->$title_name;
			}
		}
		else
		{
			if (is_object($formModel))
			{
				if (is_array($formModel->data))
				{
					$title = FArrayHelper::getValue($formModel->data, $title_name, '');
				}
			}
		}

		$bits  = FabrikWorker::JSONtoData($title, true);
		$title = FArrayHelper::getValue($bits, $model->_repeatGroupCounter, $title);
		$title = htmlspecialchars(strip_tags($title, ENT_NOQUOTES));
		$file  = $model->getStorage()->getFileUrl($file);

		$fullSize = $file;

		if (!$this->fullImageInRecord($params))
		{
			if ($params->get('fileupload_crop'))
			{
				$file = $model->getStorage()->_getCropped($fullSize);
			}
			else
			{
				$file = $model->getStorage()->_getThumb($file);
			}
		}

		list($width, $height) = $this->imageDimensions($params);

		$file = $model->storage->preRenderPath($file);

		$n = $this->inTableView ? '' : $model->getElement()->name;

		if ($params->get('restrict_lightbox', 1) == 0)
		{
			$n = '';
		}

		$layout                     = $model->getLayout('image');
		$displayData                = new stdClass;
		$displayData->lightboxAttrs = FabrikHelperHTML::getLightboxAttributes($title, $n);
		$displayData->fullSize      = $model->storage->preRenderPath($fullSize);
		$displayData->file          = $file;
		$displayData->makeLink      = $params->get('make_link', true)
			&& !$this->fullImageInRecord($params)
			&& $listModel->getOutPutFormat() !== 'feed';
		$displayData->title         = $title;
		$displayData->isJoin        = $model->isJoin();
		$displayData->width         = $width;
		$displayData->showImage     = $params->get('fu_show_image');
		$displayData->inListView    = $this->inTableView;
		$displayData->height        = $height;
		$displayData->isSlideShow   = ($this->inTableView && $params->get('fu_show_image_in_table', '0') == '2')
			|| (!$this->inTableView && !$formModel->isEditable() && $params->get('fu_show_image', '0') == '3');

		$this->output = $layout->render($displayData);
	}

	/**
	 * Get the image width / height
	 *
	 * @param   JParameter $params Params
	 *
	 * @since   3.1rc2
	 *
	 * @return  array ($width, $height)
	 */
	private function imageDimensions($params)
	{
		$width  = $params->get('fu_main_max_width');
		$height = $params->get('fu_main_max_height');

		if (!$this->fullImageInRecord($params))
		{
			if ($params->get('fileupload_crop'))
			{
				$width  = $params->get('fileupload_crop_width');
				$height = $params->get('fileupload_crop_height');
			}
			else
			{
				$width  = $params->get('thumb_max_width');
				$height = $params->get('thumb_max_height');
			}
		}

		return array($width, $height);
	}

	/**
	 * When in form or detailed view, do we want to show the full image or thumbnail/link?
	 *
	 * @param   object &$params params
	 *
	 * @return  bool
	 */
	private function fullImageInRecord(&$params)
	{
		if ($this->inTableView)
		{
			if ($params->get('fu_show_image_in_table') === '2')
			{
				return true;
			}

			return ($params->get('make_thumbnail') || $params->get('fileupload_crop')) ? false : true;
		}

		if (($params->get('make_thumbnail') || $params->get('fileupload_crop')) && $params->get('fu_show_image') == 1)
		{
			return false;
		}

		return true;
	}

	/**
	 * Build Carousel HTML
	 *
	 * @param   string $id      Widget HTML id
	 * @param   array  $data    Images to add to the carousel
	 * @param   \PlgFabrik_ElementFileupload $model   Element model
	 * @param   object $params  Element params
	 * @param   object $thisRow All rows data
	 *
	 * @return  string  HTML
	 */
	public function renderCarousel($id = 'carousel', $data = array(), $model = null, $params = null, $thisRow = null, $nav = true)
	{
		$this->inTableView = true;
		$storage = $model->getStorage();
		$id .= '_carousel';
		$layout         = $model->getLayout('slick-carousel');
		$layoutData     = new stdClass;
		$layoutData->id = $id;
		list($layoutData->width, $layoutData->height) = $this->imageDimensions($params);
		$imgs = array();
		$thumbs = array();

		if (!empty($data))
		{
			$imgs = array();
			$i    = 0;

			foreach ($data as $img)
			{
				$img = str_replace('\\', '/', $img);
				$model->_repeatGroupCounter = $i++;
				$this->renderListData($model, $params, $img, $thisRow);
				$imgs[] = $this->output;
				$showImage = $params->get('fu_show_image_in_table');
				$params->set('fu_show_image_in_table', '2');
				$this->renderListData($model, $params, $storage->_getThumb($img), $thisRow);
				$params->set('fu_show_image_in_table', $showImage);
				$thumbs[] = $this->output;
			}
		}

		$layoutData->imgs = $imgs;
		$layoutData->thumbs = $thumbs;
		$layoutData->nav = $nav;

		return $layout->render($layoutData);
	}
}
