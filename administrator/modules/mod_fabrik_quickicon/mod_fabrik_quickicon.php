<?php
/**
 * Fabrik Admin QuickIcon
 *
 * @package     Joomla.Administrator
 * @subpackage  mod_fabrik_quickicon
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once dirname(__FILE__) . '/helper.php';

$buttons   = modFabrik_QuickIconHelper::getButtons($params);
$lists     = modFabrik_QuickIconHelper::listIcons();
$menuLinks = $params->get('show_menu_links', 1);

require JModuleHelper::getLayoutPath('mod_fabrik_quickicon', $params->get('layout', 'default'));
