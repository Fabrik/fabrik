<?php
/**
 * Fileupload adaptor to render audio play
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Fileupload adaptor to render audio play
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       3.0
 */

class AudioRender
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
		$file = str_replace("\\", "/", COM_FABRIK_LIVESITE . $file);
		$this->output = "<embed src=\"$file\" autostart=\"false\" playcount=\"true\" loop=\"false\" height=\"50\" width=\"200\">";
	}
}
