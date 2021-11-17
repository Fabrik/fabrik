<?php
/**
 * Admin Form Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="tab-pane" id="tab-layout">

<div class="row-fluid">
	<div class="span6">
		<fieldset class="form-horizontal">
	    <legend>
			<?php echo FText::_('COM_FABRIK_FRONT_END_TEMPLATES'); ?>
		</legend>
		<?php foreach ($this->form->getFieldset('templates') as $this->field) :
			echo $this->loadTemplate('control_group');
		endforeach;
		?>
		<?php foreach ($this->form->getFieldset('templates2') as $this->field) :
			echo $this->loadTemplate('control_group');
		endforeach;
		?>
	</fieldset>
	</div>

	<div class="span6">

    <fieldset class="form-horizontal">
    	<legend>
			<?php echo FText::_('COM_FABRIK_ADMIN_TEMPLATES'); ?>
		</legend>
		<?php foreach ($this->form->getFieldset('admintemplates') as $this->field) :
			echo $this->loadTemplate('control_group');
		endforeach;
		?>
	</fieldset>
	</div>
</div>



	<fieldset class="form-horizontal">
    	<legend>
			<?php echo FText::_('COM_FABRIK_LAYOUT'); ?>
		</legend>
		<?php foreach ($this->form->getFieldset('layout') as $this->field) :
			echo $this->loadTemplate('control_group');
		endforeach;
		?>
	</fieldset>
</div>
