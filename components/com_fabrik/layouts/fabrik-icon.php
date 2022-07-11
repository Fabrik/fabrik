<?php
/**
 * Default list element render
 * Override this file in plugins/fabrik_element/{plugin}/layouts/fabrik-element-{plugin}-list.php
 */

defined('JPATH_BASE') or die;

$d = $displayData;

/*
 * Some code just needs the icon name itself (eg. passing to JS code so it knows what icon class to add/remove,
 * like in the rating element.
 */
if (isset($d->nameOnly) && $d->nameOnly)
{
	echo $d->icon;
	return;
}

$props = isset($d->properties) ? $d->properties : '';
?>
<i data-isicon="true" class="<?php echo $d->icon;?>" <?php echo $props;?>></i>