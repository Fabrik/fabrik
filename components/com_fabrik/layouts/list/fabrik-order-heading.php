<?php
/**
 * Default list element render
 * Override this file in plugins/fabrik_element/{plugin}/layouts/fabrik-element-{plugin}-list.php
 */

defined('JPATH_BASE') or die;

$d = $displayData;
$d->class = '';
$heading = '';
$img = '';
$headingProperties = array(
		'data-sort-asc-icon' => 'icon-arrow-up',
		'data-sort-desc-icon' => 'icon-arrow-down',
		'data-sort-icon' => 'icon-menu-2'
);

switch ($d->orderDir)
{
	case 'desc':
		$d->orderDir = '-';
		$d->class = 'class="fabrikorder-desc"';
		$img = FabrikHelperHTML::image('arrow-up', 'list', $d->tmpl, array('alt' => FText::_('COM_FABRIK_ORDER')));
		break;
	case 'asc':
		$d->orderDir = 'desc';
		$d->class = 'class="fabrikorder-asc"';
		$img = FabrikHelperHTML::image('arrow-down', 'list', $d->tmpl, array('alt' => FText::_('COM_FABRIK_ORDER')));
		break;
	case '':
	case '-':
		$d->orderDir = 'asc';
		$d->class = 'class="fabrikorder"';
		$img = FabrikHelperHTML::image('menu-2', 'list', $d->tmpl, array('alt' => FText::_('COM_FABRIK_ORDER')));
		break;
}

if ($d->class === '')
{
	if (in_array($d->key, $d->orderBys))
	{
		if ($d->item->order_dir === 'desc')
		{
			$d->class = 'class="fabrikorder-desc"';
			$img = FabrikHelperHTML::image('arrow-up.png', 'list', $d->tmpl, array('alt' => FText::_('COM_FABRIK_ORDER')));
		}
	}
}

if ($d->elementParams->get('can_order', false))
{
	$heading = '<a ' . $d->class . ' ' . FabrikHelperHTML::propertiesFromArray($headingProperties) . ' href="#">' . $img . $d->label . '</a>';
}
else
{
	$img = $d->orderDir === 'asc' ? '' : $img;
	$heading = $img . $d->label;
}

echo $heading;
