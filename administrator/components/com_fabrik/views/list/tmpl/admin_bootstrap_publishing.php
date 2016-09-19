<?php
/**
 * Admin List Tmpl
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
<div class="tab-pane" id="publishing">

	<ul class="nav nav-tabs">
		<li class="active">
	    	<a data-toggle="tab" href="#publishing-details">
	    		<?php echo FText::_('COM_FABRIK_GROUP_LABEL_PUBLISHING_DETAILS'); ?>
	    	</a>
	    </li>
	    <li>
	    	<a data-toggle="tab" href="#publishing-rss">
	    		<?php echo FText::_('COM_FABRIK_GROUP_LABEL_RSS')?>
	    	</a>
	    </li>
	    <li>
	    	<a data-toggle="tab" href="#publishing-csv">
	    		<?php echo FText::_('COM_FABRIK_GROUP_LABEL_CSV')?>
	    	</a>
	    </li>
		<li>
			<a data-toggle="tab" href="#publishing-oai">
				<?php echo FText::_('COM_FABRIK_OPEN_ARCHIVE_INITIATIVE'); ?>
			</a>
		</li>
	    <li>
	    	<a data-toggle="tab" href="#publishing-search">
	    		<?php echo FText::_('COM_FABRIK_GROUP_LABEL_SEARCH')?>
	    	</a>
	    </li>
		<li>
			<a data-toggle="tab" href="#publishing-dashboard">
				<?php echo FText::_('COM_FABRIK_ADMIN_DASHBOARD')?>
			</a>
		</li>

	</ul>

	<div class="tab-content">
		<div class="tab-pane active" id="publishing-details">
		    <fieldset class="form-horizontal">
				<?php foreach ($this->form->getFieldset('publishing-details') as $this->field) :
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

		<div class="tab-pane" id="publishing-csv">
			<fieldset class="form-horizontal">
				<?php
				foreach ($this->form->getFieldset('csv') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				foreach ($this->form->getFieldset('csvauto') as $this->field) :
				echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>
		</div>

		<div class="tab-pane" id="publishing-oai">
			<fieldset class="form-horizontal">
				<div class="alert"><?php echo FText::_('COM_FABRIK_OPEN_ARCHIVE_INITIATIVE'); ?></div>
				<?php foreach ($this->form->getFieldset('open_archive_initiative') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>
		</div>

		<div class="tab-pane" id="publishing-search">
			<fieldset class="form-horizontal">
				<div class="alert"><?php echo FText::_('COM_FABRIK_SPECIFY_ELEMENTS_IN_DETAILS_FILTERS'); ?></div>
				<?php foreach ($this->form->getFieldset('search') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>
		</div>

		<div class="tab-pane" id="publishing-dashboard">
			<fieldset class="form-horizontal">
				<?php foreach ($this->form->getFieldset('dashboard') as $this->field) :
					echo $this->loadTemplate('control_group');
				endforeach;
				?>
			</fieldset>
		</div>

	</div>
</div>
