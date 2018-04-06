<?php
defined('JPATH_BASE') or die;

$d         = $displayData;
$multiple  = $d->multiple ? 'multiple' : '';
$multisize = $d->multisize === '' ? '' : 'size="' . $d->multisize . '"';
?>

<select name="<?php echo $d->name ?>" id="<?php echo $d->id ?>" <?php echo $multiple; ?>
	<?php echo $multisize; ?> <?php echo $d->attribs; ?>>
	<?php foreach ($d->options as $opt) :
		$disabled = $opt->disable === true ? ' disabled' : ''; 
		$selected = in_array($opt->value, $d->selected) ? ' selected="selected" ' : ''; ?>
		<option value="<?php echo $opt->value;?>" <?php  echo $disabled; ?><?php echo $selected; ?>><?php echo JText::_($opt->text); ?></option>
	<?php endforeach; ?>
</select>
