<?php $element = $this->element;?>
<div class="control-group <?php echo $element->containerClass . $element->span; ?>">
	<?php echo $element->label;?>

	<?php if ($this->tipLocation == 'above') : ?>
		<span class="help-block"><?php echo $element->tipAbove ?></span>
	<?php endif ?>

	<div class="fabrikElement">
		<?php echo $element->element;?>
	</div>

	<div class="<?php echo $this->class?>">
		<?php echo $element->error ?>
	</div>

	<?php if ($this->tipLocation == 'side') : ?>
		<span class="help-block"><?php echo $element->tipSide ?></span>
	<?php endif ?>

	<?php if ($this->tipLocation == 'below') :?>
		<span class="help-block"><?php echo $element->tipBelow ?></span>
	<?php endif ?>
</div>


