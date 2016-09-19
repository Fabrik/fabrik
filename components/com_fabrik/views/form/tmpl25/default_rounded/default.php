<?php
/**
 * Default Rounded Form Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

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

*/
?>

<!--If you have set to show the page title in the forms layout parameters, then the page title will show-->

<?php if ($this->params->get('show_page_heading', 1)) { ?>
	<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>"><?php echo $this->escape($this->params->get('page_heading')); ?></div>
<?php } ?>

<?php $form = $this->form;
if ($this->params->get('show-title', 1)) {?>
	<!--This will show the forms label-->
	<h1><?php echo $form->label;?></h1>
	<!--This area will show the form's intro as well as any errors-->
<?php }
echo $form->intro;
?>

<form method="post" <?php echo $form->attribs?>>
<?php
	echo $this->plugintop;
	$active = ($form->error != '') ? '' : ' fabrikHide';
	echo "<div class=\"fabrikMainError fabrikError$active\">";
	echo FabrikHelperHTML::image('alert.png', 'form', $this->tmpl);
	echo "$form->error</div>";
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
	foreach ($this->groups as $group) {
	?>
		<!-- This is where the fieldset is set up -->
		<fieldset class="fabrikGroup" id="group<?php echo $group->id;?>" style="<?php echo $group->css;?>">
			<?php if (trim($group->title) !== '') {?>
				<!-- This is where the legend is set up -->
				<legend><span><?php echo $group->title;?></span></legend>
			<?php }?>
			<!-- This is where the group intro is shown -->
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
										<?php echo FabrikHelperHTML::image('plus-sign.png', 'form', $this->tmpl, array('class' => 'fabrikTip','opts' => "{notice:true}", 'title' => FText::_('COM_FABRIK_ADD_GROUP')));?>
									</a>
								<?php }?>
								<?php if ($group->canDeleteRepeat) {?>
									<a class="deleteGroup" href="#">
										<?php echo FabrikHelperHTML::image('minus-sign.png', 'form', $this->tmpl, array('class' => 'fabrikTip','opts' => "{notice:true}", 'title' => FText::_('COM_FABRIK_DELETE_GROUP')));?>
									</a>
								<?php }?>
							</div>
						<?php } ?>
					</div>
				<?php }
			} else {
				$this->elements = $group->elements;
				echo $this->loadTemplate('group');
			}
			// Show the group outro
			if ($group->outro !== '') :?>
				<div class="groupoutro"><?php echo $group->outro ?></div>
			<?php endif; ?>
		</fieldset>
	<?php }
	echo $this->hiddenFields;
	echo $this->pluginbottom; ?>
	<!-- This is where the buttons at the bottom of the form are set up -->
	<div class="fabrikActions"><?php echo $form->resetButton;?> <?php echo $form->submitButton;?>
		<?php echo $form->prevButton?> <?php echo $form->nextButton?>
		<?php echo $form->applyButton;?>
		<?php echo $form->copyButton  . " " . $form->gobackButton . ' ' . $form->deleteButton . ' ' . $this->message ?>
	</div>
</form>
<?php
echo $form->outro;
echo $this->pluginend;
echo FabrikHelperHTML::keepalive();
?>