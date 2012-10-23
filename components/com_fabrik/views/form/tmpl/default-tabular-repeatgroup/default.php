<?php
/**
 * Default Form Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       3.0
 */

/* The default template includes the following folder and files:

images - this is the folder for the form template's images
- add.png
- alert.png
- delete.png
default.php - this file controls the layout of the form
default_group.php - this file controls the layout of the individual form groups
default_relateddata.php - this file controls the layout of the forms related data
template_css.php - this file controls the styling of the form

CSS classes and id's included in this file are:

componentheading - used if you choose to display the page title
<h1> - used if you choose to show the form label
fabrikMainError -
fabrikError -
fabrikGroup -
groupintro -
fabrikSubGroup -
fabrikSubGroupElements -
fabrikGroupRepeater -
addGroup -
deleteGroup -
fabrikTip -
fabrikActions -

Other form elements that can be styled here are:

legend
fieldset

To learn about all the different elements in a basic form see http://www.w3schools.com/tags/tag_legend.asp.

If you have set to show the page title in the forms layout parameters, then the page title will show */
?>
<?php if ($this->params->get('show_page_title', 1)) { ?>
	<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>"><?php echo $this->escape($this->params->get('page_title')); ?></div>
<?php } ?>
<?php $form = $this->form;
//echo $form->startTag;
if ($this->params->get('show-title', 1)) {?>

<?php  /*This will show the forms label */?>
<h1><?php echo $form->label;?></h1>

<?php  /*This area will show the form's intro as well as any errors */ ?>
<?php }
echo $form->intro;
echo $form->startTag;
echo $this->plugintop;
$active = ($form->error != '') ? '' : ' fabrikHide';
echo "<div class=\"fabrikMainError fabrikError$active\">";
echo FabrikHelperHTML::image('alert.png', 'form', $this->tmpl);
echo "$form->error</div>";?>
<?php
	if ($this->showEmail || $this->showPDF || $this->showPrint) { ?>
<div class="fabrikActions">
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
echo '</div>';
}
	echo $this->loadTemplate('relateddata');
	foreach ($this->groups as $group) {
      /* This is where the fieldset is set up */ ?>
		<?php if ($group->canRepeat && $group->isTabular) { ?>
		<table class="fabrikGroup" id="group<?php echo $group->id;?>" summary="<?php echo $group->title;?>">
		<caption>
		<?php if (trim($group->title) !== '') {?>
			<<?php echo $form->legendTag ?> class="legend"><span><?php echo $group->title;?></span></<?php echo $form->legendTag ?>>
		<?php }?>
		<?php if ($group->intro !== '') {?>
		<div class="groupintro"><?php echo $group->intro ?></div>
		<?php }?>
		<div style="clear:both;"></div>
		</caption>
		<thead><tr>
		<th class="fabrikHide"></th>
		<?php $this->elements = $group->elements; ?>
		<?php foreach ( $this->elements as $element) { ?>
		<th class="<?php echo $element->containerClass;?>">
		<?php echo $element->label;?>
		</th>
		<?php } ?>
		<?php if ($group->editable && ($group->canAddRepeat || $group->canDeleteRepeat)) {?>
		<th>Add/del</th>
		<?php } ?>
		</tr>
		</thead>
					<?php if ($group->editable) { ?>
		<tfoot>
		<tr><th>
		</th></tr>
		</tfoot>
					<?php } ?>
		<tbody>
			<?php foreach ($group->subgroups as $subgroup) {
			?>
			<tr class="fabrikSubGroup">
						<td class="fabrikSubGroupElements fabrikHide"></td>
						<?php
						$this->elements = $subgroup;
						echo $this->loadTemplate('tabular');
						?>
					<?php if ($group->editable) { ?>
							<td class="fabrikGroupRepeater"><?php if ($group->canAddRepeat) {?>
							<a class="addGroup" href="#">
								<?php echo FabrikHelperHTML::image('add.png', 'form', $this->tmpl, array('class' => 'fabrikTip', 'title' => JText::_('COM_FABRIK_ADD_GROUP')));?>
							</a>
							<?php }?>
							<?php if ($group->canDeleteRepeat) {?>
							<a class="deleteGroup" href="#">
								<?php echo FabrikHelperHTML::image('del.png', 'form', $this->tmpl, array('class' => 'fabrikTip', 'title' => JText::_('COM_FABRIK_DELETE_GROUP')));?>
							</a>
							<?php }?>
						</td>
					<?php } ?>
				</tr>
				<?php
			} ?>
			</tbody></table>
		<?php } else { ?>
<?php  /* This is where the fieldset is set up */ ?>
		<<?php echo $form->fieldsetTag ?> class="fabrikGroup" id="group<?php echo $group->id;?>" style="<?php echo $group->css;?>">

		<?php if (trim($group->title) !== '')
		{?>
			<<?php echo $form->legendTag ?> class="legend"><span><?php echo $group->title;?></span></<?php echo $form->legendTag ?>>
		<?php }?>

<?php  /* This is where the group intro is shown */ ?>
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
								<?php echo FabrikHelperHTML::image('add.png', 'form', $this->tmpl, array('class' => 'fabrikTip', 'title' => JText::_('COM_FABRIK_ADD_GROUP')));?>
							</a>
							<?php }?>
							<?php if ($group->canDeleteRepeat) {?>
							<a class="deleteGroup" href="#">
								<?php echo FabrikHelperHTML::image('del.png', 'form', $this->tmpl, array('class' => 'fabrikTip', 'title' => JText::_('COM_FABRIK_DELETE_GROUP')));?>
							</a>
							<?php }?>
						</div>
					<?php } ?>
				</div>
				<div style="clear:both"></div>
				<?php
			}
		} else {
			$this->elements = $group->elements;
			echo $this->loadTemplate('group');?>
			<div style="clear:both"></div>
		<?php }?>
	</<?php echo $form->fieldsetTag ?>>
	<?php	}
	}
	echo $this->hiddenFields;
	?>
	<?php echo $this->pluginbottom; ?>

<?php  /* This is where the buttons at the bottom of the form are set up */ ?>
	<?php if ($this->hasActions) {?>
	<div class="fabrikActions"><?php echo $form->resetButton;?> <?php echo $form->submitButton;?>
	<?php echo $form->nextButton?> <?php echo $form->prevButton?>
	 <?php echo $form->applyButton;?>
	<?php echo $form->copyButton  . " " . $form->gobackButton . ' ' . $form->deleteButton . ' ' . $this->message ?>
	</div>
	<?php } ?>

<?php
echo $form->endTag;
echo $form->outro;



echo $this->pluginend;
echo FabrikHelperHTML::keepalive();?>