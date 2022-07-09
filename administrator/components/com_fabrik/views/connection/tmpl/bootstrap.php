<?php
/**
 * Admin Connection Tmpl
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

	<div class="row-fluid">
		<?php if ($this->item->host != "") :?>
			<li>
				<label><?php echo FText::_('COM_FABRIK_ENTER_PASSWORD_OR_LEAVE_AS_IS'); ?></label>
			</li>
		<?php endif; ?>
		<fieldset class="form-horizontal">
	    	<legend>
	    		<?php echo FText::_('COM_FABRIK_DETAILS');?>
	    	</legend>
			<?php foreach ($this->form->getFieldset('details') as $this->field) :
				echo $this->loadTemplate('control_group');
			endforeach;
			?>
		</fieldset>
	</div>

	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
