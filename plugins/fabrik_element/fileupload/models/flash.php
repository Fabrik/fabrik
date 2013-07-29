<?php
/**
 * Fileupload - Plugin element to render Flash files
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Fileupload - Plugin element to render Flash files
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       3.0
 */

class FlashRender
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

	function render(&$model, &$params, $file)
	{
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		ini_set('display_errors', true);
		require_once COM_FABRIK_FRONTEND . '/libs/getid3/getid3/getid3.php';
		require_once COM_FABRIK_FRONTEND . '/libs/getid3/getid3/getid3.lib.php';

		getid3_lib::IncludeDependency(COM_FABRIK_FRONTEND . '/libs/getid3/getid3/extension.cache.mysql.php', __FILE__, true);
		$config = JFactory::getConfig();
		$host = $config->get('host');
		$database = $config->get('db');
		$username = $config->get('user');
		$password = $config->get('password');
		$getID3 = new getID3_cached_mysql($host, $database, $username, $password);

		// Analyze file and store returned data in $ThisFileInfo
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
		// $$$ hugh - if they've enabled thumbnails, for Flash content we'll take that to mean they don't
		// want to play the content inline in the table, and use mediabox (if available) to open it instead.
		if (!$model->_inDetailedView && $fbConfig->get('use_mediabox', true) && $params->get('make_thumbnail', false))
		{
			$element = $model->getElement();

			// @TODO - work out how to do thumbnails
			$thumb_dir = $params->get('thumb_dir');
			if (!empty($thumb_dir))
			{
				$file = str_replace("\\", "/", $file);
				$pathinfo = pathinfo($file);

				// $$$ hugh - apparently filename ocnstant only added in PHP 5.2
				if (!isset($pathinfo['filename']))
				{
					$pathinfo['filename'] = explode('.', $pathinfo['basename']);
					$pathinfo['filename'] = $pathinfo['filename'][0];
				}
				$thumb_path = COM_FABRIK_BASE . $thumb_dir . '/' . $pathinfo['filename'] . '.png';
				if (JFile::exists($thumb_path))
				{
					$thumb_file = COM_FABRIK_LIVESITE . '/' . $thumb_dir . '/' . $pathinfo['filename'] . '.png';
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
			$this->output .= "<a href='$file' rel='lightbox[flash $w $h]'><img src='$thumb_file' alt='Full Size'></a>";
		}
		elseif ($model->_inDetailedView)
		{
			$file = str_replace("\\", "/", COM_FABRIK_LIVESITE . $file);
			$this->output = "<object width=\"$w\" height=\"$h\">
				<param name=\"movie\" value=\"$file\">
				<embed src=\"$file\" width=\"$w\" height=\"$h\">
				</embed>
				</object>";
		}
		else
		{
			$file = str_replace("\\", "/", COM_FABRIK_LIVESITE . $file);
			$this->output = "<object width=\"$w\" height=\"$h\">
				<param name=\"movie\" value=\"$file\">
				<embed src=\"$file\" width=\"$w\" height=\"$h\">
				</embed>
				</object>";
		}
	}
}
