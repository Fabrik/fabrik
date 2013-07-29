<?php
/**
 * Bootstrap Tabs Form Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       3.1
 */

$form = $this->form;
$model = $this->getModel();
$groupTmpl = $model->editable ? 'group' : 'group_details';
$active = ($form->error != '') ? '' : ' fabrikHide';

if ($this->params->get('show_page_heading', 1)) : ?>
	<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>">
		<?php echo $this->escape($this->params->get('page_heading')); ?>
	</div>
<?php
endif;

if ($this->params->get('show-title', 1)) :?>
	<div class="page-header">
		<h1><?php echo $form->label;?></h1>
	</div>
<?php
endif;

echo $form->intro;

if ($model->editable) :
	echo '<form action="' . $form->action . '" class="' . $form->class . '" method="post" name="' . $form->name . '" id="' . $form->formid
					. '" enctype="' . $model->getFormEncType() . '">';
	else:
		echo '	<div class="fabrikForm fabrikDetails" id="' . $form->formid . '">';
endif;
echo $this->plugintop;
?>

<div class="fabrikMainError alert alert-error fabrikError<?php echo $active?>">
	<button class="close" data-dismiss="alert">×</button>
	<?php echo $form->error?>
</div>
<div class="row-fluid nav">
	<div class="span12">
		<?php
		echo $this->loadTemplate('buttons');
		echo $this->loadTemplate('relateddata');
		?>
	</div>
</div>
<ul class="nav nav-tabs">
	<?php
	$i = 0;
	foreach ($this->groups as $group) :
		// If this ismultipage then groups are consolidated until a group with a page break
		// So we should only show a tab if: it is first tab, or if it is a page break
		if (!$model->isMultiPage() or ($i == 0 or $group->splitPage)) :
		?>
			<li <?php if ($i == 0) echo 'class="active"'?>>
				<a href="#group-tab<?php echo $group->id;?>" data-toggle="tab">
					<?php echo $group->title?>
				</a>
			</li>
		<?php
		endif;
		$i ++;
	endforeach;
	?>
</ul>
<div class="tab-content">
	<?php

	$i = 0;
	foreach ($this->groups as $group) :
		$this->group = $group;
		?>
		<div class="tab-pane <?php  if ($i == 0) echo "active"?>" id="group-tab<?php echo $group->id;?>">
			<fieldset class="fabrikGroup row-fluid" id="group<?php echo $group->id;?>" style="<?php echo $group->css;?>">

				<?php if (trim($group->title) !== '') :?>
					<legend class="legend">
						<span><?php echo $group->title;?></span>
					</legend>
				<?php
				endif;

				if ($group->intro !== '') : ?>
					<div class="groupintro">
						<?php echo $group->intro ?>
					</div>
				<?php
				endif;

				// Load the group template - this can be :
				//  * default_group.php - standard group non-repeating rendered as an unordered list
				//  * default_repeatgroup.php - repeat group rendered as an unordered list
				//  * default_repeatgroup.table.php - repeat group rendered in a table.

				$this->elements = $group->elements;
				echo $this->loadTemplate($group->tmpl);
				?>
			</fieldset>
		</div>
		<?php
		$i++;
	endforeach;
	?>
</div>
<?php
if ($model->editable) :
	echo '<div class="fabrikHiddenFields">';
	echo $this->hiddenFields;
	echo '</div>';
endif;

echo $this->pluginbottom;
echo $this->loadTemplate('actions');
?>
</form>
<?php
echo $form->outro;
echo $this->pluginend;
echo FabrikHelperHTML::keepalive();
