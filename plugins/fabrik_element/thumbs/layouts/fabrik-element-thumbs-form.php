<?php

defined('JPATH_BASE') or die;

$d = $displayData;
?>
<?php
if ($d->j3) :
	?>
	<div class="btn-group">
		<button <?php echo $d->commentdata;?> data-fabrik-thumb-formid="<?php echo $d->formId;?>"
			data-fabrik-thumb="up" class="btn btn-small thumb-up<?php echo $d->upActiveClass;?>">
			<?php echo FabrikHelperHTML::image('thumbs-up', 'form', $d->tmpl); ?>
		<span class="thumb-count"><?php echo $d->countUp;?>
		</span>
		</button>
		<?php
		if ($d->showDown) :
			?>
			<button <?php echo $d->commentdata;?> data-fabrik-thumb-formid="<?php echo $d->formId;?>"
				data-fabrik-thumb="down" class="btn btn-small thumb-down<?php echo $d->downActiveClass;?>">
				<?php echo FabrikHelperHTML::image('thumbs-down', 'form', $d->tmpl); ?>
				<span class="thumb-count"><?php echo $d->countDown;?></span>
			</button>
		<?php
		endif;
		?>

	</div>
<?php
else :
	?>
	<span style="color:#32d723;" id="count_thumbup"><?php echo $d->countUp;?></span>
	<img src="<?php echo $d->imagepath . $d->imagefileup;?>" style="padding:0px 5px 0 1px;" alt="UP" id="thumbup" />

	<?php
	if ($d->showDown) :
		?>
		<span style="color:#f82516;" id="count_thumbdown"><?php echo $d->countDown;?></span>
		<img src="<?php echo $d->imagepath . $d->imagefiledown; ?>" style="padding:0px 5px 0 1px;" alt="DOWN" id="thumbdown" />
	<?php
	endif;

endif;
?>

<input type="hidden" name="<?php echo $d->name;?>"
	id="<?php echo $d->id;?>" value="<?php echo $d->countDiff;?>"
	class="<?php echo $d->id;?>" />
