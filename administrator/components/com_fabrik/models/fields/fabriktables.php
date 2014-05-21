<?php
/**
 * Renders a list of fabrik lists or db tables
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Required for menus
require_once JPATH_SITE . '/components/com_fabrik/helpers/html.php';
require_once JPATH_SITE . '/components/com_fabrik/helpers/string.php';
require_once JPATH_SITE . '/components/com_fabrik/helpers/parent.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Renders a list of fabrik lists or db tables
 *
 * @package     Fabrik
 * @subpackage  Form
 * @since       3.0
 */

class JFormFieldFabrikTables extends JFormFieldList
{
	/**
	 * Element name
	 * @var		string
	 */
	protected $name = 'Fabriktables';

	/**
	 * Fabrik lists
	 *
	 * @var  array
	 */
	protected static $fabriktables;

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */

	protected function getOptions()
	{
		$app = JFactory::getApplication();

		if (!isset($fabriktables))
		{
			$fabriktables = array();
		}

		$connectionDd = $this->element['observe'];
		$db = FabrikWorker::getDbo(true);
		$id = $this->id;

		if ($this->element['package'])
		{
			$package = $app->setUserState('com_fabrik.package', $this->element['package']);
		}

		$fullName = $this->name;

		if ($connectionDd == '')
		{
			// We are not monitoring a connection drop down so load in all tables
			$query = $db->getQuery(true);
			$query->select('id AS value, label AS text')->from('#__{package}_lists')->where('published <> -2')->order('label ASC');
			$db->setQuery($query);
			$rows = $db->loadObjectList();
		}
		else
		{
			$rows = array(JHTML::_('select.option', '', FText::_('COM_FABRIK_SELECT_A_CONNECTION_FIRST'), 'value', 'text'));
		}

		return $rows;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string	The field input markup.
	 */

	protected function getInput()
	{
		$c = isset($this->form->repeatCounter) ? (int) $this->form->repeatCounter : 0;
		$connectionDd = $this->element['observe'];

		if (!isset($fabriktables))
		{
			$fabriktables = array();
		}

		$script = array();

		if ($connectionDd != '' && !array_key_exists($this->id, $fabriktables))
		{
			$repeatCounter = empty($this->form->repeatCounter) ? 0 : $this->form->repeatCounter;

			if ($this->form->repeat)
			{
				// In repeat fieldset/group
				$connectionDd = $connectionDd . '-' . $repeatCounter;
			}
			else
			{
				$connectionDd = ($c === false || $this->element['connection_in_repeat'] == 'false') ? $connectionDd : $connectionDd . '-' . $c;
			}

			$opts = new stdClass;
			$opts->livesite = COM_FABRIK_LIVESITE;
			$opts->conn = 'jform_' . $connectionDd;

			$opts->value = $this->value;
			$opts->connInRepeat = (bool) $this->element['connection_in_repeat'][0];
			$opts->inRepeatGroup = $this->form->repeat;
			$opts->repeatCounter = $repeatCounter;
			$opts->container = 'test';
			$opts = json_encode($opts);
			$script[] = "var p = new fabriktablesElement('$this->id', $opts);";
			$script[] = "FabrikAdmin.model.fields.fabriktable['$this->id'] = p;";

			$fabriktables[$this->id] = true;
			$src[] = 'media/com_fabrik/js/fabrik.js';
			$src[] = 'administrator/components/com_fabrik/views/namespace.js';
			$src[] = 'administrator/components/com_fabrik/models/fields/fabriktables.js';
			FabrikHelperHTML::script($src, $script);
		}

		$script = implode("\n", $script);

		$html = parent::getInput();
		$html .= '<img style="margin-left:10px;display:none" id="' . $this->id . '_loader" src="components/com_fabrik/images/ajax-loader.gif" alt="'
			. FText::_('LOADING') . '" />';
		$script = '<script type="text/javascript">' . $script . '</script>';
		FabrikHelperHTML::framework();
		FabrikHelperHTML::iniRequireJS();

		return $html;
	}
}
