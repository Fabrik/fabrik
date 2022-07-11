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
$label = isset($d->option) ? $d->option->text : '';
$value = isset($d->option) ? $d->option->value : '';
$checked = isset($d->checked ) ? $d->checked : '';
$colSize    = floor(floatval(12) / $d->optsPerRow);
$colClass = (int) $colSize === 12 ? '' : 'class="span' . $colSize . '" ';
?>
<div <?php echo $colClass;?> data-role="suboption">
	<label class="radio">
		<input type="radio" value="<?php echo $value;?>" <?php echo $checked;?> data-role="fabrikinput" name="<?php echo $d->name; ?>" class="fabrikinput" />
		<span><?php echo $label;?></span>
	</label>
</div>
