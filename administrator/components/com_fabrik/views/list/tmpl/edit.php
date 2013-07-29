<?php
/**
 * Admin List Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHTML::stylesheet('administrator/components/com_fabrik/views/fabrikadmin.css');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');

JHtml::script('media/com_fabrik/js/mootools-ext.js');
$fbConfig = JComponentHelper::getParams('com_fabrik');

$document = JFactory::getDocument();
$srcs = FabrikHelperHTML::framework();
$srcs[] = 'administrator/components/com_fabrik/views/namespace.js';
$srcs[] = 'administrator/components/com_fabrik/views/pluginmanager.js';
$srcs[] = 'administrator/components/com_fabrik/views/list/tmpl/adminlist.js';

FabrikHelperHTML::script($srcs, $this->js);
?>

<script type="text/javascript">

	Joomla.submitbutton = function(task) {
		if (task !== 'list.cancel'  && !controller.canSaveForm()) {
			alert('Please wait - still loading');
			return false;
		}
		if (task == 'list.cancel' || document.formvalidator.isValid(document.id('adminForm'))) {

			Joomla.submitform(task, document.getElementById('adminForm'));
		} else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
</script>

<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

	<div class="width-40 fltlft">

	<?php

$panels = array(
	array('heading'=>JText::_('COM_FABRIK_DETAILS'),
		'fieldset'=>array('main', 'details2')),

	array('heading'=>JText::_('COM_FABRIK_FILTERS'),
		'fieldset'=>array('main_filter', 'filters')),

	array('heading'=>JText::_('COM_FABRIK_NAVIGATION'),
		'fieldset'=>array('main_nav', 'navigation')),

	array('heading'=>JText::_('COM_FABRIK_LAYOUT'),
		'fieldset'=>array('main_template', 'layout')),

	array('heading'=>JText::_('COM_FABRIK_LINKS'),
		'fieldset'=>array('links')),

	array('heading'=>JText::_('COM_FABRIK_NOTES'),
		'fieldset'=>array('notes')),

	array('heading'=>JText::_('COM_FABRIK_ADVANCED'),
		'fieldset'=>array('advanced'))

);

echo JHtml::_('sliders.start','list-sliders-'.$this->item->id, array('useCookie'=>1));

foreach ($panels as $panel) {
	echo JHtml::_('sliders.panel',$panel['heading'], $panel['fieldset'][0].-'details');
			?>
			<fieldset class="adminform">
				<ul class="adminformlist">
				<?php foreach ($panel['fieldset'] as $fieldset) :
					foreach ($this->form->getFieldset($fieldset) as $field) :?>
					<li>
					<?php if (JString::strtolower($field->type) != 'hidden') {
							echo $field->label;
						} ?>
						<?php echo $field->input; ?>
					</li>
					<?php endforeach;
					endforeach;?>
				</ul>
			</fieldset>
<?php }
echo JHtml::_('sliders.end');
?>

	</div>
	<div class="width-60 fltrt">
		<?php echo JHtml::_('tabs.start', 'list-tabs-'.(int) $this->item->id, array('useCookie'=>1));
		echo $this->loadTemplate('data');
		echo $this->loadTemplate('publishing');
		echo $this->loadTemplate('plugins');
		echo $this->loadTemplate('rules');
		echo JHtml::_('tabs.end'); ?>
	</div>

	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
