<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
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

FabrikHelperHTML::framework();
require_once COM_FABRIK_FRONTEND . '/controllers/form.php';

// $$$rob looks like including the view does something to the layout variable
$origLayout = $input->get('layout');
require_once COM_FABRIK_FRONTEND . '/views/form/view.html.php';
require_once COM_FABRIK_FRONTEND . '/views/package/view.html.php';
require_once COM_FABRIK_FRONTEND . '/views/list/view.html.php';

$input->set('layout', $origLayout);

JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
JModelLegacy::addIncludePath(COM_FABRIK_FRONTEND . '/models', 'FabrikFEModel');

$formId = (int) $params->get('formid');
$rowid = (string) $params->get('row_id', '');

$layout = $params->get('template', 'default');
$usersConfig = JComponentHelper::getParams('com_fabrik');
$usersConfig->set('rowid', $rowid);

$usekey = $params->get('usekey', '');

if (!empty($usekey))
{
	$input->set('usekey', $usekey);
}

$moduleclass_sfx = $params->get('moduleclass_sfx', '');
$moduleAjax = $params->get('formmodule_useajax', true);
$origView = $input->get('view');

$input->set('view', 'form');
$controller = new FabrikControllerForm;

/* $$$rob for table views in category blog layouts when no layout specified in {} the blog layout
 * was being used to render the table - which was not found which gave a 500 error
*/
$input->set('layout', $layout);

// Display the view
$controller->isMambot = true;
$origFormid = $input->getInt('formid');
$ajax = $input->get('ajax');
$input->set('formid', $formId);

$input->set('ajax', $moduleAjax);
echo $controller->display();

// Reset the layout and view etc for when the component needs them
$input->set('formid', $origFormid);
$input->set('ajax', $ajax);
$input->set('layout', $origLayout);
$input->set('view', $origView);
