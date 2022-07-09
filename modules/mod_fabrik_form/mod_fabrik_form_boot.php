<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');

// Load front end language file as well
$lang = JFactory::getLanguage();
$lang->load('com_fabrik', JPATH_BASE . '/components/com_fabrik');

if (!defined('COM_FABRIK_FRONTEND'))
{
	JError::raiseError(400, JText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'));
}

$app = JFactory::getApplication();
$input = $app->input;

$origLayout = $input->get('layout');
$origView = $input->get('view');
$origAjax = $input->get('ajax');
$origFormid = $input->getInt('formid');
$input->set('orig_formid', $origFormid);
$origRowid = $input->getInt('rowid');
$input->set('orig_rowid', $origRowid);

FabrikHelperHTML::framework();

// $$$rob looks like including the view does something to the layout variable
require_once COM_FABRIK_FRONTEND . '/views/form/view.html.php';
require_once COM_FABRIK_FRONTEND . '/views/package/view.html.php';
require_once COM_FABRIK_FRONTEND . '/views/list/view.html.php';

$input->set('layout', $origLayout);

JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
JModelLegacy::addIncludePath(COM_FABRIK_FRONTEND . '/models', 'FabrikFEModel');

$formId = (int) $params->get('formid');

if (empty($formId))
{
	throw new \InvalidArgumentException('No form selected in Fabrik form module!');
}

$readonly = $params->get('readonly', '0');
if ($readonly == 1) {
	require_once COM_FABRIK_FRONTEND . '/controllers/details.php';
	$controller = new FabrikControllerDetails;
	$input->set('view', 'details');
} else {
	require_once COM_FABRIK_FRONTEND . '/controllers/form.php';
	$controller = new FabrikControllerForm;
	$input->set('view', 'form');
}

$layout = $params->get('template', 'default');
$usersConfig = JComponentHelper::getParams('com_fabrik');
$rowid = (string) $params->get('row_id', '');
$usersConfig->set('rowid', $rowid);

$usekey = $params->get('usekey', '');

if (!empty($usekey))
{
	$input->set('usekey', $usekey);
}

$moduleclass_sfx = $params->get('moduleclass_sfx', '');
$moduleAjax = $params->get('formmodule_useajax', true);


/* $$$rob for table views in category blog layouts when no layout specified in {} the blog layout
 * was being used to render the table - which was not found which gave a 500 error
*/
$input->set('layout', $layout);

// Display the view
$controller->isMambot = true;
$controller->cacheId = $formId . '-' . $rowid;
$input->set('formid', $formId);

$input->set('ajax', $moduleAjax);
echo $controller->display();

// Reset the layout and view etc for when the component needs them
$input->set('formid', $origFormid);
$input->set('rowid', $origRowid);
$input->set('ajax', $origAjax);
$input->set('layout', $origLayout);
$input->set('view', $origView);
