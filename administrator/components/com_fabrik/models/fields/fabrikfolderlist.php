<?php
/**
 * Get a list of templates - either in components/com_fabrik/views/{view}/tmpl or {view}/tmpl25
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
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
class JFormFieldFabrikFolderlist extends JFormFieldFolderList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	public $type = 'FabrikFolderlist';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		$dir = JPATH_ROOT . DIRECTORY_SEPARATOR . $this->element['directory'];

		$dir = str_replace('\\', '/', $dir);
		$dir = str_replace('//', '/', $dir);


		$this->element['directory'] = $this->directory = $dir;


		$opts = parent::getOptions();

		foreach ($opts as &$opt)
		{
			$opt->value = str_replace('\\', '/', $opt->value);
			$opt->value = str_replace('//', '/', $opt->value);
			$opt->value = str_replace($dir, '', $opt->value);
			$opt->text = str_replace('\\', '/', $opt->text);
			$opt->text = str_replace('//', '/', $opt->text);
			$opt->text = str_replace($dir, '', $opt->text);
		}

		return $opts;
	}
}
