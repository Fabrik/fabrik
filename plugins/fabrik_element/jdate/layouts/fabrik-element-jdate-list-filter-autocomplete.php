<?php
defined('JPATH_BASE') or die;

$d    = $displayData;

?>

<input type="hidden" name="<?php echo $d->name; ?>" class="<?php echo $d->class; ?>"
	value="<?php echo $d->default; ?>" id="<?php echo $d->htmlId; ?>" />

<input type="text" name="<?php echo $d->name; ?>-auto-complete"
	class="<?php echo $d->class; ?> autocomplete-trigger" value="<?php echo $d->default; ?>"
	id="<?php echo $d->htmlId; ?>-auto-complete" />