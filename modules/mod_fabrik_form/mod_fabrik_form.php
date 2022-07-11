<?php
/**
 * Fabrik Module to display Fabrik Form on a Joomla page
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Ensure the package is set to fabrik
$prevUserState = $app->getUserState('com_fabrik.package');
$app->setUserState('com_fabrik.package', 'fabrik');

require JPATH_SITE . '/modules/mod_fabrik_form/mod_fabrik_form_boot.php';

// Set the package back to what it was before rendering the module
$app->setUserState('com_fabrik.package', $prevUserState);
