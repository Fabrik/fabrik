<?php
/**
 * Fabrik Cron View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * Cron view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0.6
 */
class FabrikViewCron extends FabrikView
{
	/**
	 * Display
	 *
	 * @param   string $tmpl Template
	 *
	 * @return  void
	 */

	public function display($tmpl = 'default')
	{
		$srcs  = FabrikHelperHTML::framework();
		$input = $this->app->input;
		FabrikHelperHTML::script($srcs);
		$model       = $this->getModel();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$visualization = $model->getVisualization();
		$pluginParams  = $model->getPluginParams();

		$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikModel');
		$plugin        = $pluginManager->getPlugIn($visualization->plugin, 'visualization');
		$plugin->_row  = $visualization;

		if ($visualization->published == 0)
		{
			$this->app->enqueueMessage(FText::_('COM_FABRIK_SORRY_THIS_VISUALIZATION_IS_UNPUBLISHED'), 'warning');

			return '';
		}

		// Plugin is basically a model
		$pluginTask = $input->get('plugintask', 'render', 'request');

		// @FIXME cant set params directly like this, but I think plugin model setParams() is not right
		$plugin->_params = $pluginParams;
		$tmpl            = $plugin->getParams()->get('calendar_layout', $tmpl);
		$plugin->$pluginTask($this);
		$this->plugin = $plugin;
		$this->addTemplatePath($this->_basePath . '/plugins/' . $this->_name . '/' . $plugin->_name . '/tmpl/' . $tmpl);
		$root = $this->app->isAdmin() ? JPATH_ADMINISTRATOR : JPATH_SITE;
		$this->addTemplatePath($root . '/templates/' . $this->app->getTemplate() . '/html/com_fabrik/visualization/' . $plugin->_name . '/' . $tmpl);
		$ab_css_file = JPATH_SITE . '/plugins/fabrik_visualization/' . $plugin->_name . '/tmpl/' . $tmpl . '/template.css';

		if (JFile::exists($ab_css_file))
		{
			JHTML::stylesheet('template.css', 'plugins/fabrik_visualization/' . $plugin->_name . '/tmpl/' . $tmpl . '/', true);
		}

		echo parent::display();
	}

	/**
	 * Just for plugin
	 *
	 * @return  void
	 */
	public function setId()
	{
	}
}
