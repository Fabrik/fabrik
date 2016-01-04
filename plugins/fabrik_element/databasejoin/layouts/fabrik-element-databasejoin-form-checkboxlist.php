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

$colSize    = floor(floatval(12) / $d->optsPerRow);
$colCounter = 0;
$rowStarted = false;

foreach ($d->options as $option) :
	$checked = in_array($option->value, $d->default) ? 'checked="checked"' : '';
	$name = $d->name . '[' . $colCounter . ']';
	if (($colSize * $colCounter) % 12 === 0 || $colCounter == 0) :
		$rowStarted = true; ?>
		<div class="row-fluid" data-role="fabrik-rowopts">
	<?php endif;

	$d->option = $option;
	$d->option->checked = $checked;
	$d->colCounter = $colCounter;
	if ($d->editable) :
		echo $d->optionLayout->render($d);
	elseif ($checked) : ?>
		<span><?php echo $d->option->text;?></span>
	<?php endif;
	$colCounter++;
	if (($colSize * $colCounter) % 12 === 0 || $colCounter == 0) :
		$rowStarted = false; ?>
		</div>
	<?php endif;

endforeach;

// If the last element was not closing the row add an additional div
if ($rowStarted === true) :?>
	</div><!-- end checkboxlist row-fluid for open row -->
<?php endif;?>