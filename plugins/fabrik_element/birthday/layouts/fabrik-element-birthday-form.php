<?php
defined('JPATH_BASE') or die;

$d = $displayData;
?>

<div class="fabrikSubElementContainer" id="<?php echo $d->id;?>">
	<?php echo JHTML::_('select.genericlist', $d->day_options, $d->day_name, $d->attribs." input-mini aria-label='".$d->day_label."'", 'value', 'text', $d->day_value, $d->day_id); ?>
	<?php echo $d->separator;?>
	<?php echo JHTML::_('select.genericlist', $d->month_options, $d->month_name, $d->attribs." input-small aria-label='".$d->month_label."'", 'value', 'text', $d->month_value, $d->month_id); ?>
	<?php echo $d->separator;?>
	<?php echo JHTML::_('select.genericlist', $d->year_options, $d->year_name, $d->attribs." input-mini aria-label='".$d->year_label."'", 'value', 'text', $d->year_value, $d->year_id); ?>
</div>
