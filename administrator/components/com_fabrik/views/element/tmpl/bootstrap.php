<?php
/**
 * Admin Element Edit Tmpl
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
JHtml::_('behavior.framework', true);
FabrikHelperHTML::formvalidation();
JHtml::_('behavior.keepalive');

JText::script('COM_FABRIK_SUBOPTS_VALUES_ERROR');
?>

<script type="text/javascript">

	Joomla.submitbutton = function(task) {
		requirejs(['fab/fabrik'], function (Fabrik) {
			if (task !== 'element.cancel' && !Fabrik.controller.canSaveForm()) {
				window.alert('Please wait - still loading');
				return false;
			}
			if (task == 'element.cancel' || document.formvalidator.isValid(document.id('adminForm'))) {
				window.fireEvent('form.save');
				Joomla.submitform(task, document.getElementById('adminForm'));
			} else {
				window.alert('<?php echo $this->escape(FText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
			}
		});
	}
</script>
<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

<?php if ($this->item->parent_id != 0)
{
	?>
	<div id="system-message" class="alert alert-notice">
		<strong><?php echo FText::_('COM_FABRIK_ELEMENT_PROPERTIES_LINKED_TO') ?>: <?php echo $this->parent->label ?></strong>

		<p><a href="#" id="swapToParent" class="element_<?php echo $this->parent->id ?>"><span class="icon-pencil"></span>
		<?php echo FText::_('COM_FABRIK_EDIT') . ' ' . $this->parent->label ?></a></p>

		<label><?php echo FText::_('COM_FABRIK_OR')?> <span class="icon-magnet"></span>
		<input id="unlink" name="unlink" id="unlinkFromParent" type="checkbox"> <?php echo FText::_('COM_FABRIK_UNLINK') ?>
		</label>
	</div>
<?php
}?>
	<div class="row-fluid" id="elementFormTable">

		<div class="span2">

			<ul class="nav nav-list">
				<li class="active">
			    	<a data-toggle="tab" href="#tab-details">
			    		<?php echo FText::_('COM_FABRIK_DETAILS')?>
			    	</a>
			    </li>
			    <li>
			    	<a data-toggle="tab" href="#tab-publishing">
			    		<?php echo FText::_('COM_FABRIK_PUBLISHING')?>
			    	</a>
			    </li>
			    <li>
			    	<a data-toggle="tab" href="#tab-access">
			    		<?php echo FText::_('COM_FABRIK_GROUP_LABEL_RULES_DETAILS')?>
			    	</a>
			    </li>
			    <li>
			    	<a data-toggle="tab" href="#tab-listview">
			    		<?php echo FText::_('COM_FABRIK_LIST_VIEW_SETTINGS')?>
			    	</a>
			    </li>
			    <li>
			    	<a data-toggle="tab" href="#tab-validations">
			    		<?php echo FText::_('COM_FABRIK_VALIDATIONS')?>
			    	</a>
			    </li>
			    <li>
			    	<a data-toggle="tab" href="#tab-javascript">
			    		<?php echo FText::_('COM_FABRIK_JAVASCRIPT')?>
			    	</a>
			    </li>
			</ul>
		</div>

		<div class="span10 tab-content">
			<?php
			echo $this->loadTemplate('details');
			echo $this->loadTemplate('publishing');
			echo $this->loadTemplate('access');
			echo $this->loadTemplate('listview');
			echo $this->loadTemplate('validations');
			echo $this->loadTemplate('javascript');
			?>
		</div>
	</div>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="redirectto" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
