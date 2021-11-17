<?php
/**
 * Admin Connection Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
FabrikHelperHTML::formvalidation();
JHtml::_('behavior.keepalive');
?>

<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
	<div class="width-100 fltlft">
		<fieldset class="adminform">
			<legend><?php echo FText::_('COM_FABRIK_DETAILS');?></legend>
			<ul class="adminformlist">
				<li>
					<?php echo $this->form->getLabel('description') . $this->form->getInput('description'); ?>
				</li>

				<li>
					<?php echo $this->form->getLabel('host') . $this->form->getInput('host'); ?>
				</li>

				<li>
					<?php echo $this->form->getLabel('database') . $this->form->getInput('database'); ?>
				</li>

				<li>
					<?php echo $this->form->getLabel('user') . $this->form->getInput('user'); ?>
				</li>

			<?php if ($this->item->host != ""){?>
				<li>
					<label><?php echo FText::_('COM_FABRIK_ENTER_PASSWORD_OR_LEAVE_AS_IS'); ?></label>
				</li>
			<?php } ?>

				<li>
					<?php echo $this->form->getLabel('password') . $this->form->getInput('password'); ?>
				</li>

				<li>
					<?php echo $this->form->getLabel('passwordConf') . $this->form->getInput('passwordConf'); ?>
				</li>

				<li>
					<?php echo $this->form->getLabel('published') . $this->form->getInput('published'); ?>
				</li>

				<li>
					<?php echo $this->form->getLabel('id') . $this->form->getInput('id'); ?>
				</li>
			</ul>
			<div class="clr"> </div>

		</fieldset>

	</div>
	<div class="clr"></div>

	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
