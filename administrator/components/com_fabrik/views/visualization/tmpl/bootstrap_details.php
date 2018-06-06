<?php
/**
 * Admin Visualization Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.8
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="tab-pane active" id="tab-details">
	<fieldset class="form-horizontal">
		<div>
			<legend><?php echo FText::_('COM_FABRIK_DETAILS'); ?></legend>
			<?php foreach ($this->form->getFieldset('details') as $this->field) :
				echo $this->loadTemplate('control_group');
			endforeach;	?>
		</div>
	</fieldset>

	<fieldset class="form-horizontal">
		<div id="plugin-container">
			<?php echo $this->pluginFields;?>
		</div>
	</fieldset>
</div>
