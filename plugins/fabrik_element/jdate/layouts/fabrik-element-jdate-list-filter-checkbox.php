<?php
defined('JPATH_BASE') or die;

$d    = $displayData;

echo implode("\n", FabrikHelperHTML::grid($d->values, $d->labels, $d->default, $d->name,
	'checkbox', false, 1, array('input' => array('fabrik_filter'))));
