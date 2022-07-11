<?php
defined('JPATH_BASE') or die;

$d = $displayData;
if ($d->height <= 1) :
?>
<span class="fabrikinput fabrikElementReadOnly" style="display:inline-block;" name="<?php echo $d->name;?>" id="<?php echo $d->id;?>"><?php echo $d->value;?></span>
<?php
else : ?>
<textarea class="fabrikinput" disabled="disabled" name="<?php echo $d->name;?>"
	id="<?php echo $d->id;?>" cols="<?php echo $d->cols; ?>"
	rows="<?php echo $d->rows; ?>"><?php echo $d->value;?></textarea>
<?php endif; ?>