<?php
/**
 * Bootstrap Details Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$element = $this->element;
?>
<div class="<?php echo $element->containerClass .' '. $element->span;?>">
	<div class="span4 fabrikLabel">
		<?php echo $element->label_raw;?>
	</div>
	<div class="span8">
		<?php if ($this->tipLocation == 'above') : ?>
			<p class=""><?php echo $element->tipAbove ?></p>
		<?php endif ?>

		<div class="fabrikElement">
			<?php echo $element->element;?>
		</div>

		<?php if ($this->tipLocation == 'side') : ?>
			<p class=""><?php echo $element->tipSide ?></p>
		<?php endif ?>


	<?php if ($this->tipLocation == 'below') :?>
		<p class=""><?php echo $element->tipBelow ?></p>
	<?php endif ?>
	</div>
</div><!--  end span -->
