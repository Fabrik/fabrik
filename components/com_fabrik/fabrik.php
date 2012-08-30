<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.helper');
jimport('joomla.filesystem.file');

if (!defined('COM_FABRIK_FRONTEND'))
{
	JError::raiseError(400, JText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'));
}

require_once JPATH_COMPONENT . '/controller.php';

/**
 * Test for YQL & XML document type
 * use the format request value to check for document type
 */
$docs = array("yql", "xml");
foreach ($docs as $d)
{
	if (JRequest::getCmd("type") == $d)
	{
		// Get the class
		require_once JPATH_SITE . '/administrator/components/com_fabrik/classes/' . $d . 'document.php';

		// Replace the document
		$document = JFactory::getDocument();
		$docClass = 'JDocument' . JString::strtoupper($d);
		$document = new $docClass;
	}
}

JModel::addIncludePath(JPATH_COMPONENT . '/models');

$controllerName = JRequest::getCmd('view');

// Check for a plugin controller

// Call a plugin controller via the url :
// &c=visualization.calendar

$isplugin = false;
$cName = JRequest::getCmd('controller');
if (JString::strpos($cName, '.') != false)
{
	list($type, $name) = explode('.', $cName);
	if ($type == 'visualization')
	{
		require_once JPATH_COMPONENT . '/controllers/visualization.php';
	}
	$path = JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/controllers/' . $name . '.php';
	if (JFile::exists($path))
	{
		require_once $path;
		$isplugin = true;
		$controller = $type . $name;
	}
	else
	{
		$controller = '';
	}

}
else
{
	// Its not a plugin
	// map controller to view - load if exists

	/**
	 * $$$ rob was a simple $controller = view, which was giving an error when trying to save a popup
	 * form to the calendar viz
	 * May simply be the best idea to remove main contoller and have different controllers for each view
	 */

	// Hack for package
	if (JRequest::getCmd('view') == 'package' || JRequest::getCmd('view') == 'list')
	{
		$controller = JRequest::getCmd('view');
	}
	else
	{
		$controller = $controllerName;
	}

	$path = JPATH_COMPONENT . '/controllers/' . $controller . '.php';
	if (JFile::exists($path))
	{
		require_once $path;
	}
	else
	{
		$controller = '';
	}
}
/**
 * Create the controller if the task is in the form view.task then get
 * the specific controller for that class - otherwse use $controller to load
 * required controller class
 */
if (strpos(JRequest::getCmd('task'), '.') !== false)
{
	$controller = array_shift(explode('.', JRequest::getCmd('task')));
	$classname = 'FabrikController' . JString::ucfirst($controller);
	$path = JPATH_COMPONENT . '/controllers/' . $controller . '.php';
	if (JFile::exists($path))
	{
		require_once $path;

		// Needed to process J content plugin (form)
		JRequest::setVar('view', $controller);
		$task = array_pop(explode('.', JRequest::getCmd('task')));
		$controller = new $classname;
	}
	else
	{
		$controller = JController::getInstance('Fabrik');
	}
}
else
{
	$classname = 'FabrikController' . JString::ucfirst($controller);
	$controller = new $classname;
	$task = JRequest::getCmd('task');
}

if ($isplugin)
{
	// Add in plugin view
	$controller->addViewPath(JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/views');

	// Add the model path
	$modelpaths = JModel::addIncludePath(JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/models');
}

// $$$ rob if you want to you can override any fabrik model by copying it from
// models/ to models/adaptors the copied file will overwrite (NOT extend) the original
$controller->addModelPath(JPATH_COMPONENT . '/models/adaptors');

$app = JFactory::getApplication();
$package = JRequest::getVar('package', 'fabrik');
$app->setUserState('com_fabrik.package', $package);

// Web service testing
JLoader::import('webservice', JPATH_SITE . '/components/com_fabrik/models/');
if (JRequest::getVar('soap') == 1)
{
	$opts = array('driver' => 'soap', 'endpoint' => 'http://webservices.activetickets.com/members/ActiveTicketsMembersServices.asmx?WSDL',
		'credentials' => array('Clientname' => "SPLFenix", 'LanguageCode' => "nl"));

	$service = FabrikWebService::getInstance($opts);

	$params = $opts['credentials'];
	$params['From'] = JFactory::getDate()->toISO8601();
	$params['To'] = JFactory::getDate('next year')->toISO8601();
	$params['IncludePrices'] = true;
	$params['MemberId'] = 14;
	$method = JRequest::getVar($method, 'GetProgramList');
	$program = $service->get($method, $params, '//ProgramList/Program', null);

	$listModel = JModel::getInstance('List', 'FabrikFEModel');
	$listModel->setId(7);
	$service->storeLocally($listModel, $program);

}

if (JRequest::getVar('yql') == 1)
{
	$opts = array('driver' => 'yql', 'endpoint' => 'https://query.yahooapis.com/v1/public/yql');

	$service = FabrikWebService::getInstance($opts);
	$query = "select * from upcoming.events where location='London'";
	$program = $service->get($query, array(), 'event', null);
}
// End web service testing

$controller->execute($task);

// Redirect if set by the controller
$controller->redirect();
