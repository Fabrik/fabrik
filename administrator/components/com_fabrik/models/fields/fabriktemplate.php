<?php
/**
 * Get a list of templates - either in components/com_fabrik/views/{view}/tmpl or {view}/tmpl25
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('folderlist');

/**
 * Get a list of templates - either in components/com_fabrik/views/{view}/tmpl or {view}/tmpl25
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       3.1b
 */

class JFormFieldFabrikTemplate extends JFormFieldFolderList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	public $type = 'FabrikTemplate';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */

	protected function getOptions()
	{
		$view = $this->element['view'] ? $this->element['view'] : 'list';
		if (FabrikWorker::j3())
		{
			$this->element['directory'] = '/components/com_fabrik/views/' . $view . '/tmpl/';
		}
		else
		{
			$this->element['directory'] = '/components/com_fabrik/views/' . $view . '/tmpl25/';
		}
		return parent::getOptions();
	}
}
