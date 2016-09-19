<?php
/**
 * Tabs Form Template: Group
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<ul>
<?php foreach ($this->elements as $element) :
	if ($this->tipLocation == 'above' && $element->tipAbove != '') :?>
		<li><?php echo $element->tipAbove; ?></li>
	<?php endif; ?>
	<li <?php echo @$element->column;?> class="<?php echo $element->containerClass; ?>">
		<?php echo $element->label; ?>
		<?php echo $element->errorTag; ?>
		<div class="fabrikElement">
			<?php echo $element->element; ?>
		</div>
		<?php if ($this->tipLocation == 'side') :
			echo $element->tipSide;
		endif; ?>
		<div class="fabrikErrorMessage">
			<?php echo $element->error;?>
		</div>
		<div style="clear:both"></div>
	</li>
	<?php if ($this->tipLocation == 'below' && $element->tipBelow != '') :?>
		<li><?php echo $element->tipBelow; ?></li>
	<?php endif;
endforeach; ?>
</ul>