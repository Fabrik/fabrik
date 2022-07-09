<?php
/**
 * Bootstrap List Template: Default Headings
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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
?>
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