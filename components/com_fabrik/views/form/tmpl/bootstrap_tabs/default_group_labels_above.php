<?php
/**
 * Bootstrap Tabs Form Template: Labels Above
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$element = $this->element;
?>
<?php echo $element->label;?>

<?php if ($this->tipLocation == 'above') : ?>
	<span class=""><?php echo $element->tipAbove ?></span>
<?php endif ?>

<div class="fabrikElement">
	<?php echo $element->element;?>
</div><!-- end fabrikElement -->

<div class="<?php echo $this->class?>">
	<?php echo $element->error ?>
</div><!-- end element error -->

<?php if ($this->tipLocation == 'side') : ?>
	<span class=""><?php echo $element->tipSide ?></span>
<?php endif ?>

<?php if ($this->tipLocation == 'below') :?>
	<span class=""><?php echo $element->tipBelow ?></span>
<?php endif ?>
