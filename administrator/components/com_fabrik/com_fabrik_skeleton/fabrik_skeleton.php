<?php
/**
 * Fabrik Package - Skeleton
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$db = JFactory::getDbo();

// Load front end language file as well
$lang = JFactory::getLanguage();
$lang->load('com_fabrik', JPATH_SITE . '/components/com_fabrik');

// Get the package id from #__fabrik_packages for this option
$query = $db->getQuery(true);
$app = JFactory::getApplication();
$input = $app->input;
$option = $input->get('option');
$shortName = JString::substr($option, 4);
$query->select('id')->from('#__fabrik_packages')
->where('(component_name = ' . $db->quote($option) . ' OR component_name = ' . $db->quote($shortName) . ') AND external_ref <> ""')
->order('version DESC');
$db->setQuery($query, 0, 1);
$id = $db->loadResult();

if ($id == '')
{
	throw new RuntimeException('Fabrik: Could not load package', 500);
}
// Not 100% sure we need to set packageId now - most urls are now converted to com_{packagename}
$input->set('packageId', $id);

// Include dependencies
jimport('joomla.application.component.controller');
jimport('joomla.application.component.model');
jimport('joomla.filesystem.file');

// Set the user state to load the package db tables
$app = JFactory::getApplication();
$option = FabrikString::ltrimword($option, 'com_');
$app->setUserState('com_fabrik.package', $option);

JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_fabrik/models');

$controller = $input->getCmd('view');
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
 * the specific controller for that class - otherwise use $controller to load
 * required controller class
 */

if (strpos($input->getCmd('task'), '.') !== false)
{
	$controller = explode('.', $input->getCmd('task'));
	$controller = array_shift($controller);
	$classname = 'FabrikController' . JString::ucfirst($controller);
	$path = JPATH_SITE . '/components/com_fabrik/controllers/' . $controller . '.php';

	if (JFile::exists($path))
	{
		require_once $path;

		// Needed to process J content plugin (form)
		$input->set('view', $controller);
		$task = explode('.', $input->getCmd('task'));
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
	$task = $input->getCmd('task');
}

$controller->execute($task);
$controller->redirect();
