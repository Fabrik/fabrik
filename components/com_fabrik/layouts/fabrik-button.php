<?php
/**
 * Button layout
 */

defined('JPATH_BASE') or die;

$d          = $displayData;
$attributes = isset($d->attributes) ? $d->attributes : '';
$type       = isset($d->type) ? 'type="' . $d->type . '"' : 'type="button"';
$tag        = isset($d->tag) ? $d->tag : 'button'; // button or a
$name       = isset($d->name) ? 'name="' . $d->name . '"' : '';
$aria       = isset($d->aria) ? $d->aria : '';
$id 	    = isset($d->id) ? 'id="' . $d->id .'"' : '';
?>

<<?php echo $tag; ?> <?php echo $type; ?> class="btn <?php echo $d->class; ?>" <?php echo $attributes; ?>
	<?php echo $name; ?> <?php echo $id; ?> <?php echo $aria; ?>>
	<?php echo $d->label; ?>
</<?php echo $tag; ?>>

