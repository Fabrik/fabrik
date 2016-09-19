<?php
/**
 * Admin Element Edit - Publishing Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="tab-pane" id="tab-publishing">
	<fieldset class="form-horizontal">
		<legend><?php echo FText::_('COM_FABRIK_PUBLISHING');?></legend>
		<ul class="nav nav-tabs">
			<li class="active">
					<a data-toggle="tab" href="#publishing-details">
						<?php echo FText::_('COM_FABRIK_ELEMENT_LABEL_PUBLISHING_DETAILS'); ?>
					</a>
				</li>
				<li>
					<a data-toggle="tab" href="#publishing-rss">
						<?php echo FText::_('COM_FABRIK_ELEMENT_LABEL_RSS')?>
					</a>
				</li>
				<li>
					<a data-toggle="tab" href="#publishing-tips">
						<?php echo FText::_('COM_FABRIK_ELEMENT_LABEL_TIPS')?>
					</a>
				</li>
		</ul>
	</fieldset>

	<div class="tab-content">
		<div class="tab-pane active" id="publishing-details">
		    <fieldset class="form-horizontal">
				<?php foreach ($this->form->getFieldset('publishing') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>
		</div>

		<div class="tab-pane" id="publishing-rss">
			<fieldset class="form-horizontal">
				<?php foreach ($this->form->getFieldset('rss') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>
		</div>

		<div class="tab-pane" id="publishing-tips">
			<fieldset class="form-horizontal">
				<?php foreach ($this->form->getFieldset('tips') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>
		</div>
	</div>
</div>
