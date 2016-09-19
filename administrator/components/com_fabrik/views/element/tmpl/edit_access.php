<?php
/**
 * Admin Element Edit:access Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

echo JHtml::_('tabs.panel', FText::_('COM_FABRIK_GROUP_LABEL_RULES_DETAILS'), 'element-access');?>
<fieldset class="adminform">
	<ul class="adminformlist">
	<?php
		foreach ($this->form->getFieldset('access') as $field) :?>
		<li>
			<?php echo $field->label;
			echo $field->input;?>
		</li>
		<?php endforeach;
		?>
		<?php
		foreach ($this->form->getFieldset('access2') as $field) :?>
		<li>
			<?php echo $field->label;
			echo $field->input; ?>
		</li>
		<?php endforeach; ?>
	</ul>
</fieldset>