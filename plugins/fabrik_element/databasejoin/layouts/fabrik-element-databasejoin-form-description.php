<?php
/**
 * Database join and Cascading drop down front end select layout
 */
defined('JPATH_BASE') or die;

$d = $displayData;
?>
<div class="dbjoin-description">
	<?php
	// @FIXME - if read only, surely no need to insert every possible value, we just need the selected one?
	for ($i = 0; $i < count($d->opts); $i++) :
		$opt = $d->opts[$i];
		$display = $opt->value == $d->defaultRaw ? '' : ' style="display: none"';
		// phil changed - this will mess up list - 
		//$c = $d->showPleaseSelect ? $i + 1 : $i;		
		// also, pk value now used instead of i as part of id an identifier (instead of class)  		
	?>
		<div class="notice" id="<?php echo 'dbjdesc-'.$d->fullElName.'-'.$opt->value;?>"<?php echo $display;?>>
			<?php echo $opt->description; ?>
		</div>
	<?php
	endfor;
	?>
</div>
