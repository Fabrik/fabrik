<?php
/**
 * Fabrik Media Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * Fabrik Media Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 * @since       3.0
 */

class FabrikViewMedia extends JView
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
		$model = $this->getModel();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$this->row = $model->getVisualization();
		$params = $model->getParams();

		$js = $model->getJs();
		$srcs = FabrikHelperHTML::framework();
		$srcs[] = 'media/com_fabrik/js/listfilter.js';
		$srcs[] = 'plugins/fabrik_visualization/media/media.js';
		if ($params->get('media_which_player', 'jw') == 'jw')
		{
			$srcs[] = 'plugins/fabrik_visualization/media/libs/jw/jwplayer.js';
		}
		FabrikHelperHTML::script($srcs, $js);
		if ($this->row->published == 0)
		{
			JError::raiseWarning(500, JText::_('JERROR_ALERTNOAUTHOR'));
			return '';
		}
		$media = $model->getRow();
		$this->media = $model->getMedia();

		$this->params = $params;
		$viewName = $this->getName();
		$this->containerId = $this->get('ContainerId');
		$this->showFilters = $model->showFilters();
		$this->filters = $this->get('Filters');
		$this->params = $model->getParams();
		$tpl = $params->get('media_layout', $tpl);
		$tmplpath = JPATH_ROOT . '/plugins/fabrik_visualization/media/views/media/tmpl/' . $tpl;
		$this->_setPath('template', $tmplpath);
		FabrikHelperHTML::stylesheetFromPath('plugins/fabrik_visualization/media/views/media/tmpl/' . $tpl . '/template.css');
		echo parent::display();
	}

}
