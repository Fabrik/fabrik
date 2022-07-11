<?php
/**
 * Fabrik Google Map Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * Fabrik Google Map Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @since       3.0
 */

class FabrikViewGooglemap extends JViewLegacy
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
		$app   = JFactory::getApplication();
		$input = $app->input;
		$j3    = FabrikWorker::j3();
		$srcs  = FabrikHelperHTML::framework();
		FabrikHelperHTML::slimbox();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model       = $this->getModel();
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$this->row = $model->getVisualization();

		if (!$model->canView())
		{
			echo FText::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		$this->row->label     = FText::_($this->row->label);
		$js                   = $model->getJs();
		$this->txt            = $model->getText();
		$params               = $model->getParams();
		$this->params         = $params;
		$tpl                  = $j3 ? 'bootstrap' : 'default';
		$tpl                  = $params->get('fb_gm_layout', $tpl);
		$tmplpath             = JPATH_ROOT . '/plugins/fabrik_visualization/googlemap/views/googlemap/tmpl/' . $tpl;
		$srcs['ListPlugin']   = 'media/com_fabrik/js/list-plugin.js';
		$srcs['FbListFilter'] = 'media/com_fabrik/js/listfilter.js';

		if ($params->get('fb_gm_center') == 'userslocation')
		{
			$ext = FabrikHelperHTML::isDebug() ? '.js' : '-min.js';
			FabrikHelperHTML::script('media/com_fabrik/js/lib/geo-location/geo' . $ext);
		}

		$model->getPluginJsClasses($srcs);

		global $ispda;

		if ($ispda == 1)
		{
			// Pdabot
			$template        = 'static';
			$this->staticmap = $model->getStaticMap();
		}
		else
		{
			/*if (FabrikHelperHTML::isDebug())
			{
				$srcs['GoogleMap'] = 'plugins/fabrik_visualization/googlemap/googlemap.js';
			}
			else
			{
				$srcs['GoogleMap'] = 'plugins/fabrik_visualization/googlemap/googlemap-min.js';
			}*/
			$srcs['GoogleMap'] = 'plugins/fabrik_visualization/googlemap/googlemap.js';

			if ((int) $this->params->get('fb_gm_clustering', '0') == 1)
			{
				if (FabrikHelperHTML::isDebug())
				{
					$srcs['Cluster'] = 'components/com_fabrik/libs/googlemaps/markerclustererplus/src/markerclusterer.js';
				}
				else
				{
					$srcs['Cluster'] = 'components/com_fabrik/libs/googlemaps/markerclustererplus/src/markerclusterer_packed.js';
				}
			}

			$template = null;
		}

		// Assign plugin js to viz so we can then run clearFilters()
		$aObjs = $model->getPluginJsObjects();

		if (!empty($aObjs))
		{
			$js .= $model->getJSRenderContext() . ".addPlugins([\n";
			$js .= "\t" . implode(",\n  ", $aObjs);
			$js .= "]);";
		}

		if ($model->showFilters())
		{
			$js .= $model->getFilterJs();
		}

		$model->getCustomJsAction($srcs);

		FabrikHelperHTML::iniRequireJs($model->getShim());
		FabrikHelperHTML::script($srcs, $js);
		FabrikHelperHTML::stylesheetFromPath('plugins/fabrik_visualization/googlemap/views/googlemap/tmpl/' . $tpl . '/template.css');

		// Check and add a general fabrik custom css file overrides template css and generic table css
		FabrikHelperHTML::stylesheetFromPath('media/com_fabrik/css/custom.css');

		// Check and add a specific viz template css file overrides template css generic table css and generic custom css
		FabrikHelperHTML::stylesheetFromPath('plugins/fabrik_visualization/googlemap/views/googlemap/tmpl/' . $tpl . '/custom.css');
		$this->filters         = $model->getFilters();
		$this->showFilters     = $model->showFilters();
		$this->filterFormURL   = $model->getFilterFormURL();
		$this->sidebarPosition = $params->get('fb_gm_use_overlays_sidebar');
		$this->showOverLays    = (bool) $params->get('fb_gm_use_overlays');

		if ($model->getShowSideBar())
		{
			$this->showSidebar   = 1;
			$this->overlayUrls   = $model->overlayData['urls'];
			$this->overlayLabels = $model->overlayData['labels'];
		}
		else
		{
			$this->showSidebar = 0;
		}

		$this->_setPath('template', $tmplpath);
		$this->containerId    = $model->getContainerId();
		$this->groupTemplates = $model->getGroupTemplates();
		echo parent::display($template);
	}
}
