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
?>
<table>
<tr>
<td>County</td>
<td><?php echo $d->multiName; ?></td>
<td><?php echo $d->extraFKName . ' - planned as hidden'; ?></td>
</tr>

<?php
foreach ($d->options as $option) :
	$name = $d->name . '[' . $colCounter . ']';
	$extraPKVal = $d->extraPKVal;
	if (($colSize * $colCounter) % 12 === 0 || $colCounter == 0) :
		$rowStarted = true;
	 endif; ?>
	<tr>
	<?php

	$d->option = $option;
	$d->colCounter = $colCounter;
	if ($d->editable) :
		echo $d->optionLayout->render($d);
	else : ?>
		<span><?php echo $d->option->text;?></span>
	<?php endif;
	$colCounter++;
	if (($colSize * $colCounter) % 12 === 0 || $colCounter == 0) :
		$rowStarted = false;
	endif;	?>
	</tr>
	<?php 

endforeach;

// If the last element was not closing the row add an additional div
if ($rowStarted === true) :?>
	</dd><!-- end checkboxlist row-fluid for open row -->
<?php endif;?>
</table>