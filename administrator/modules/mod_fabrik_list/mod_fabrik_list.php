<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');

// Load front end language file as well
$lang = JFactory::getLanguage();
$lang->load('com_fabrik', JPATH_SITE . '/components/com_fabrik');

if (!defined('COM_FABRIK_FRONTEND'))
{
	throw RuntimeException(FText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
}

jimport('joomla.application.component.model');
jimport('joomla.application.component.helper');
JModelLegacy::addIncludePath(COM_FABRIK_FRONTEND . '/models', 'FabrikFEModel');
require_once __DIR__ . '/helper.php';

$app = JFactory::getApplication();

require_once COM_FABRIK_FRONTEND . '/controller.php';
require_once COM_FABRIK_FRONTEND . '/controllers/list.php';

// $$$rob looks like including the view does something to the layout variable
$input = $app->input;
$origLayout = $input->get('layout');
require_once COM_FABRIK_FRONTEND . '/views/list/view.html.php';
$input->set('layout', $origLayout);

require_once COM_FABRIK_FRONTEND . '/views/package/view.html.php';
JModelLegacy::addIncludePath(COM_FABRIK_FRONTEND . '/models');
JTable::addIncludePath(COM_FABRIK_BASE . '/administrator/components/com_fabrik/tables');
$document = JFactory::getDocument();
require_once COM_FABRIK_FRONTEND . '/controllers/package.php';
require_once COM_FABRIK_FRONTEND . '/views/form/view.html.php';
$listId = (int) $params->get('list_id', 0);

if ($listId === 0)
{
	throw RuntimeException('Fabrik Module: No list specified', 500);
}

$listels = json_decode($params->get('list_elements'));

if (isset($listels->show_in_list))
{
	$input->set('fabrik_show_in_list', $listels->show_in_list);
}

$layout	= $params->get('fabriklayout', 'default');
$input->set('layout', $layout);

$moduleclass_sfx = $params->get('moduleclass_sfx', '');
$listId = (int) $params->get('list_id', 1);

$viewName = 'list';
$viewType = $document->getType();
$controller = new FabrikControllerList;

// Set the default view name from the Request
$view = clone ($controller->getView($viewName, $viewType));

// Push a model into the view
$model = $controller->getModel($viewName, 'FabrikFEModel');
$model->setId($listId);
$model->setRenderContext($module->id);
ModFabrikListHelper::applyParams($params, $model);

$view->setModel($model, true);
$view->isMambot = true;

// Display the view
$view->error = $controller->getError();

// Build unique cache id on url, post and user id
$user = JFactory::getUser();
$uri = JURI::getInstance();
$uri = $uri->toString(array('path', 'query'));
$cacheid = serialize(array($uri, $_POST, $user->get('id'), get_class($view), 'display', $listId));
$cache = JFactory::getCache('com_fabrik', 'view');

// F3 cache with raw view gives error
if (in_array($input->get('format'), array('raw', 'csv')))
{
	$view->display();
}
else
{
	$cache->get($view, 'display', $cacheid);
}

JText::script('COM_FABRIK_FORM_SAVED');

// Reset altered input parameters
$input->set('layout', $origLayout);
$input->set('fabrik_show_in_list', null);
