<?php
/**
 * Fileupload adaptor to render uploaded images
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
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

class ImageRender
{

	/**
	 * Render output
	 *
	 * @var  string
	 */
	public $output = '';

	var $inTableView = false;

	/**
	 * Render list data
	 *
	 * @param   object  &$model   Element model
	 * @param   object  &$params  Element params
	 * @param   string  $file     Row data for this element
	 * @param   object  $thisRow  All rows data
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
	 * @param   object  &$model   Element model
	 * @param   object  &$params  Element params
	 * @param   string  $file     Row data for this element
	 * @param   object  $thisRow  All row's data
	 *
	 * @return  void
	 */

	public function render(&$model, &$params, $file, $thisRow = null)
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		/*
		 * $$$ hugh - added this hack to let people use elementname__title as a title element
		 * for the image, to show in the lightbox popup.
		 * So we have to work out if we're being called from a table or form
		 */
		$formModel = $model->getFormModel();
		$title = basename($file);
		if ($params->get('fu_title_element') == '')
		{
			$title_name = $model->getFullName(true, false) . '__title';
		}
		else
		{
			$title_name = str_replace('.', '___', $params->get('fu_title_element'));
		}
		if ($input->get('view') == 'list')
		{
			$listModel = $model->getlistModel();
			if (array_key_exists($title_name, $thisRow))
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
					$title = JArrayHelper::getValue($formModel->data, $title_name, '');
				}
			}
		}

		$bits = FabrikWorker::JSONtoData($title, true);
		$title = JArrayHelper::getValue($bits, $model->_repeatGroupCounter, $title);
		$title = htmlspecialchars(strip_tags($title, ENT_NOQUOTES));
		$element = $model->getElement();
		$file = $model->getStorage()->getFileUrl($file);

		$fullSize = $file;
		$width = $params->get('fu_main_max_width');
		$height = $params->get('fu_main_max_height');
		if (!$this->fullImageInRecord($params))
		{
			if ($params->get('fileupload_crop'))
			{
				$width = $params->get('fileupload_crop_width');
				$height = $params->get('fileupload_crop_height');
				$file = $model->getStorage()->_getCropped($fullSize);
			}
			else
			{
				$width = $params->get('thumb_max_width');
				$height = $params->get('thumb_max_height');
				$file = $model->getStorage()->_getThumb($file);
			}
		}
		$file = $model->storage->preRenderPath($file);
		$fullSize = $model->storage->preRenderPath($fullSize);
		if ($params->get('fu_show_image') == 0 && !$this->inTableView)
		{
			$fileName = explode("/", $file);
			$fileName = array_pop($fileName);
			$this->output .= '<a href="' . $fullSize . '">' . $fileName . '</a>';
		}
		else
		{
			if (($this->inTableView && $params->get('fu_show_image_in_table', '0') == '2')
				|| (!$this->inTableView && !$formModel->isEditable() && $params->get('fu_show_image', '0') == '3'))
			{
				/*
				 * We're building a Bootstrap slideshow, just a simple img tag
				 */
				$this->output = '<img src="' . $fullSize . '" alt="' . $title . '" />';
			}
			else
			{
				if ($model->isJoin())
				{
					$this->output .= '<div class="fabrikGalleryImage" style="width:' . $width . 'px;height:' . $height
						. 'px; vertical-align: middle;text-align: center;">';
				}
				$img = '<img class="fabrikLightBoxImage" src="' . $file . '" alt="' . $title . '" />';
				if ($params->get('make_link', true) && !$this->fullImageInRecord($params))
				{
					$this->output .= '<a href="' . $fullSize . '" rel="lightbox[]" title="' . $title . '">' . $img . '</a>';
				}
				else
				{
					$this->output .= $img;
				}
				if ($model->isJoin())
				{
					$this->output .= '</div>';
				}
			}
		}
	}

	/**
	 * When in form or detailed view, do we want to show the full image or thumbnail/link?
	 *
	 * @param   object  &$params  params
	 *
	 * @return  bool
	 */

	private function fullImageInRecord(&$params)
	{
		if ($this->inTableView)
		{
			return ($params->get('make_thumbnail') || $params->get('fileupload_crop')) ? false : true;
		}
		if (($params->get('make_thumbnail') || $params->get('fileupload_crop')) && $params->get('fu_show_image') == 1)
		{
			return false;
		}
		return true;
	}

}
