<?php
/**
 * Tabs Form Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if ($this->params->get('show_page_heading', 1)) : ?>
	<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>"><?php echo $this->escape($this->params->get('page_heading')); ?></div>
<?php
endif;
$form = $this->form;
if ($this->params->get('show-title', 1)) {?>
	<h1>
		<?php echo $form->label;?>
	</h1>
<?php }
echo $form->intro;
?>
<div class="fabrikForm fabrikDetails" id="<?php echo $form->formid; ?>">
<?php
echo $this->plugintop;
$active = ($form->error != '') ? '' : ' fabrikHide';
echo "<div class=\"fabrikMainError fabrikError$active\">$form->error</div>";

if ($this->showEmail) :
	echo $this->emailLink;
endif;
if ($this->showPDF) :
	echo $this->pdfLink;
endif;
if ($this->showPrint) :
	echo $this->printLink;
endif;
echo $this->loadTemplate('relateddata');
?>
<br />
<dl class="tabs">
<?php
$display = 0;
$c = 0;
foreach ($this->groups as $group) :
$this->group = $group;
	$errorstyle = '';
	foreach ($group->elements as $element) :
		if ($element->error !== '') :
			if ($display === 0) :
				$display = $c;
			endif;
			$errorstyle = 'style="background:#EFE7B8 url(' . COM_FABRIK_LIVESITE .'media/com_fabrik/images/alert.png) no-repeat scroll left 7px !important;padding-left:40px;"';
			break;
		endif;
	endforeach;
	$c ++;
	?>
	<dt id="group<?php echo $group->id; ?>_tab"
		<?php echo $errorstyle?>>
		<?php echo $group->title;?>
	</dt>
	<dd class="fabrikGroup" id="group<?php echo $group->id;?>" style="<?php echo $group->css;?>">
	<?php if (trim($group->title) !== '') :
	?>
		<h3 class="legend">
			<span>
				<?php echo $group->title;?>
			</span>
		</h3>
	<?php
	endif;

	// Show the group intro
	if ($group->intro !== '') :?>
		<div class="groupintro"><?php echo $group->intro ?></div>
	<?php
	endif;
	?>
	<div style="<?php echo $group->css?>">
	<?php if ($group->canRepeat) :
		foreach ($group->subgroups as $subgroup) :
		?>
			<div class="fabrikSubGroup">
				<div class="fabrikSubGroupElements">
					<?php
					$this->elements = $subgroup;
					echo $this->loadTemplate($group->tmpl);
	// Show the group outro
	if ($group->outro !== '') :?>		<div class="groupoutro"><?php echo $group->outro ?></div>
	<?php
	endif;
	?>					?>
				</div>
				<?php if ($group->editable) : ?>
					<div class="fabrikGroupRepeater">
						<?php if ($group->canAddRepeat) :?>
							<a class="addGroup" href="#">
								<?php echo FabrikHelperHTML::image('plus-sign.png', 'form', $this->tmpl, FText::_('COM_FABRIK_ADD_GROUP'));?>
							</a>
						<?php
						endif;
						if ($group->canDeleteRepeat) : ?>
							<a class="deleteGroup" href="#">
								<?php echo FabrikHelperHTML::image('minus-sign.png', 'form', $this->tmpl, FText::_('COM_FABRIK_DELETE_GROUP'));?>
							</a>
						<?php
						endif;
						?>
					</div>
				<?php
				endif;
				?>
			</div>
			<div style="clear:both"></div>
			<?php
		endforeach;
	else:
		$this->elements = $group->elements;
		echo $this->loadTemplate($group->tmpl);
		// Show the group outro
		if ($group->outro !== '') :?>
			<div class="groupoutro"><?php echo $group->outro ?></div>
<?php
		endif;
	endif;
	?>
	</div>
	<div style="clear:left;"></div>
	</dd>
<?php
endforeach;
?>
</dl>
<?php
echo $this->hiddenFields;
 echo $this->pluginbottom; ?>
<div class="fabrikActions"><?php echo $form->resetButton;?> <?php echo $form->submitButton;?>
<?php echo $form->prevButton?><?php echo $form->nextButton?>
 <?php echo $form->applyButton;?>
<?php echo $form->copyButton  . " " . $form->gobackButton . ' ' . $form->deleteButton . ' ' . $this->message ?>
</div>
</div>
<?php
$document = JFactory::getDocument();
echo $form->outro;
echo $this->pluginend;
echo FabrikHelperHTML::keepalive();
$options = "{display:$display}";
$js = '		window.addEvent("fabrik.load", function() { $$(\'dl.tabs\').each(function(tabs) { new JTabs(tabs, '.$options.'); }); });';
FabrikHelperHTML::addScriptDeclaration($js);
$document->addScript(JURI::root(true). '/media/system/js/tabs.js');?>