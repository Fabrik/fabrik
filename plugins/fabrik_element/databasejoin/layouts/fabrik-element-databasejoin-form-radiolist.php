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
	$d->checked = in_array($option->value, $d->default) ? 'checked="checked" ' : '';
	if (($colSize * $colCounter) % 12 === 0  || $colCounter == 0) :
		$rowStarted = true; ?>
		<div class="row-fluid fabrik-option-value-<?php echo $option->value; ?>" data-role="fabrik-rowopts" data-optsperrow="<?php echo $d->optsPerRow; ?>">
	<?php endif;
	$d->option = $option;
	$d->colCounter = $colCounter;
	if ($d->editable) :
		echo $d->optionLayout->render($d);
	elseif ($d->checked) : ?>
		<span><?php echo $d->option->text;?></span>
	<?php endif;
	$colCounter++;
	if (($colSize * $colCounter) % 12 === 0 || $colCounter == 0) :
		$rowStarted = false; ?>
		</div><!--end radiolist rowfluid-->
	<?php endif;

endforeach;

// If the last element was not closing the row add an additional div
if ($rowStarted === true) :?>
	</div><!-- end radiolist row-fluid for open row -->
<?php endif;?>

