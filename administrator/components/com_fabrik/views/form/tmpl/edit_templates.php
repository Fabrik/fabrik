<?php
/**
 * Admin Form Edit:templates Tmpl
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
<fieldset class="adminform">
	<legend>
		<?php echo FText::_('COM_FABRIK_FRONT_END_TEMPLATES'); ?>
	</legend>
	<ul class="adminformlist">
		<?php foreach ($this->form->getFieldset('templates') as $field) :?>
		<li>
			<?php echo $field->label; ?><?php echo $field->input; ?>
		</li>
		<?php endforeach; ?>
		<?php foreach ($this->form->getFieldset('templates2') as $field) :?>
		<li>
			<?php echo $field->label; ?><?php echo $field->input; ?>
		</li>
		<?php endforeach; ?>
	</ul>
</fieldset>

<fieldset class="adminform">
<legend>
		<?php echo FText::_('COM_FABRIK_ADMIN_TEMPLATES'); ?>
	</legend>
	<ul class="adminformlist">
		<?php foreach ($this->form->getFieldSet('admintemplates') as $field) :?>
		<li>
			<?php echo $field->label; ?><?php echo $field->input; ?>
		</li>
		<?php endforeach?>
	</ul>
</fieldset>

<fieldset class="adminform">
<legend>
		<?php echo FText::_('COM_FABRIK_LAYOUT'); ?>
	</legend>
	<ul class="adminformlist">
		<?php foreach ($this->form->getFieldSet('layout') as $field) :?>
		<li>
			<?php echo $field->label; ?><?php echo $field->input; ?>
		</li>
		<?php endforeach?>
	</ul>
</fieldset>