<?php
/**
 * Fabrik Fusion Chart HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionchart
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * Fabrik Fusion Chart HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionchart
 * @since       3.0
 */

class FabrikViewFusionchart extends JViewLegacy
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 */

	public function display($tpl = 'default')
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$j3 = FabrikWorker::j3();
		$model = $this->getModel();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$this->row = $model->getVisualization();

		if (!$model->canView())
		{
			echo FText::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		$this->requiredFiltersFound = $this->get('RequiredFiltersFound');

		/*
		if ($this->requiredFiltersFound)
		{
			$this->chart = $this->get('Fusionchart');
		}
		else
		{
			$this->chart = '';
		}
		*/

		$params = $model->getParams();
		$this->params = $params;
		$viewName = $this->getName();
		$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		$plugin = $pluginManager->getPlugIn('calendar', 'visualization');
		$this->containerId = $this->get('ContainerId');
		$this->filters = $this->get('Filters');
		$this->showFilters = $model->showFilters();
		$this->filterFormURL = $this->get('FilterFormURL');
		$tpl = $j3 ? 'bootstrap' : 'default';
		$tpl = $input->get('layout', $params->get('fusionchart_layout', $tpl));
		$this->_setPath('template', JPATH_ROOT . '/plugins/fabrik_visualization/fusionchart/views/fusionchart/tmpl/' . $tpl);

		FabrikHelperHTML::stylesheetFromPath('plugins/fabrik_visualization/fusionchart/views/fusionchart/tmpl/' . $tpl . '/template.css');
		// Adding custom.css, just for the heck of it
		FabrikHelperHTML::stylesheetFromPath('plugins/fabrik_visualization/fusionchart/views/fusionchart/tmpl/' . $tpl . '/custom.css');
		FabrikHelperHTML::stylesheetFromPath(
			'plugins/fabrik_visualization/fusionchart/views/fusionchart/tmpl/' . $tpl . '/custom_css.php?c=' . $this->containerId . '&id=' . $model->getVisualization()->get('id')
		);

		$this->iniJs();
		$text = $this->loadTemplate();
		FabrikHelperHTML::runContentPlugins($text, true);
		echo $text;
	}

	/**
	 * Get Js Options
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	private function jsOptions()
	{
		$model    = $this->getModel();
		$params = $model->getParams();
		$options = new stdClass;
		$options->id = $model->getVisualization()->get('id');
		$options->chartJSON = $model->getFusionChart();
		$options->chartType = $model->getRealChartType($params->get('fusionchart_type'));
		$options->chartWidth = $params->get('fusionchart_width', '100%');
		$options->chartHeight = $params->get('fusionchart_height', '100%');
		$options->chartID = 'FusionChart_' . $model->getJSRenderContext();
		$options->chartContainer = 'chart-container-' . $model->getJSRenderContext();
		return $options;
	}

	/**
	 * Initialize the js
	 *
	 * @return void
	 */
	private function iniJs()
	{
		$model = $this->getModel();
		$params = $model->getParams();
		$ref   = $model->getJSRenderContext();
		$json  = json_encode($this->jsOptions());
		$js    = array();
		$js[]  = "\tvar $ref = new fabrikFusionchart('$ref', $json);";
		$js[]  = "\tFabrik.addBlock('" . $ref . "', $ref);";
		$js[]  = "" . $model->getFilterJs();
		$js    = implode("\n", $js);

		$mediaFolder = FabrikHelperHTML::getMediaFolder();

		$srcs   = FabrikHelperHTML::framework();
		$srcs['FbListFilter'] = $mediaFolder . '/listfilter.js';
		$srcs['fabrikFusionchart'] = 'plugins/fabrik_visualization/fusionchart/fusionchart.js';

		$shim = $model->getShim();
        $xtLibPath = $params->get('fusionchart_library', 'fusioncharts-suite-xt');
        $paths = array(
        	'fusionchart' => 'plugins/fabrik_visualization/fusionchart/libs/' . $xtLibPath . '/js/fusioncharts'
        );

		$shim['fusionchart'] = (object) array(
			'deps' => array()
		);

		$vizShim = 'viz/fusionchart/fusionchart';
		if (!FabrikHelperHTML::isDebug())
		{
			$vizShim .= '-min';
		}

		$shim[$vizShim] = (object) array(
			'deps' => array('fusionchart', 'jquery')
		);

		$theme = $params->get('fusionchart_theme', '');

		if (!empty($theme))
		{
			$paths['fusioncharttheme'] = 'plugins/fabrik_visualization/fusionchart/libs/' . $xtLibPath .
				'/js/themes/fusioncharts.theme.' . $theme;

			$shim['fusioncharttheme'] = (object) array(
				'deps' => array('fusionchart')
			);

			$shim[$vizShim]->deps[] = 'fusioncharttheme';

			//$shim['fusionchart']->deps[] = 'fusioncharttheme';
		}

		$model->getCustomJsAction($srcs);

		FabrikHelperHTML::iniRequireJs($shim, $paths);
		FabrikHelperHTML::script($srcs, $js);
	}
}
