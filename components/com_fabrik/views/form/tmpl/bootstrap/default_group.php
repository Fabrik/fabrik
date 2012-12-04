<?php foreach ($this->elements as $element) :
	$class = 'fabrikErrorMessage';
	if (trim($element->error) !== '') :
		$element->error = '<i class=" icon-warning"></i> ' . $element->error;
		//$class .= ' alert alert-error';
		$element->containerClass .= ' error';
		$class .= ' help-inline';
	endif;
?>

	<div class="control-group <?php echo $element->containerClass; ?>">

	<?php echo $element->label;?>
	<div class="controls">

		<?php if ($this->tipLocation == 'above') : ?>
			<p class="help-block"><?php echo $element->tipAbove ?></p>
		<?php endif ?>


		<div class="fabrikElement">
			<?php echo $element->element;?>
		</div>

		<div class="<?php echo $class?>">
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

<?php endforeach; ?>
