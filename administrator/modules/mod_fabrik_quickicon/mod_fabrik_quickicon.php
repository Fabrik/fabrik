<?php
/**
 * @package		Joomla.Administrator
 * @subpackage	mod_fabrik_quickicon
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// No direct access.
defined('_JEXEC') or die;

require_once dirname(__FILE__).'/helper.php';

$buttons = modFabrik_QuickIconHelper::getButtons($params);

require JModuleHelper::getLayoutPath('mod_fabrik_quickicon', $params->get('layout', 'default'));
