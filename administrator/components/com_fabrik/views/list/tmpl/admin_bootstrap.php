<?php
/**
 * Admin List Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
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
			if (task !== 'list.cancel' && !Fabrik.controller.canSaveForm()) {
				window.alert('Please wait - still loading');
				return false;
			}
			if (task == 'list.cancel' || document.formvalidator.isValid(document.id('adminForm'))) {
				<?php echo $this->form->getField('introduction')->save(); ?>
				window.fireEvent('form.save');
				Joomla.submitform(task, document.getElementById('adminForm'));
			} else {
				window.alert('<?php echo $this->escape(FText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
			}
		});
	}
</script>

<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
	<div class="row-fluid" id="elementFormTable">

		<div class="span2">


				<ul class="nav nav-list"style="margin-top:40px">
					<li class="active">
						<a data-toggle="tab" href="#detailsX">
							<?php echo FText::_('COM_FABRIK_DETAILS')?>
						</a>
					</li>
					<li>
						<a data-toggle="tab" href="#data">
							<?php echo FText::_('COM_FABRIK_DATA')?>
						</a>
					</li>
					<li>
						<a data-toggle="tab" href="#publishing">
							<?php echo FText::_('COM_FABRIK_GROUP_LABEL_PUBLISHING_DETAILS')?>
						</a>
					</li>
					<li>
						<a data-toggle="tab" href="#access">
							<?php echo FText::_('COM_FABRIK_GROUP_LABEL_RULES_DETAILS')?>
						</a>
					</li>
					<li>
						<a data-toggle="tab" href="#tabplugins">
							<?php echo FText::_('COM_FABRIK_GROUP_LABEL_PLUGINS_DETAILS')?>
						</a>
					</li>
				</ul>
		</div>
		<div class="span10">

			<div class="tab-content">
				<?php
				echo $this->loadTemplate('details');
				echo $this->loadTemplate('data');
				echo $this->loadTemplate('publishing');
				echo $this->loadTemplate('plugins');
				echo $this->loadTemplate('access');
				?>
			</div>

			<input type="hidden" name="task" value="" />
			<?php echo JHtml::_('form.token'); ?>
		</div>
	</div>
</form>
