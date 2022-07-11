<?php
defined('JPATH_BASE') or die;

use Joomla\Utilities\ArrayHelper;

$d = $displayData;

$data = 'data-filter-name="' . $d->elementName . '" ';
// Order of the selects is important - do not change
?>

<select name="<?php echo $d->name . '[year]'; ?>" class="input-small fabrik_filter" <?php echo $data; ?> >
	<?php foreach ($d->years as $item) :
		$selected = ArrayHelper::getValue($d->default, 0) == $item->value ? 'selected' : '' ?>
		<option value="<?php echo $item->value; ?>" <?php echo $selected; ?>>
			<?php echo $item->text; ?>
		</option>
	<?php endforeach; ?>
</select>

<select name="<?php echo $d->name . '[month]'; ?>" class="input-small fabrik_filter" <?php echo $data; ?> >
	<?php foreach ($d->months as $item) :
		$selected = ArrayHelper::getValue($d->default, 1) == $item->value ? 'selected' : '' ?>
		<option value="<?php echo $item->value; ?>" <?php echo $selected; ?>>
			<?php echo $item->text; ?>
		</option>
	<?php endforeach; ?>
</select>

<select name="<?php echo $d->name . '[day]'; ?>" class="input-small fabrik_filter" <?php echo $data; ?> >
	<?php foreach ($d->days as $item) :
		$selected = ArrayHelper::getValue($d->default, 2) == $item->value ? 'selected' : '' ?>
		<option value="<?php echo $item->value; ?>" <?php echo $selected; ?>>
			<?php echo $item->text; ?>
		</option>
	<?php endforeach; ?>
</select>
