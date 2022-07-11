<?php
/**
 * Labels-Above Article Template - Default
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<?php if ($this->params->get('show_page_heading', 1)) { ?>
	<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>"><?php echo $this->escape($this->params->get('page_heading')); ?></div>
<?php } ?>
<?php $form = $this->form;
?>
<div class="fabrikForm fabrikDetails" id="<?php echo $form->formid; ?>">
<?php
if ($this->params->get('show-title', 1)) {?>
<h1><?php echo $form->label;?></h1>
<?php }
echo $form->intro;
echo $this->plugintop;
$active = ($form->error != '') ? '' : ' fabrikHide';
echo "<div class='fabrikMainError fabrikError$active'>" . $form->error . "</div>";?>

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
	foreach ( $this->groups as $group) {
		?>
		<fieldset class="fabrikGroup" id="group<?php echo $group->id;?>" style="<?php echo $group->css;?>">
		<legend><?php echo $group->title;?></legend>
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
								<?php echo FabrikHelperHTML::image('plus', 'form', $this->tmpl, FText::_('COM_FABRIK_ADD_GROUP'));?>
							</a>
							<?php }?>
							<a class="deleteGroup" href="#">
								<?php echo FabrikHelperHTML::image('minus', 'form', $this->tmpl, FText::_('COM_FABRIK_DELETE_GROUP'));?>
							</a>
						</div>
					<?php } ?>
				</div>
				<?php
			}
		} else {
			$this->elements = $group->elements;
			echo $this->loadTemplate('group');
		}?>
	</fieldset>
<?php
	}
	echo $this->hiddenFields;
	?>
	<?php echo $this->pluginbottom; ?>
	<div class="fabrikActions"><?php echo $form->resetButton;?> <?php echo $form->submitButton;?>
	<?php echo $form->copyButton  . " " . $form->gobackButton . ' ' . $this->message?>
	</div>
</div>
<?php
echo FabrikHelperHTML::keepalive();
?>