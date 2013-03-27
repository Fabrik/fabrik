	<?php $element = $this->element; ?>
<div class="control-group <?php echo $element->containerClass . $element->span; ?>">
	<?php echo $element->label;?>
	<div class="controls">

		<?php if ($this->tipLocation == 'above') : ?>
			<p class="help-block"><?php echo $element->tipAbove ?></p>
		<?php endif ?>

		<div class="fabrikElement">
			<?php echo $element->element;?>
		</div>

		<div class="<?php echo $this->class?>">
			<?php echo $element->error ?>
		</div>

		<?php if ($this->tipLocation == 'side') : ?>
			<p class="help-block"><?php echo $element->tipSide ?></p>
		<?php endif ?>

	</div>

	<?php if ($this->tipLocation == 'below') :?>
		<p class="help-block"><?php echo $element->tipBelow ?></p>
	<?php endif ?>

</div>
