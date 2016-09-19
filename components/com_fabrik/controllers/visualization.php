<?php
/**
 * Abstract Visualization Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Controllers\Controller;

jimport('joomla.application.component.controller');

require_once COM_FABRIK_FRONTEND . '/helpers/params.php';

/**
 * Abstract Visualization Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       1.5
 */
class FabrikControllerVisualization extends Controller
{
	/**
	 * Id used from content plugin when caching turned on to ensure correct element rendered
	 *
	 * @var  int
	 */
	public $cacheId = 0;

	/**
	 * Display the view
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached - NOTE not actually used to control caching!!
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JControllerLegacy  A JControllerLegacy object to support chaining.
	 *
	 * @since   12.2
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$document = JFactory::getDocument();
		$input = $this->input;
		$viewName = str_replace('FabrikControllerVisualization', '', get_class($this));

		if ($viewName == '')
		{
			/**
			 * if we are using a url like http://localhost/fabrik3.0.x/index.php?option=com_fabrik&view=visualization&id=6
			 * then we need to ascertain which viz to use
			 */
			$viewName = $this->getViewName();
		}

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view
		if ($model = $this->getModel($viewName))
		{
			$view->setModel($model, true);
		}
		// Display the view
		$view->error = $this->getError();

		// F3 cache with raw view gives error
		if (in_array($input->get('format'), array('raw', 'csv')))
		{
			$view->display();
		}
		else
		{
			// Build unique cache id on url, post and user id
			$user = JFactory::getUser();
			$uri = JURI::getInstance();
			$uri = $uri->toString(array('path', 'query'));
			$cacheId = serialize(array($uri, $input->post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache = JFactory::getCache('com_fabrik', 'view');
			$cache->get($view, 'display', $cacheId);
		}

		return $this;
	}

	/**
	 * If loading via id then we want to get the view name and add the plugin view and model paths
	 *
	 * @return   string  view name
	 */
	protected function getViewName()
	{
		$viz = FabTable::getInstance('Visualization', 'FabrikTable');
		$viz->load($this->input->getInt('id'));
		$viewName = $viz->plugin;
		$this->addViewPath(JPATH_SITE . '/plugins/fabrik_visualization/' . $viewName . '/views');
		JModelLegacy::addIncludePath(JPATH_SITE . '/plugins/fabrik_visualization/' . $viewName . '/models');

		return $viewName;
	}

	/**
	 * Override of j!'s getView
	 *
	 * Method to get a reference to the current view and load it if necessary.
	 *
	 * @param   string  $name    The view name. Optional, defaults to the controller name.
	 * @param   string  $type    The view type. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for view. Optional.
	 *
	 * @return  object  Reference to the view or an error.
	 */
	public function getView($name = '', $type = '', $prefix = '', $config = array())
	{
		$viewName = str_replace('FabrikControllerVisualization', '', get_class($this));
		$viewName = $viewName == '' ? $this->getViewName() : $name;

		return parent::getView($viewName, $type, $prefix, $config);
	}
}
