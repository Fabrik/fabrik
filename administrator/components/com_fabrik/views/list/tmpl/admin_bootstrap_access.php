<?php
/**
 * Admin List Tmpl
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
<div class="tab-pane" id="access">
    <fieldset class="form-horizontal">
    	<legend>
    		<?php echo FText::_('COM_FABRIK_GROUP_LABEL_RULES_DETAILS'); ?>
    	</legend>
		<?php
		foreach ($this->form->getFieldset('access') as $this->field) :
			echo $this->loadTemplate('control_group');
		endforeach;
		foreach ($this->form->getFieldset('access2') as $this->field) :
			echo $this->loadTemplate('control_group');
		endforeach;
		?>
	</fieldset>
</div>
