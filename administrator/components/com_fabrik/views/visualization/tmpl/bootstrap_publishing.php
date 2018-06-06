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
<div class="tab-pane" id="tab-publishing">
	<fieldset class="form-horizontal">
		<legend>
			<?php echo FText::_('COM_FABRIK_GROUP_LABEL_PUBLISHING_DETAILS');?>
		</legend>
		<?php foreach ($this->form->getFieldset('publishing') as $this->field) :
			echo $this->loadTemplate('control_group');
		endforeach; ?>
	</fieldset>
</div>
