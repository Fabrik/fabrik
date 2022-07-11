<?php
/**
 * Tabs layout
 */

defined('JPATH_BASE') or die;

$d = $displayData;

$thisName = $d->type === 'checkbox' ? FabrikString::rtrimword($d->name, '[]') . '[' . $d->i . ']' : $d->name;
$thisId = str_replace(array('[', ']'), array('_', '_'), $thisName);
$thisId = rtrim($thisId, '_');
$thisId .=  '_input_' . $d->i;

$label    = '<span>' . $d->label . '</span>';

$inputClass = FabrikWorker::j3() ? '' : $d->type;

if (array_key_exists('input', $d->classes))
{
	$inputClass .= ' ' . implode(' ', $d->classes['input']);
}

$chx = '<input type="' . $d->type . '" class="fabrikinput ' . $inputClass . '" ' . $d->inputDataAttributes .
	' name="' . $thisName . '" id="' . $thisId . '" value="' . $d->value . '" ';

$sel = in_array($d->value, $d->selected);
$chx .= $sel ? ' checked="checked" />' : ' />';
$labelClass = FabrikWorker::j3() && !$d->buttonGroup ? $d->type : '';

if (array_key_exists('label', $d->classes))
{
	$labelClass .= ' ' . implode(' ', $d->classes['label']);
}

$html = $d->elementBeforeLabel == '1' ? $chx . $label : $label . $chx;
?>
<label for="<?php echo $thisId; ?>" class="fabrikgrid_<?php echo FabrikString::clean($d->value) . ' ' . $labelClass; ?>">
	<?php echo $html; ?>
</label>
