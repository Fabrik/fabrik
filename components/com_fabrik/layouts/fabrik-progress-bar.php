<?php
/**
 * Default list element render
 * Override this file in plugins/fabrik_element/{plugin}/layouts/fabrik-element-{plugin}-list.php
 */

defined('JPATH_BASE') or die;

$d = $displayData;

$klass = '';

$stripped = isset($d->stripped) && $d->stripped === true ? 'progress-striped': '';
$extraClass = isset($d->extraClass) ? $d->extraClass : '';
$extraStyle = isset($d->extraStyle) ? $d->extraStyle : '';
$animated = isset($d->animated) && $d->animated === true ? 'active' : '';
$value = isset($d->value) ? (int) $d->value : 0;

if (isset($d->context)) {
	switch ($d->context) {
		case 'success':
			$klass = 'bar-success';
			break;
		case 'info':
			$klass = 'bar-info';
			break;
		case 'warning':
			$klass = 'bar-warning';
			break;
		case 'danger':
			$klass = 'bar-danger';
			break;

	}
}

?>
<div class="progress <?php echo $stripped;?> <?php echo $extraClass;?>" style="margin-left=6px; <?php echo $extraStyle; ?>">
	<div class="bar <?php echo $klass;?> <?php echo $animated;?>" role="progressbar"
	aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $value;?>%;"></div>
</div>