<?php
/**
 * Cascading drop down front end select layout
 */
defined('JPATH_BASE') or die;

$d = $displayData;

?>
<div class="dbjoin-description">
	<?php
	// @FIXME - if read only, surely no need to insert every possible value, we just need the selected one?
	for ($i = 0; $i < count($d->opts); $i++) :
		$opt = $d->opts[$i];
		$display = $opt->value == $d->default ? '' : 'style="display: none" ';
		$c = $d->showPleaseSelect ? $i + 1 : $i;
	?>
		<div <?php echo $display ;?> class="notice description-<?php echo  $c; ?>">
			<?php echo $opt->description; ?>
		</div>
	<?php
	endfor;
	?>
</div>
