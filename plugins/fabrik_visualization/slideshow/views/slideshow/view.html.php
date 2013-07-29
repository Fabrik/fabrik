<?php
/**
 * Slideshow vizualization: view
 *
* @package     Joomla.Plugin
* @subpackage  Fabrik.visualization.slideshow
* @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
* @license     GNU General Public License version 2 or later; see LICENSE.txt
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * Fabrik Slideshow Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.timeline
 * @since       3.0
 */

class FabrikViewSlideshow extends JView
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
		$srcs = FabrikHelperHTML::framework();
		$model = $this->getModel();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$this->row = $model->getVisualization();
		if ($this->row->published == 0)
		{
			JError::raiseWarning(500, JText::_('JERROR_ALERTNOAUTHOR'));
			return '';
		}
		$this->js = $this->get('JS');
		$viewName = $this->getName();
		$params = $model->getParams();
		$this->params = $params;
		$pluginManager = JModel::getInstance('Pluginmanager', 'FabrikFEModel');
		$plugin = $pluginManager->getPlugIn('slideshow', 'visualization');
		$this->showFilters = $model->showFilters();
		$this->filters = $this->get('Filters');
		$this->filterFormURL = $this->get('FilterFormURL');
		$this->params = $model->getParams();
		$this->containerId = $this->get('ContainerId');
		$pluginParams = $model->getPluginParams();
		$tpl = $pluginParams->get('slideshow_viz_layout', $tpl);
		$tmplpath = $model->pathBase . 'slideshow/views/slideshow/tmpl/' . $tpl;
		$this->_setPath('template', $tmplpath);
		$srcs[] = 'media/com_fabrik/js/listfilter.js';
		if ($this->get('RequiredFiltersFound'))
		{
			$srcs[] = 'components/com_fabrik/libs/slideshow2/js/slideshow.js';
			$mode = $pluginParams->get('slideshow_viz_type', 1);
			switch ($mode)
			{
				case 1:
					break;
				case 2:
					$srcs[] = 'components/com_fabrik/libs/slideshow2/js/slideshow.kenburns.js';
					break;
				case 3:
					$srcs[] = 'components/com_fabrik/libs/slideshow2/js/slideshow.push.js';
					break;
				case 4:
					$srcs[] = 'components/com_fabrik/libs/slideshow2/js/slideshow.fold.js';
					break;
				default:
					break;
			}

			JHTML::stylesheet('components/com_fabrik/libs/slideshow2/css/slideshow.css');
			$srcs[] = 'plugins/fabrik_visualization/slideshow/slideshow.js';
		}
		FabrikHelperHTML::script($srcs, $this->js);

		$tmplpath = JPATH_ROOT . '/plugins/fabrik_visualization/slideshow/views/slideshow/tmpl/' . $tpl;
		$this->_setPath('template', $tmplpath);
		FabrikHelperHTML::stylesheetFromPath('plugins/fabrik_visualization/slideshow/views/slideshow/tmpl/' . $tpl . '/template.css');
		echo parent::display();
	}

}
