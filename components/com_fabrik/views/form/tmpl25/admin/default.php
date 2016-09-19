<?php
/**
 * Admin Form Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<?php if ($this->params->get('show_page_heading', 1)) { ?>
	<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>"><?php echo $this->escape($this->params->get('page_heading')); ?></div>
<?php } ?>
<?php $form = $this->form;
if ($this->params->get('show-title', 1)) {?>
<h1><?php echo $form->label;?></h1>
<?php }
echo $form->intro;
?>
<form method="post" <?php echo $form->attribs?>>
<?php
$active = ($form->error != '') ? '' : ' fabrikHide';
echo "<div class=\"fabrikMainError fabrikError$active\">$form->error</div>";?>

	<?php
	if ($this->showEmail) {
		echo $this->emailLink;
	}
	if ($this->showPDF) {
		echo $this->pdfLink;
	}
	if ($this->showPrint) {
		echo $this->printLink;
	}
	echo $this->loadTemplate('relateddata');
	foreach ($this->groups as $group) {?>
		<fieldset class="fabrikGroup" id="group<?php echo $group->id;?>" style="<?php echo $group->css;?>">
		<legend><span><?php echo $group->title;?></span></legend>

		<?php if ($group->intro !== '') {?>
		<div class="groupintro"><?php echo $group->intro ?></div>
		<?php }?>
		<?php if ($group->canRepeat) {
			foreach ($group->subgroups as $subgroup) {
			?>
				<div class="fabrikSubGroup">
					<div class="fabrikSubGroupElements">
						<?php
						$this->elements = $subgroup;
						echo $this->loadTemplate('group');
						?>
					</div>
					<?php if ($group->editable) { ?>
					<div class="fabrikGroupRepeater">
						<?php if ($group->canAddRepeat) {?>
						<a class="addGroup" href="#">
							<?php echo FabrikHelperHTML::image('plus-sign.png', 'form', $this->tmpl, FText::_('COM_FABRIK_ADD_GROUP'));?>
						</a>
						<?php }?>
						<?php if ($group->canDeleteRepeat) {?>
						<a class="deleteGroup" href="#">
							<?php echo FabrikHelperHTML::image('minus-sign.png', 'form', $this->tmpl, FText::_('COM_FABRIK_DELETE_GROUP'));?>
						</a>
						<?php }?>
					</div>
					<?php } ?>
				</div>
				<?php
			}
		} else {
			$this->elements = $group->elements;
			echo $this->loadTemplate('group');
		}
	// Show the group outro
	if ($group->outro !== '') :?>
		<div class="groupoutro"><?php echo $group->outro ?></div>
	<?php
	endif;
	?>

	</fieldset>
<?php
	}
	echo $this->hiddenFields;
	?>
	<div class="fabrikActions"><?php echo $form->resetButton;?> <?php echo $form->submitButton;?>
	<?php echo $form->prevButton?> <?php echo $form->nextButton?>
	 <?php echo $form->applyButton;?>
	<?php echo $form->copyButton  . " " . $form->gobackButton . ' ' . $form->deleteButton . ' ' . $this->message ?>
	</div>
</form>
<?php

echo $form->outro;
echo $this->pluginend;
echo FabrikHelperHTML::keepalive();?>