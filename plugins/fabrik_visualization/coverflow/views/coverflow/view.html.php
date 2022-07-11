<?php
/**
 * Fabrik Coverflow HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.coverflow
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * Fabrik Coverflow HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.coverflow
 * @since       3.0
 */

class FabrikViewCoverflow extends JViewLegacy
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
		$srcs = FabrikHelperHTML::framework();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model = $this->getModel();
		$id = $input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0)));
		$model->setId($id);
		$row = $model->getVisualization();

		if (!$model->canView())
		{
			echo FText::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		if ($this->get('RequiredFiltersFound'))
		{
			$model->render();
		}

		$params = $model->getParams();
		$this->params = $params;
		$this->containerId = $this->get('ContainerId');
		$this->row = $row;
		$this->showFilters = $model->showFilters();
		$this->filters = $this->get('Filters');
		$this->filterFormURL = $this->get('FilterFormURL');
		$tpl = $j3 ? 'bootstrap' : 'default';
		$tmplpath = JPATH_ROOT . '/plugins/fabrik_visualization/coverflow/views/coverflow/tmpl/' . $tpl;
		$this->_setPath('template', $tmplpath);
		$srcs['FbListFilter'] = 'media/com_fabrik/js/listfilter.js';

		// Assign something to Fabrik.blocks to ensure we can clear filters
		$ref = $model->getJSRenderContext();
		$js = "$ref = {};";
		$js .= "\n" . "Fabrik.addBlock('$ref', $ref);";
		$js .= $model->getFilterJs();
		FabrikHelperHTML::iniRequireJs($model->getShim());
		FabrikHelperHTML::script($srcs, $js);
		echo parent::display();
	}
}
