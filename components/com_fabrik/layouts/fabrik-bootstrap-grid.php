<?php
/**
 * Bootstrap grid layout
 *
 * NOTE - this layout must implode the grid with \n, as the calling func in HTML helper has an 'explode' arg,
 * which controls whether grid gets reutrned as string, or an array.
 */

defined('JPATH_BASE') or die;

$d = $displayData;

$span = floor(12 / $d->columns);
$i    = 0;
$id   = is_null($d->spanId) ? '' : ' id="' . $d->spanId . '"';
$grid = array();

foreach ($d->items as $i => $s)
{
	$endLine = ($i !== 0 && (($i) % $d->columns == 0));
	$newLine = ($i % $d->columns == 0);

	if ($endLine)
	{
		$grid[] = '</div><!-- grid close row -->';
	}

	if ($newLine)
	{
		$grid[] = '<div class="row-fluid">';
	}

	$grid[] = '<div class="' . $d->spanClass . ' span' . $span . '"' . $id . '>' . $s . '</div>';
}

if (!empty($d->items))
{
	// Close opened row, last row-fluid is always open if there's data
	$grid[] = '</div><!-- grid close end row -->';
}

echo implode("\n", $grid);
