<?php
/**
 * Get a list of templates - either in components/com_fabrik/views/{view}/tmpl or {view}/tmpl25
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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
		$path = JPATH_ROOT . DIRECTORY_SEPARATOR . $this->element['directory'];

		$path = str_replace('\\', '/', $path);
		$path = str_replace('//', '/', $path);


		$this->element['directory'] = $this->directory = $path;

        $options = array();
        $path = JPath::clean($path);

        // Prepend some default options based on field attributes.
        if (!$this->hideNone)
        {
            $options[] = JHtml::_('select.option', '-1', JText::alt('JOPTION_DO_NOT_USE', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
        }

        if (!$this->hideDefault)
        {
            $options[] = JHtml::_('select.option', '', JText::alt('JOPTION_USE_DEFAULT', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
        }

        // Get a list of folders in the search path with the given filter.
        $folders = JFolder::folders($path, $this->filter, $this->recursive, true);

        // Build the options list from the list of folders.
        if (is_array($folders))
        {
            foreach ($folders as $folder)
            {
                // Check to see if the file is in the exclude mask.
                if ($this->exclude)
                {
                    if (preg_match(chr(1) . $this->exclude . chr(1), $folder))
                    {
                        continue;
                    }
                }

                // Remove the root part and the leading /
                $folder = trim(str_replace($path, '', $folder), '/');

                $options[] = JHtml::_('select.option', $folder, $folder);
            }
        }

		foreach ($options as &$opt)
		{
			$opt->value = str_replace('\\', '/', $opt->value);
			$opt->value = str_replace('//', '/', $opt->value);
			$opt->value = str_replace($path, '', $opt->value);
			$opt->text = str_replace('\\', '/', $opt->text);
			$opt->text = str_replace('//', '/', $opt->text);
			$opt->text = str_replace($path, '', $opt->text);
		}

		return $options;
	}
}
