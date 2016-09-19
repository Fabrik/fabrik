<?php
/**
 * Admin Group Edit Tmpl
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
JHtml::_('behavior.tooltip');
FabrikHelperHTML::formvalidation();
JHtml::_('behavior.keepalive');

?>

<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

<ul class="nav nav-tabs">
		<li class="active">
	    	<a data-toggle="tab" href="#details-info">
	    		<?php echo FText::_('COM_FABRIK_DETAILS'); ?>
	    	</a>
	    </li>
	    <li>
	    	<a data-toggle="tab" href="#details-repeat">
	    		<?php echo FText::_('COM_FABRIK_REPEAT')?>
	    	</a>
	    </li>
	    <li>
	    	<a data-toggle="tab" href="#details-layout">
	    		<?php echo FText::_('COM_FABRIK_LAYOUT')?>
	    	</a>
	    </li>
	    <li>
	    	<a data-toggle="tab" href="#details-multipage">
	    		<?php echo FText::_('COM_FABRIK_GROUP_MULTIPAGE')?>
	    	</a>
	    </li>
	</ul>

	<div class="tab-content">

		<div class="tab-pane active" id="details-info">
			<fieldset class="form-horizontal">
		    	<legend>
		    		<?php echo FText::_('COM_FABRIK_DETAILS');?>
		    	</legend>
				<?php foreach ($this->form->getFieldset('details') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				foreach ($this->form->getFieldset('details2') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>
		</div>

		<div class="tab-pane" id="details-repeat">
			<fieldset class="form-horizontal">
		    	<legend>
		    		<?php echo FText::_('COM_FABRIK_REPEAT');?>
		    	</legend>
				<?php foreach ($this->form->getFieldset('repeat') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>
		</div>

		<div class="tab-pane" id="details-layout">
			<fieldset class="form-horizontal">
		    	<legend>
		    		<?php echo FText::_('COM_FABRIK_LAYOUT');?>
		    	</legend>
				<?php foreach ($this->form->getFieldset('layout') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>
		</div>

		<div class="tab-pane" id="details-multipage">
			<fieldset class="form-horizontal">
		    	<legend>
		    		<?php echo FText::_('COM_FABRIK_GROUP_MULTIPAGE');?>
		    	</legend>
				<?php foreach ($this->form->getFieldset('pagination') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>
		</div>
	</div>

	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
