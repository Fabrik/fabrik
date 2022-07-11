<?php
/**
 * Fabrik Media Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * Fabrik Media Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 * @since       3.0
 */

class FabrikViewMedia extends JViewLegacy
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
		$params = $model->getParams();
		$js = $model->getJs();
		$srcs = FabrikHelperHTML::framework();
		$srcs['FbListFilter'] = 'media/com_fabrik/js/listfilter.js';
		$srcs['Media'] = 'plugins/fabrik_visualization/media/media.js';

		if ($params->get('media_which_player', 'jw') == 'jw')
		{
			$srcs['JWPlayer'] = 'plugins/fabrik_visualization/media/libs/jw/jwplayer.js';
		}

		FabrikHelperHTML::iniRequireJs($model->getShim());
		FabrikHelperHTML::script($srcs, $js);

		if (!$model->canView())
		{
			echo FText::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		$media = $model->getRow();
		$this->media = $model->getMedia();
		$this->params = $params;
		$viewName = $this->getName();
		$this->containerId = $model->getContainerId();
		$this->showFilters = $model->showFilters();
		$this->filterFormURL = $model->getFilterFormURL();
		$this->filters = $this->get('Filters');
		$this->params = $model->getParams();
		$tpl = $j3 ? 'bootstrap' : 'default';
		$tpl = $params->get('media_layout', $tpl);
		$tplpath = JPATH_ROOT . '/plugins/fabrik_visualization/media/views/media/tmpl/' . $tpl;
		$this->_setPath('template', $tplpath);
		FabrikHelperHTML::stylesheetFromPath('plugins/fabrik_visualization/media/views/media/tmpl/' . $tpl . '/template.css');
		echo parent::display();
	}
}
