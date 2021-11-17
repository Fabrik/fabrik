<?php
/**
 * Admin Cron Edit Tmpl
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
JHTML::stylesheet('administrator/components/com_fabrik/views/fabrikadmin.css');
JHtml::_('behavior.tooltip');
FabrikHelperHTML::formvalidation();
JHtml::_('behavior.keepalive');

?>
<script type="text/javascript">

	Joomla.submitbutton = function(task) {
		requirejs(['fab/fabrik'], function (Fabrik) {
			if (task !== 'cron.cancel' && !Fabrik.controller.canSaveForm()) {
				window.alert('Please wait - still loading');
				return false;
			}
			if (task == 'cron.cancel' || document.formvalidator.isValid(document.id('adminForm'))) {
				window.fireEvent('form.save');
				Joomla.submitform(task, document.getElementById('adminForm'));
			} else {
				window.alert('<?php echo $this->escape(FText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
			}
		});
	}
</script>
<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

	<div class="row-fluid">
		<div class="span6">
			<fieldset class="form-horizontal">
		    	<legend>
		    		<?php echo FText::_('COM_FABRIK_DETAILS');?>
		    	</legend>
				<?php
				foreach ($this->form->getFieldset('details') as $key => $this->field) :
					if ($key !== 'jform_plugin')
					{
						echo $this->loadTemplate('control_group');
					}
					else
					{
						// Defer the plug-in field to the end
						$pluginField = $this->field;
					}
				endforeach;
				foreach ($this->form->getFieldset('connection') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				$this->field = $pluginField;
				echo $this->loadTemplate('control_group');
				?>
			</fieldset>

		</div>

		<div class="span6">

			<fieldset class="form-horizontal">
		    	<legend>
		    		<?php echo FText::_('COM_FABRIK_RUN');?>
		    	</legend>
				<?php foreach ($this->form->getFieldset('run') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>

			<fieldset class="form-horizontal">
		    	<legend>
		    		<?php echo FText::_('COM_FABRIK_LOG');?>
		    	</legend>
				<?php foreach ($this->form->getFieldset('log') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span12">
			<fieldset class="form-horizontal">
				<div id="plugin-container">
					<?php echo $this->pluginFields;?>
				</div>
			</fieldset>
		</div>
	</div>
	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
