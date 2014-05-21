<?php
/**
 * Entry point to Fabrik's administration pages
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_fabrik'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Load front end language file as well
$lang = JFactory::getLanguage();
$lang->load('com_fabrik', JPATH_SITE . '/components/com_fabrik');

// Test if the system plugin is installed and published
if (!defined('COM_FABRIK_FRONTEND'))
{
	throw new RuntimeException(JText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
}

$app = JFactory::getApplication();
$input = $app->input;

// Include dependencies
jimport('joomla.application.component.controller');
jimport('joomla.filesystem.file');

if ($input->get('format', 'html') === 'html')
{
	FabrikHelperHTML::framework();
}

JHTML::stylesheet('administrator/components/com_fabrik/headings.css');

// Check for plugin views (e.g. list email plugin's "email form"
$cName = $input->getCmd('controller');

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
		$controller = $type . $name;

		$classname = 'FabrikController' . JString::ucfirst($controller);
		$controller = new $classname;

		// Add in plugin view
		$controller->addViewPath(JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/views');

		// Add the model path
		$modelpaths = JModelLegacy::addIncludePath(JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/models');
	}
}
else
{
	$controller	= JControllerLegacy::getInstance('FabrikAdmin');
}

// Test that they've published some element plugins!
$db = JFactory::getDbo();
$query = $db->getQuery(true);
$query->select('COUNT(extension_id)')->from('#__extensions')->where('enabled = 1 AND folder = "fabrik_element"');
$db->setQuery($query);

if (count($db->loadResult()) === 0)
{
	$app->enqueueMessage(JText::_('COM_FABRIK_PUBLISH_AT_LEAST_ONE_ELEMENT_PLUGIN'), 'notice');
}

// Execute the task.
$controller->execute($input->get('task', 'home.display'));
$controller->redirect();
