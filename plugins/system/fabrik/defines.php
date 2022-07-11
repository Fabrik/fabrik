<?php
/**
 * Any of these defines can be overwritten by copying this file to
 * plugins/system/fabrik/user_defines.php
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');

// Could be that the sys plugin is installed but fabrik not
if (!JFolder::exists(JPATH_SITE . '/components/com_fabrik/'))
{
	return;
}

define("COM_FABRIK_BASE", JPATH_SITE . DIRECTORY_SEPARATOR);
define("COM_FABRIK_FRONTEND", COM_FABRIK_BASE . 'components/com_fabrik');
define("COM_FABRIK_BACKEND", COM_FABRIK_BASE . 'administrator/components/com_fabrik');
define("COM_FABRIK_LIBRARY", COM_FABRIK_BASE . 'libraries/fabrik');
define("COM_FABRIK_LIVESITE", JURI::root());
define("COM_FABRIK_LIVESITE_ROOT", JURI::getInstance()->toString(array('scheme', 'host', 'port')));
define("FABRIKFILTER_TEXT", 0);
define("FABRIKFILTER_EVAL", 1);
define("FABRIKFILTER_QUERY", 2);
define("FABRIKFILTER_NOQUOTES", 3);

/** @var delimiter used to define separator in csv export */
define("COM_FABRIK_CSV_DELIMITER", ",");
define("COM_FABRIK_EXCEL_CSV_DELIMITER", ";");

/** @var string separator used in repeat elements/groups IS USED IN F3 */
define("GROUPSPLITTER", "//..*..//");

$app = JFactory::getApplication();
$input = $app->input;

// Override JHTML -needed for framework override
$version = new JVersion;
JHTML::addIncludePath(JPATH_SITE . '/components/com_fabrik/jhelpers/' . $version->RELEASE . '/');

// Register the element class with the loader
JLoader::register('JElement', JPATH_SITE . '/administrator/components/com_fabrik/element.php');

/**
 * Moved these to the plugin constructor, fixing a compat issue with Kunena, see comments there.
 */
// JLoader::import('components.com_fabrik.classes.field', JPATH_SITE . '/administrator', 'administrator.');
// JLoader::import('components.com_fabrik.classes.form', JPATH_SITE . '/administrator', 'administrator.');

// Avoid errors during update, if plugin has been updated but component hasn't, use old helpers
if (JFile::exists(COM_FABRIK_FRONTEND . '/helpers/legacy/aliases.php'))
{
	if (!($app->input->get('option', '') === 'com_installer' && $app->input->get('task', '') === 'update.update'))
	{
		require_once COM_FABRIK_FRONTEND . '/helpers/legacy/aliases.php';
	}
}
else
{
	$helpers = JFolder::files(COM_FABRIK_FRONTEND . '/helpers/', '.php');
	foreach ($helpers as $helper)
	{
		require_once(COM_FABRIK_FRONTEND . '/helpers/' . $helper);
	}
}

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/tables/fabtable.php';
require_once COM_FABRIK_FRONTEND . '/models/fabrik.php';
require_once COM_FABRIK_FRONTEND . '/models/plugin.php';
require_once COM_FABRIK_FRONTEND . '/models/element.php';
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';
require_once COM_FABRIK_FRONTEND . '/models/elementlist.php';
//require_once COM_FABRIK_FRONTEND . '/views/FabrikView.php';

if ($app->isAdmin())
{
	// Load in front end model path
	if ($input->get('option') !== 'com_acymailing')
	{
		JModelLegacy::addIncludePath(COM_FABRIK_FRONTEND . '/models', 'FabrikFEModel');
	}
	JModelLegacy::addIncludePath(COM_FABRIK_BACKEND . '/models', 'FabrikAdminModel');
	require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/fabrik.php';
}
