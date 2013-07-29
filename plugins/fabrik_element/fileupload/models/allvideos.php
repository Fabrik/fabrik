<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Fileupload adaptor to render allvideos
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       3.0
 */

class AllVideosRender
{

	/**
	 * Render output
	 *
	 * @var  string
	 */
	public $output = '';

	var $inTableView = false;

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
		$this->inTableView = true;
		$this->render($model, $params, $file);
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
		$src = str_replace("\\", "/", COM_FABRIK_LIVESITE . $file);
		$ext = JString::strtolower(JFile::getExt($file));
		if (!JPluginHelper::isEnabled('content', 'jw_allvideos'))
		{
			$this->output = JText::_(
				'to display this media files types you need to install the all videos plugin - http://www.joomlaworks.gr/content/view/35/41/');
			return;
		}
		$extra = array();
		$extra[] = $src;
		if ($this->inTableView || $params->get('fu_show_image') < 2)
		{
			$extra[] = $params->get('thumb_max_width');
			$extra[] = $params->get('thumb_max_height');
		}
		else
		{
			$extra[] = $params->get('fu_main_max_width');
			$extra[] = $params->get('fu_main_max_height');
		}
		$src = implode('|', $extra);
		switch ($ext)
		{
			case 'flv':
				$this->output = "{flvremote}$src{/flvremote}";
				break;
			case '3gp':
				$this->output = "{3gpremote}$src{/3gpremote}";
				break;
			case 'divx':
				$this->output = "{divxremote}$src{/divxremote}";
				break;
		}
		/*if (!JPluginHelper::isEnabled( 'content', 'fab_jwplayer')) {


		    } else {

		    }
		} else {
		    $this->output = '{fabjwplayer file='.$src.'}';
		}*/
	}
}
