<?php
/**
 * Renders a repeating drop down list of forms
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Renders a repeating drop down list of forms
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldFormList extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $name = 'Formlist';

	/**
	 * Method to get the field options.
	 *
	 * @return  array	The field option objects.
	 */

	protected function getOptions()
	{
		$app = JFactory::getApplication();

		if ($this->element['package'])
		{
			$package = $app->setUserState('com_fabrik.package', $this->element['package']);
		}

		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('id AS value, label AS ' . $db->quote('text') . ', published');
		$query->from('#__{package}_forms');

		if (!$this->element['showtrashed'])
		{
			$query->where('published <> -2');
		}

		$query->order('published DESC, label ASC');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		foreach ($rows as &$row)
		{
			switch ($row->published)
			{
				case '0':
					$row->text .= ' [' . FText::_('JUNPUBLISHED') . ']';
					break;
				case '-2':
					$row->text .= ' [' . FText::_('JTRASHED') . ']';
					break;
			}
		}

		$o = new stdClass;
		$o->value = '';
		$o->text = '';
		array_unshift($rows, $o);

		return $rows;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 */

	protected function getInput()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$option = $input->get('option');

		if (!in_array($option, array('com_modules', 'com_menus', 'com_advancedmodules')))
		{
			$db = FabrikWorker::getDbo(true);
			$query = $db->getQuery(true);
			$query->select('form_id')->from('#__{package}_formgroup')->where('group_id = ' . (int) $this->form->getValue('id'));
			$db->setQuery($query);
			$this->value = $db->loadResult();
			$this->form->setValue('form', null, $this->value);
		}

		if ((int) $this->form->getValue('id') == 0 || !$this->element['readonlyonedit'])
		{
			return parent::getInput();
		}
		else
		{
			$options = (array) $this->getOptions();
			$v       = '';

			foreach ($options as $opt)
			{
				if ($opt->value == $this->value)
				{
					$v = $opt->text;
				}
			}
		}

		return '<input type="hidden" value="' . $this->value . '" name="' . $this->name . '" />' . '<input type="text" value="' . $v
		. '" name="form_justalabel" class="readonly" readonly="true" />';
	}
}
