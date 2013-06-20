<?php
// Bootstrap form template: Used for showing elements in details view

foreach ($this->elements as $element) :
	 if (!$element->hidden) {
		if ($element->startRow) :?>
			<div class="row-fluid">
		<?php
		endif;
		?>

		<div class="<?php echo $element->span;?>">
			<div class="row-fluid">
				<div class="span4"><em><?php echo $element->label_raw ?></em></div>
				<div class="span8"><?php echo $element->element;?></div>
			</div>
		</div>

	<?php
	if ($element->endRow) :
	?>
		</div>
	<?php
	endif;
	}
endforeach; ?>
