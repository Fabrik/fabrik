<?php
/**
 * Access point to render Fabrik package component
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// No direct access
defined('_JEXEC') or die;

$db = JFactory::getDbo();

// Load front end language file as well
$lang = JFactory::getLanguage();
$lang->load('com_fabrik', JPATH_SITE . '/components/com_fabrik');

// Get the package id from #__fabrik_packages for this opton
$query = $db->getQuery(true);
$option = JRequest::getCmd('option');
$shortName = JString::substr($option, 4);
$query->select('id')->from('#__fabrik_packages')->where('(component_name = ' . $db->quote($option) . ' OR component_name = ' . $db->quote($shortName) . ') AND external_ref <> ""')->order('version DESC');
$db->setQuery($query, 0, 1);
$id = $db->loadResult();
if ($id == '')
{
	JError::raiseError(500, 'Could not load package');
}
// Not 100% sure we need to set packageId now - most urls are now converted to com_{packagename}
JRequest::setVar('packageId', $id);

// Include dependancies
jimport('joomla.application.component.controller');
jimport('joomla.application.component.model');
jimport('joomla.filesystem.file');

// Set the user state to load the package db tables
$app = JFactory::getApplication();
$option = FabrikString::ltrimword($option, 'com_');
$app->setUserState('com_fabrik.package', $option);

JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
JModel::addIncludePath(JPATH_SITE . '/components/com_fabrik/models');


$controller = JRequest::getCmd('view');
$path = JPATH_SITE . '/components/com_fabrik/controllers/' . $controller . '.php';
if (JFile::exists($path))
{
	require_once $path;
}
else
{
	$controller = '';
}

$classname = 'FabrikController' . JString::ucfirst($controller);

$config = array();
$config['base_path'] = JPATH_SITE . '/components/com_fabrik/';

/**
 * Create the controller if the task is in the form view.task then get
 * the specific controller for that class - otherwse use $controller to load
 * required controller class
 */
if (strpos(JRequest::getCmd('task'), '.') !== false)
{
	$controller = explode('.', JRequest::getCmd('task'));
	$controller = array_shift($controller);
	$classname = 'FabrikController' . JString::ucfirst($controller);
	$path = JPATH_SITE . '/components/com_fabrik/controllers/' . $controller . '.php';
	if (JFile::exists($path))
	{
		require_once $path;

		// Needed to process J content plugin (form)
		JRequest::setVar('view', $controller);
		$task = explode('.', JRequest::getCmd('task'));
		$task = array_pop($task);
		$controller = new $classname($config);
	}
	else
	{
		$controller = JController::getInstance('Fabrik');
	}
}
else
{
	$classname = 'FabrikController' . JString::ucfirst($controller);
	$controller = new $classname($config);
	$task = JRequest::getCmd('task');
}
$controller->execute($task);
$controller->redirect();
