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
<?php echo JHtml::_('bootstrap.startTabSet', 'publishingTabs', array('active' => 'publishing-details')); ?>

<?php echo JHtml::_('bootstrap.addTab', 'publishingTabs', 'publishing-details', JText::_('COM_FABRIK_GROUP_LABEL_PUBLISHING_DETAILS')); ?>
<div class="row">
    <div class="col-md-12">
        <fieldset class="form-horizontal">
		    <?php foreach ($this->form->getFieldset('publishing-details') as $this->field) :
			    echo $this->loadTemplate('control_group');
		    endforeach;
		    ?>
        </fieldset>
    </div>
</div>
<?php echo JHtml::_('bootstrap.endTab'); ?>

<?php echo JHtml::_('bootstrap.addTab', 'publishingTabs', 'publishing-rss', JText::_('COM_FABRIK_GROUP_LABEL_RSS')); ?>
<div class="row">
    <div class="col-md-12">
        <fieldset class="form-horizontal">
		    <?php foreach ($this->form->getFieldset('rss') as $this->field) :
			    echo $this->loadTemplate('control_group');
		    endforeach;
		    ?>
        </fieldset>
    </div>
</div>
<?php echo JHtml::_('bootstrap.endTab'); ?>

<?php echo JHtml::_('bootstrap.addTab', 'publishingTabs', 'publishing-csv', JText::_('COM_FABRIK_GROUP_LABEL_CSV')); ?>
<div class="row">
    <div class="col-md-12">
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
</div>
<?php echo JHtml::_('bootstrap.endTab'); ?>

<?php echo JHtml::_('bootstrap.addTab', 'publishingTabs', 'publishing-oai', JText::_('COM_FABRIK_OPEN_ARCHIVE_INITIATIVE')); ?>
<div class="row">
    <div class="col-md-12">
        <fieldset class="form-horizontal">
            <div class="alert"><?php echo FText::_('COM_FABRIK_OPEN_ARCHIVE_INITIATIVE'); ?></div>
		    <?php foreach ($this->form->getFieldset('open_archive_initiative') as $this->field) :
			    echo $this->loadTemplate('control_group');
		    endforeach;
		    ?>
        </fieldset>
    </div>
</div>
<?php echo JHtml::_('bootstrap.endTab'); ?>

<?php echo JHtml::_('bootstrap.addTab', 'publishingTabs', 'publishing-search', JText::_('COM_FABRIK_GROUP_LABEL_SEARCH')); ?>
<div class="row">
    <div class="col-md-12">
        <fieldset class="form-horizontal">
            <div class="alert"><?php echo FText::_('COM_FABRIK_SPECIFY_ELEMENTS_IN_DETAILS_FILTERS'); ?></div>
		    <?php foreach ($this->form->getFieldset('search') as $this->field) :
			    echo $this->loadTemplate('control_group');
		    endforeach;
		    ?>
        </fieldset>
    </div>
</div>
<?php echo JHtml::_('bootstrap.endTab'); ?>

<?php echo JHtml::_('bootstrap.addTab', 'publishingTabs', 'publishing-dashboard', JText::_('COM_FABRIK_ADMIN_DASHBOARD')); ?>
<div class="row">
    <div class="col-md-12">
        <fieldset class="form-horizontal">
		    <?php foreach ($this->form->getFieldset('dashboard') as $this->field) :
			    echo $this->loadTemplate('control_group');
		    endforeach;
		    ?>
        </fieldset>
    </div>
</div>
<?php echo JHtml::_('bootstrap.endTab'); ?>

<?php echo JHtml::_('bootstrap.endTabSet'); ?>