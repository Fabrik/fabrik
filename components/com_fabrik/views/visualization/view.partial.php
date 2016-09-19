<?php
/**
 * Visualization View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * HTML Partial Fabrik Visualization view class. Renders HTML without <head> or wrapped in <body>
 * Any Ajax request requiring HTML should add "&foramt=partial" to the URL. This avoids us
 * potentially reloading jQuery in the <head> which is problematic as that replaces the main page's
 * jQuery object and removes any additional functions that had previously been assigned
 * such as JQuery UI, or fullcalendar
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.4.3
 */
class FabrikViewVisualization extends FabrikView
{
	/**
	 * Display
	 *
	 * @param   string  $tmpl  Template
	 *
	 * @return  void
	 */
	public function display($tmpl = 'default')
	{
		$srcs = FabrikHelperHTML::framework();
		$input = $this->app->input;
		FabrikHelperHTML::script($srcs);
		$model = $this->getModel();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model->setId($input->get('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$visualization = $model->getVisualization();
		$params = $model->getParams();
		$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		$plugin = $pluginManager->getPlugIn($visualization->plugin, 'visualization');
		$plugin->setRow($visualization);

		if ($visualization->published == 0)
		{
			$this->app->enqueueMessage(FText::_('COM_FABRIK_SORRY_THIS_VISUALIZATION_IS_UNPUBLISHED'), 'error');
			return;
		}

		// Plugin is basically a model
		$pluginTask = $input->get('plugintask', 'render', 'request');

		// @FIXME cant set params directly like this, but I think plugin model setParams() is not right
		$plugin->params = $params;
		$tmpl = $plugin->getParams()->get('calendar_layout', $tmpl);
		$plugin->$pluginTask($this);
		$this->plugin = $plugin;
		$jTmplFolder = FabrikWorker::j3() ? 'tmpl' : 'tmpl25';
		$this->addTemplatePath($this->_basePath . '/plugins/' . $this->_name . '/' . $plugin->_name . '/' . $jTmplFolder . '/' . $tmpl);

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
