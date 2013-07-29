<?php
/**
 * Fabrik Front end controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

/**
 * Fabrik Component Controller
 * DEPRECIATED - should always get directed to specific controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       1.5
 */

class FabrikController extends JController
{

	/**
	 * Is the controller inside a content plug-in
	 *
	 * @var  bool
	 */
	public $isMambot = false;

	/**
	 * Display the view
	 *
	 * @param   bool   $cachable   If true, the view output will be cached
	 * @param   array  $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  null
	 */

	public function display($cachable = false, $urlparams = false)
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		// Menu links use fabriklayout parameters rather than layout
		$flayout = $input->get('fabriklayout');
		if ($flayout != '')
		{
			$input->set('layout', $flayout);
		}
		$document = JFactory::getDocument();

		$viewName = $input->get('view', 'form');
		$modelName = $viewName;
		if ($viewName == 'emailform')
		{
			$modelName = 'form';
		}

		if ($viewName == 'details')
		{
			$viewName = 'form';
			$modelName = 'form';
		}

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view
		$model = $this->getModel($modelName);
		if (!JError::isError($model) && is_object($model))
		{
			$view->setModel($model, true);
		}

		// Display the view
		$view->error = $this->getError();
		if (($viewName = 'form' || $viewName = 'details'))
		{
			$cachable = true;
		}
		$user = JFactory::getUser();

		if ($viewType != 'feed' && !$this->isMambot && $user->get('id') == 0)
		{
			$cache = JFactory::getCache('com_fabrik', 'view');
			$cache->get($view, 'display');
		}
		else
		{
			return $view->display();
		}
	}

}
