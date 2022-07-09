<?php
/**
 * Layout: List Limit Box
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.3.3
 */

$d = $displayData;

// Initialize variables
$limits = array();
$values = array();

for ($i = 5; $i <= 30; $i += 5)
{
	$values[] = $i;
}

$values[] = 50;
$values[] = 100;

if (!in_array($d->startLimit, $values))
{
	$values[] = $d->startLimit;
}

asort($values);

foreach ($values as $v)
{
	$limits[] = JHTML::_('select.option', $v);
}

if ($d->showAllOption == true)
{
	$limits[] = JHTML::_('select.option', '-1', FText::_('COM_FABRIK_ALL'));
}

$selected   = $d->viewAll ? '-1' : $d->limit;
$js         = '';
$attributes = 'class="inputbox input-mini" size="1" onchange="' . $js . '"';
$html       = JHTML::_('select.genericlist', $limits, 'limit' . $d->id, $attributes, 'value', 'text', $selected);

echo $html;
