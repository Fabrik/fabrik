<?php
/**
 * Generic global element description layout
 */
defined('JPATH_BASE') or die;

$d = $displayData;

?>
<div class="<?php echo $d->opts['elname'];?>___description">
	<?php echo $d->opts['description']; ?>
</div>
