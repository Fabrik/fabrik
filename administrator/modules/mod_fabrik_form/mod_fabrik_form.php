<?php
/**
 * Admin form module
 *
 * @package     Joomla.Administrator
 * @subpackage  mod_fabrik_form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');
$app = JFactory::getApplication();
$input = $app->input;

// Load front end language file as well
$lang = JFactory::getLanguage();
$lang->load('com_fabrik', JPATH_BASE . '/components/com_fabrik');

if (!defined('COM_FABRIK_FRONTEND'))
{
	throw new RuntimeException(FText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
}

FabrikHelperHTML::framework();
require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/controllers/form.php';

// $$$rob looks like including the view does something to the layout variable
$origLayout = $input->get('layout');
require_once COM_FABRIK_FRONTEND . '/views/form/view.html.php';
$input->set('layout', $origLayout);

JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
JModelLegacy::addIncludePath(COM_FABRIK_FRONTEND . '/models', 'FabrikFEModel');

$formId	= (int) $params->get('formid', 1);
$rowid = (int) $params->get('row_id', 0);
$layout = $params->get('template', '');
$usersConfig = JComponentHelper::getParams('com_fabrik');
$usersConfig->set('rowid', $rowid);

$moduleclass_sfx = $params->get('moduleclass_sfx', '');

$moduleAjax = $params->get('formmodule_useajax', true);

$origView = $input->get('view');

$input->set('formid', $formId);
$input->set('view', 'form');
$controller = new FabrikControllerForm;

/*
 * For table views in category blog layouts when no layout specified in {} the blog layout
 * was being used to render the table - which was not found which gave a 500 error
 */
if ($layout !== '')
{
	$input->set('layout', $layout);
}

// Display the view
$controller->isMambot = true;
$controller->set('cacheId', 'admin_module');
$origFormid = $input->getInt('formid');
$ajax = $input->get('ajax');
$input->set('formid', $params->get('formid'));

$input->set('ajax', $moduleAjax);
echo $controller->view();

// Reset the layout and view etc for when the component needs them
$input->set('formid', $origFormid);
$input->set('ajax', $ajax);
$input->set('layout', $origLayout);
$input->set('view', $origView);
