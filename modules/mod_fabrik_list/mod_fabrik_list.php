<?php
/**
 * Fabrik List Module
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if (!defined('COM_FABRIK_FRONTEND'))
{
	JError::raiseError(400, JText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'));
}

jimport('joomla.filesystem.file');
jimport('joomla.application.component.model');
jimport('joomla.application.component.helper');
JModelLegacy::addIncludePath(COM_FABRIK_FRONTEND . '/models', 'FabrikFEModel');
JModelLegacy::addIncludePath(COM_FABRIK_FRONTEND . '/models');
JModelLegacy::addIncludePath(COM_FABRIK_BASE . '/administrator/components/com_fabrik/tables');

require_once COM_FABRIK_FRONTEND . '/controller.php';
require_once COM_FABRIK_FRONTEND . '/controllers/list.php';
require_once COM_FABRIK_FRONTEND . '/views/list/view.html.php';
require_once COM_FABRIK_FRONTEND . '/views/package/view.html.php';
require_once COM_FABRIK_FRONTEND . '/controllers/package.php';
require_once COM_FABRIK_FRONTEND . '/views/form/view.html.php';

// Load front end language file as well
$lang = JFactory::getLanguage();
$lang->load('com_fabrik', JPATH_BASE . '/components/com_fabrik');

$app = JFactory::getApplication();
$input = $app->input;

// Clear out other filters (e.g. content/module previously rendered which used the same list but different filter)
if ($params->get('clearfilters'))
{
	$input->set('clearfilters', 1);
}

$document = JFactory::getDocument();

// Ensure the package is set to fabrik
$prevUserState = $app->getUserState('com_fabrik.package');
$app->setUserState('com_fabrik.package', 'fabrik');

FabrikHelperHTML::framework();

// $$$rob looks like including the view does something to the layout variable
$origLayout = $input->get('layout');
$origItemId = $input->get('itemId');

$listId = (int) $params->get('list_id', 1);
$useajax = (int) $params->get('useajax', 0);
$random	= (int) $params->get('radomizerecords', 0);
$limit = (int) $params->get('limit', 0);
$origResetfilters = $input->get('resetfilters');
$resetFilters = (int) $params->get('resetfilters', 0);
$input->set('resetfilters', $resetFilters);
$showTitle = $params->get('show-title', '');
$layout	= $params->get('fabriklayout', '');
$input->set('layout', $layout);

$moduleclass_sfx = $params->get('moduleclass_sfx', '');
$listId	= intval($params->get('list_id', 0));
$listels = json_decode($params->get('list_elements'));

if ($listId === 0)
{
	JError::raiseError(500, 'no list specified');
}

if (isset($listels->show_in_list))
{
	$input->set('fabrik_show_in_list', $listels->show_in_list);
}

$viewName = 'list';
$viewType = $document->getType();
$controller = new FabrikControllerList;

// Set the default view name from the Request
$view = clone($controller->getView($viewName, $viewType));

// Push a model into the view
$model = $controller->getModel($viewName, 'FabrikFEModel');
$model->setId($listId);
$model->setRenderContext($module->id);

if ($limit !== 0)
{
	$app->setUserState('com_fabrik.list' . $model->getRenderContext() . '.limitlength', $limit);
	$input->set('limit' . $listId, $limit);
}

if ($useajax !== '')
{
	$model->set('ajax', $useajax);
}

if ($params->get('ajax_links') !== '')
{
	$listParams = $model->getParams();
	$listParams->set('list_ajax_links', $params->get('ajax_links'));
}

if ($params->get('show_nav', '') !== '')
{
	$listParams->set('show-table-nav', $params->get('show_nav'));
}

$listParams->set('show_into', $params->get('show_into', 1));
$listParams->set('show_outro', $params->get('show_outro', 1));
$origShowFilters = $app->input->get('showfilters', 1);
$app->input->set('showfilters', $params->get('show_filters', 1));

if ($showTitle !== '')
{
	$listParams->set('show-title', $showTitle);
}

$ordering = JArrayHelper::fromObject(json_decode($params->get('ordering')));
$orderBy = (array) $ordering['order_by'];
$orderDir = (array) $ordering['order_dir'];

if (!empty($orderBy))
{
	$model->getTable()->order_by = json_encode($orderBy);
	$model->getTable()->order_dir = json_encode($orderDir);
}

// Set up prefilters - will overwrite ones defined in the list!

$prefilters = JArrayHelper::fromObject(json_decode($params->get('prefilters')));
$conditions = (array) $prefilters['filter-conditions'];

if (!empty($conditions))
{
	$listParams->set('filter-join', $prefilters['filter-join']);
	$listParams->set('filter-fields', $prefilters['filter-fields']);
	$listParams->set('filter-conditions', $prefilters['filter-conditions']);
	$listParams->set('filter-value', $prefilters['filter-value']);
	$listParams->set('filter-access', $prefilters['filter-access']);
	$listParams->set('filter-eval', $prefilters['filter-eval']);
}

$model->randomRecords = $random;

if (!JError::isError($model))
{
	$view->setModel($model, true);
}

$view->isMambot = true;

$input->set('itemId', $params->get('itemId', $origItemId));

// Display the view
$view->error = $controller->getError();
echo $view->display();

$input->set('itemId', $origItemId);
$input->set('layout', $origLayout);
$input->set('showfilters', $origShowFilters);
$input->set('resetfilters', $origResetfilters);

// Set the package back to what it was before rendering the module
$app->setUserState('com_fabrik.package', $prevUserState);
