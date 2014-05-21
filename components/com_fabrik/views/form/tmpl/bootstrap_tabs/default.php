<?php
/**
 * Bootstrap Tabs Form Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$form = $this->form;
$model = $this->getModel();
$groupTmpl = $model->editable ? 'group' : 'group_details';
$active = ($form->error != '') ? '' : ' fabrikHide';

if ($model->isMultiPage())
{
	$app = JFactory::getApplication();
	$app->enqueueMessage(FText::_('COM_FABRIK_ERR_TAB_FORM_TEMPLATE_INCOMPATIBLE_WITH_MULTIPAGE_FORMS'), 'error');
}

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
?>
<form method="post" <?php echo $form->attribs?>>
<?php
echo $this->plugintop;
?>

<div class="fabrikMainError alert alert-error fabrikError<?php echo $active?>">
	<button class="close" data-dismiss="alert">×</button>
	<?php echo $form->error?>
</div>

<div class="row-fluid nav">
	<div class="span6 pull-right">
		<?php
		echo $this->loadTemplate('buttons');
		?>
	</div>
	<div class="span6">
		<?php
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
		if (!$model->isMultiPage() || $i == 0 || $group->splitPage) :
			?>
				<li <?php if ($i == 0) echo 'class="active"'?> style="<?php echo $group->css;?>">
					<a href="#group-tab<?php echo $i;?>" data-toggle="tab" id="group<?php echo $group->id;?>_tab">
						<?php
							if (!empty($group->title))
							{
								echo $group->title;
							}
							else
							{
								echo $group->name;
							}
						?>
					</a>
				</li>
			<?php
			$i ++;
		endif;
	endforeach;
	?>
</ul>
<div class="tab-content">
	<?php

	$i = 0;
	foreach ($this->groups as $group) :
		$this->group = $group;
		if ($i == 0 || !$model->isMultiPage() || $group->splitPage) :
			if ($i != 0)
			{
				echo '</div>';
			}
			?>
			<div class="tab-pane<?php if ($i == 0) echo " active"?>" id="group-tab<?php echo $i;?>">
			<?php
			$i++;
		endif; ?>
			<fieldset class="<?php echo $group->class; ?>" id="group<?php echo $group->id;?>" style="<?php echo $group->css;?>">
				<?php
				if ($group->showLegend) : ?>
					<legend class="legend"><?php echo $group->title;?></legend>
				<?php
				endif;

				if (!empty($group->intro)) : ?>
					<div class="groupintro"><?php echo $group->intro ?></div>
				<?php
				endif;

				/* Load the group template - this can be :
				 *  * default_group.php - standard group non-repeating rendered as an unordered list
				 *  * default_repeatgroup.php - repeat group rendered as an unordered list
				 *  * default_repeatgroup_table.php - repeat group rendered in a table.
				 */
				$this->elements = $group->elements;
				echo $this->loadTemplate($group->tmpl);

				if (!empty($group->outro)) : ?>
					<div class="groupoutro"><?php echo $group->outro ?></div>
				<?php
				endif;
			?>
			</fieldset>
		<?php
	endforeach;
	?>
	</div>
</div>
<?php
if ($model->editable) : ?>
<div class="fabrikHiddenFields">
	<?php echo $this->hiddenFields; ?>
</div>
<?php
endif;

echo $this->pluginbottom;
echo $this->loadTemplate('actions');
?>
</form>
<?php
echo $form->outro;
echo $this->pluginend;
echo FabrikHelperHTML::keepalive();
?>
