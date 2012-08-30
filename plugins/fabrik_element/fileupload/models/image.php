<?php
/**
 * Plugin element to render fields
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class imageRender
{

	var $output = '';

	var $inTableView = false;
	/**
	 * @param object element model
	 * @param object element params
	 * @param string row data for this element
	 * @param object all row's data
	 */

	function renderListData(&$model, &$params, $file, $thisRow)
	{
		$this->inTableView = true;
		$this->render($model, $params, $file, $thisRow);
	}

	/**
	 * @param object element model
	 * @param object element params
	 * @param string row data for this element
	 * @param object all row's data
	 */

	function render(&$model, &$params, $file, $thisRow = null)
	{
		// $$$ hugh - added this hack to let people use elementname__title as a title element
		// for the image, to show in the lightbox popup.
		// So we have to work out if we're being called from a table or form
		$title = basename($file);
		if ($params->get('fu_title_element') == '')
		{
			$title_name = $model->getFullName(true, true, false) . '__title';
		}
		else
		{
			$title_name = str_replace('.', '___', $params->get('fu_title_element'));
		}
		if (JRequest::getVar('view') == 'list')
		{
			$listModel = $model->getlistModel();
			if (array_key_exists($title_name, $thisRow))
			{
				$title = $thisRow->$title_name;

			}
		}
		else
		{
			if (is_object($model->_form))
			{
				if (is_array($model->_form->_data))
				{
					$group = $model->getGroup();
					if ($group->isJoin())
					{
						$join_id = $group->getGroup()->join_id;
						if (isset($model->_form->_data['join']))
						{
							if (array_key_exists($join_id, $model->_form->_data['join']))
							{
								if (array_key_exists($title_name, $model->_form->_data['join'][$join_id]))
								{
									if (array_key_exists($model->_repeatGroupCounter, $model->_form->_data['join'][$join_id][$title_name]))
									{
										$title = $model->_form->_data['join'][$join_id][$title_name][$model->_repeatGroupCounter];
									}
								}
							}
						}
					}
					else
					{
						if (array_key_exists($title_name, $model->_form->_data))
						{
							$title = $model->_form->_data[$title_name];
						}
					}
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
		if ($model->isJoin())
		{
			$this->output .= '<div class="fabrikGalleryImage" style="width:' . $width . 'px;height:' . $height
				. 'px; vertical-align: middle;text-align: center;">';
		}
		$img = '<img class="fabrikLightBoxImage" src="' . $file . '" alt="' . strip_tags($element->label) . '" />';
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

