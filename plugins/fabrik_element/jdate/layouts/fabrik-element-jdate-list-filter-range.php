<?php
defined('JPATH_BASE') or die;

use Joomla\Utilities\ArrayHelper;

$d    = $displayData;
$from = $d->from;
$to   = $d->to;

//$prepend = $d->j3 ? '<div class="input-append">' : '';
$prepend = $d->j3 ? '<div style="display: inline-block">' : '';
$append  = $d->j3 ? '</div>' : '';

if ($d->filterType === 'range-hidden') :
	?>
	<input type="hidden" name="<?php echo $from->name; ?>"
		class="<?php echo $d->class; ?>"
		value="<?php echo $from->value; ?>"
		id="<?php echo $d->htmlId; ?>-0" />

	<input type="hidden" name="<?php echo $to->name; ?>"
		class="<?php echo $d->class; ?>"
		value="<?php echo $to->value; ?>"
		id="<?php echo $d->htmlId; ?>-1" />
<?php
else :
	?>
<div class="fabrikDateListFilterRange">
	<div style="text-align: right">
	<?php echo FText::_('COM_FABRIK_DATE_RANGE_BETWEEN') . ' '; ?>
	<?php echo $prepend; ?>
    <?php echo $d->jCalFrom; ?>
	<?php echo $append; ?>
	</div>
	<div style="text-align: right">
	<?php echo FText::_('COM_FABRIK_DATE_RANGE_AND') . ' '; ?>
	<?php echo $prepend; ?>
    <?php echo $d->jCalTo; ?>
	<?php echo $append; ?>
	</div>
</div>
<?php
endif;