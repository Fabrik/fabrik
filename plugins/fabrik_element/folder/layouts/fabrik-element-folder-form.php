<?php
defined('JPATH_BASE') or die;

$d = $displayData;

?>
<select id="<?php echo $d->id?>" class="fabrikinput inputbox <?php echo $d->errorCss;?>" name="<?php echo $d->name;?>">
	<?php foreach ($d->options as $option) :
		$selected = $option->value === $d->selected ? ' selected="selected" ' : ''; ?>
		<option value="<?php echo $option->value;?>" <?php echo $selected; ?>>
			<?php echo $option->text;?>
		</option>
	<?php endforeach; ?>
</select>