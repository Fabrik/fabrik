<?php
/**
 * Bootstrap Form Template: Group Labels Side
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$element = $this->element; ?>
<div class="control-group <?php echo $element->containerClass . $element->span; ?>" <?php echo $element->containerProperties?>>
	<?php echo $element->label;?>

	<div class="controls">
		<?php if ($this->tipLocation == 'above') : ?>
			<p class="help-block"><?php echo $element->tipAbove ?></p>
		<?php endif ?>

		<div class="fabrikElement">
			<?php echo $element->element;?>
		</div>

		<span class="<?php echo $this->class?>">
			<?php echo $element->error ?>
		</span>

		<?php if ($this->tipLocation == 'side') : ?>
			<div class="help-block"><?php echo $element->tipSide ?></div>
		<?php endif ?>

	</div>

	<?php if ($this->tipLocation == 'below') :?>
		<div class="help-block"><?php echo $element->tipBelow ?></div>
	<?php endif ?>

</div><!--  end span -->
