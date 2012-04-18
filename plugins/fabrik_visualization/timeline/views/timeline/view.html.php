<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewTimeline extends JView
{

	function display($tmpl = 'default')
	{
		FabrikHelperHTML::framework();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model = $this->getModel();
		$id = JRequest::getVar('id', $usersConfig->get('visualizationid', JRequest::getInt('visualizationid', 0)));
		$model->setId($id);
		$row = $model->getVisualization();
		$model->setListIds();

		$model->render();
		$this->assign('containerId', $this->get('ContainerId'));
		$this->assignRef('row', $row);
		$this->assign('showFilters', JRequest::getInt('showfilters', 1) === 1 ?  1 : 0);
		$this->assignRef('filters', $this->get('Filters'));
		$this->assign('filterFormURL', $this->get('FilterFormURL'));
		$pluginParams = $model->getPluginParams();
		$this->assignRef('params', $pluginParams);
		$tmplpath = JPATH_ROOT . '/plugins/fabrik_visualization/timeline/views/timeline/tmpl/' . $tmpl;
		$this->_setPath('template', $tmplpath);
		//ensure we don't have an incorrect version of mootools loaded
		JHTML::stylesheet('list.css', 'media/com_fabrik/css/');
		FabrikHelperHTML::script('list.js', 'media/com_fabrik/js/', true);

		//check and add a general fabrik custom css file overrides template css and generic table css
		FabrikHelperHTML::stylesheetFromPath('media/com_fabrik/css/custom.css');
		//check and add a specific biz  template css file overrides template css generic table css and generic custom css
		FabrikHelperHTML::stylesheetFromPath('plugins/fabrik_visualization/timeline/views/timeline/tmpl/' . $tmpl . '/custom.css');

		return parent::display();
	}
}
?>