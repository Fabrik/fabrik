<?php
/**
 * Skeleton Form Module
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$app = JFactory::getApplication();
$input = $app->input;
$option = $input->getCmd('option');

// Set option and package to {package}
$input->set('option', 'com_{component_name}');
$prevUserState = $app->getUserState('com_fabrik.package', 'fabrik');
$app->setUserState('com_fabrik.package', '{component_name}');

require_once  JPATH_SITE . '/modules/mod_fabrik_form/mod_fabrik_form_boot.php';

// Revert option and package back to component name
$app->setUserState('com_fabrik.package', $prevUserState);
$input->set('option', $option);
