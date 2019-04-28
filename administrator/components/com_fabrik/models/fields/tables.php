<?php
/**
 * Renders a list of tables, either fabrik lists, or db tables
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
JFormHelper::loadFieldClass('list');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

/**
 * Renders a list of tables, either fabrik lists, or db tables
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */
class JFormFieldTables extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 */
	protected $name = 'Tables';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */

	protected function getOptions()
	{
		$connectionDd   = $this->element['observe'];
		$connectionName = 'connection_id';
		$mode			= $this->getAttribute('mode');
		$connId         = (int) $this->form->getValue($connectionName);
		$options        = array();
		$db             = FabrikWorker::getDbo(true);

		// DB join element observes 'params___join_conn_id'
		if (strstr($connectionDd, 'params_') && $connId === 0)
		{
			$connectionName = str_replace('params_', 'params.', $connectionDd);
			$connId         = (int) $this->form->getValue($connectionName);
		}

		if ($connectionDd == '')
		{
			// We are not monitoring a connection drop down so load in all tables
			$query = "SHOW TABLES";
			$db->setQuery($query);
			$items     = $db->loadColumn();
			if ($mode !== 'combo') 
			{
				$options[] = JHTML::_('select.option', null, null);
			}

			foreach ($items as $l)
			{
				$options[] = $mode === 'combo' ? '<li><a href="#">' . $l . '</a></li>' : JHTML::_('select.option', $l, $l);
			}
		}
		else
		{
			if ($mode === 'combo')
			{
				// We are not monitoring a connection drop down so load in all tables
				$query = "SHOW TABLES";
				$db->setQuery($query);
				$items     = $db->loadColumn();
				foreach ($items as $l)
				{
					$options[] = '<li><a href="#">' . $l . '</a></li>';
				}
			}
			// Delay for the connection to trigger an update via js.
		}

		return $options;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string    The field input markup.
	 */

	protected function getInput()
	{
		$app          = JFactory::getApplication();
		$format       = $app->input->get('format', 'html');
		$connectionDd = $this->element['observe'];
		$mode         = (string) $this->getAttribute('mode', false);
		$html = array();

		if ((int) $this->form->getValue('id') != 0 && $this->element['readonlyonedit'])
		{
			return '<input type="text" value="' . $this->value . '" class="readonly" name="' . $this->name . '" readonly="true" />';
		}
		
		$c              = FabrikAdminElementHelper::getRepeatCounter($this);
		$readOnlyOnEdit = $this->element['readonlyonedit'];

		if ($connectionDd != '')
		{
			$connectionDd   = ($c === false) ? $connectionDd : $connectionDd . '-' . $c;
			$opts           = new stdClass;
			$opts->livesite = COM_FABRIK_LIVESITE;
			$opts->conn     = 'jform_' . $connectionDd;
			$opts->value    = $this->value;
			$opts           = json_encode($opts);
			$script[]       = "FabrikAdmin.model.fields.fabriktable['$this->id'] = new tablesElement('$this->id', $opts);\n";
			$src            = array(
				'Fabrik' => 'media/com_fabrik/js/fabrik.js',
				'Namespace' => 'administrator/components/com_fabrik/views/namespace.js',
				'Tables' => 'administrator/components/com_fabrik/models/fields/tables.js',
				'Combo' => 'media/system/js/combobox.js'
			);
			FabrikHelperHTML::script($src, implode("\n", $script));

			if ($mode !== 'combo') $this->value = '';
		}

		if ($mode === 'combo')
		{
			$attr = '';

			// Initialize some field attributes.
			$attr .= !empty($this->class) ? ' class="combobox ' . $this->class . '"' : ' class="combobox"';
			$attr .= $this->readonly ? ' readonly' : '';
			$attr .= $this->disabled ? ' disabled' : '';
			$attr .= !empty($this->size) ? ' size="' . $this->size . '"' : '';
			$attr .= $this->required ? ' required aria-required="true"' : '';

			// Initialize JavaScript field attributes.
			$attr .= $this->onchange ? ' onchange="' . $this->onchange . '"' : '';

			// Get the field options.
			$options = $this->getOptions();

			// Load the combobox behavior.
			JHtml::_('behavior.combobox');

			$html[] = '<div class="combobox input-append">';

			// Build the input for the combo box.
			$html[] = '<input type="text" name="' . $this->name . '" id="' . $this->id . '" value="'
				. htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"' . $attr . ' autocomplete="off" />';

			$html[] = '<div class="btn-group">';
			$html[] = '<button type="button" class="btn dropdown-toggle">';
			$html[] = '		<span class="caret"></span>';
			$html[] = '</button>';

			// Build the list for the combo box.
			$html[] = '<ul class="dropdown-menu">';
			foreach ($options as $option)
			{
				$html[] = $option;
			}
			$html[] = '</ul>';

			$html[] = '</div></div>';

		}
		else
		{
			$html[] = parent::getInput();
		}
		
		$html[] = "<img style='margin-left:10px;display:none' id='" . $this->id . "_loader' src='components/com_fabrik/images/ajax-loader.gif' alt='"
			. FText::_('LOADING') . "' />";
		FabrikHelperHTML::framework();
		FabrikHelperHTML::iniRequireJS();

		return implode("\n", $html);
	}
}
