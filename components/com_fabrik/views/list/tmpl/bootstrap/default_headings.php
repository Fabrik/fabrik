<?php
/**
 * Bootstrap List Template: Default Headings
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
$btnLayout  = $this->getModel()->getLayout('fabrik-button');
$layoutData = (object) array(
	'class' => 'btn-info fabrik_filter_submit button',
	'name' => 'filter',
	'label' => FabrikHelperHTML::icon('icon-filter', FText::_('COM_FABRIK_GO'))
);
$groupHeaders = in_array("table-group-header", explode(" ", $this->table->class));

if ($groupHeaders && count($this->groupheadings) > 1 && max($this->groupheadings) > 1) : ?>
	<tr class="fabrik___heading">
		<?php 
		$currentGrp = NULL;
		$currentStyle = NULL;
		$grpCount = 0;
		foreach ($this->headings as $key => $heading) :
			$grp = "";
			$style = $this->headingClass[$key]['style'];
			foreach ($this->toggleCols as $cols) :
				if (array_key_exists($key, $cols["elements"])) :
					$grp = $cols["name"];
					break;
				endif;
			endforeach;
			if ($grp !== $currentGrp || $style !== $currentStyle) :
				if ($grpCount == 1) : ?>
					<th class="heading fabrik_groupcell" <?php if (!empty($currentStyle)) echo 'style="' . $currentStyle . '"' ?>></th>
				<?php elseif ($grpCount > 1) : ?>
					<th colspan="<?php echo $grpCount ?>" class="heading fabrik_groupcell" <?php if (!empty($currentStyle)) echo 'style="' . $currentStyle . '"' ?>>
						<span><?php echo $currentGrp; ?></span>
					</th>
				<?php endif;
				$grpCount = 1;
				$currentGrp = $grp;
				$currentStyle = $style;
			else :
				$grpCount++;
			endif;
		endforeach; 
		if ($grpCount == 1) : ?>
			<th class="heading fabrik_groupcell" <?php if (!empty($currentStyle)) echo 'style="' . $currentStyle . '"' ?>></th>
		<?php elseif ($grpCount > 1) : ?>
			<th colspan="<?php echo $grpCount ?>" class="heading fabrik_groupcell" <?php if (!empty($currentStyle)) echo 'style="' . $currentStyle . '"' ?>>
				<span><?php echo $currentGrp; ?></span>
			</th>
		<?php endif; ?>
	</tr>
<?php endif; ?>

	<tr class="fabrik___heading">
		<?php foreach ($this->headings as $key => $heading) :
			$h = $this->headingClass[$key];
			$style = empty($h['style']) ? '' : 'style="' . $h['style'] . '"'; ?>
			<th class="heading <?php echo $h['class'] ?>" <?php echo $style ?>>
				<span><?php echo $heading; ?></span>
			</th>
		<?php endforeach; ?>
	</tr>

<?php if (($this->filterMode === 3 || $this->filterMode === 4) && count($this->filters) <> 0) : ?>
	<tr class="fabrikFilterContainer">
		<?php foreach ($this->headings as $key => $heading) :
			$h = $this->headingClass[$key];
			$style = empty($h['style']) ? '' : 'style="' . $h['style'] . '"';
			?>
			<th class="<?php echo $h['class'] ?>" <?php echo $style ?>>
				<?php
				if (array_key_exists($key, $this->filters)) :

					$filter = $this->filters[$key];
					$required = $filter->required == 1 ? ' notempty' : '';
					?>
					<div class="listfilter<?php echo $required; ?> pull-left">
						<?php echo $filter->element; ?>
					</div>
				<?php elseif ($key == 'fabrik_actions' && $this->filter_action != 'onchange') :
					?>
					<div style="text-align:center">
						<?php echo $btnLayout->render($layoutData); ?>
					</div>
				<?php endif; ?>
			</th>
		<?php endforeach; ?>
	</tr>
<?php endif; ?>