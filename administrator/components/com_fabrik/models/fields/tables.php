<?php
/**
 * Renders a list of tables, either fabrik lists, or db tables
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
			$options[] = JHTML::_('select.option', null, null);

			foreach ($items as $l)
			{
				$options[] = JHTML::_('select.option', $l, $l);
			}
		}
		else
		{
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
				'Tables' => 'administrator/components/com_fabrik/models/fields/tables.js'
			);
			FabrikHelperHTML::script($src, implode("\n", $script));

			$this->value = '';
		}

		$html = parent::getInput();
		$html .= "<img style='margin-left:10px;display:none' id='" . $this->id . "_loader' src='components/com_fabrik/images/ajax-loader.gif' alt='"
			. FText::_('LOADING') . "' />";
		FabrikHelperHTML::framework();
		FabrikHelperHTML::iniRequireJS();

		return $html;
	}
}
