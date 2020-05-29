<?php
/**
 * Layout: List filters
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.4
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$d             = $displayData;

$extraClass = '';

switch ($d->tmpl)
{
	case 'div':
		$extraClass = 'fabrik_row well row-striped';
}

return $extraClass;

