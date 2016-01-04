<?php
defined('JPATH_BASE') or die;

$d = $displayData;
if ($d->optsPerRow < 1)
{
	$d->optsPerRow = 1;
}
if ($d->optsPerRow > 12)
{
	$d->optsPerRow = 12;
}

$colCounter = 0;
$rowChunks  = array();

foreach ($d->options as $option) :
	$checked = in_array($option->value, $d->default) ? 'checked="checked"' : '';
	$d->option = $option;
	$d->option->checked = $checked;
	$d->colCounter = $colCounter++;
	if ($d->editable) :
		$rowChunks[] = $d->optionLayout->render($d);
	elseif ($checked) :
		$rowChunks[] = '<span>' . $d->option->text . '</span>';
	endif;
endforeach;

$rowChunks = array_chunk($rowChunks, $d->optsPerRow);
foreach ($rowChunks as $chunk) :
	?>
	<div class="row-fluid" data-role="fabrik-rowopts" data-optsperrow="<?php echo $d->optsPerRow; ?>">
	<?php
	foreach ($chunk as $option) :
		echo $option;
	endforeach;
	?>
	</div>
	<?php
endforeach;
