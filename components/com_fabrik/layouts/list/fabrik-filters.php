<?php
/**
 * Layout: List filters
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.4
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$d             = $displayData;
$underHeadings = $d->filterMode === 3 || $d->filterMode === 4;
$clearFiltersClass = $d->gotOptionalFilters ? "clearFilters hasFilters" : "clearFilters";

$style = $d->toggleFilters ? 'style="display:none"' : ''; ?>
<div class="fabrikFilterContainer" <?php echo $style ?>>
	<?php
	if (!$underHeadings) :
	?>
	<div class="row-fluid">
		<?php
		if ($d->filterCols === 1) :
		?>
		<div class="span6">
			<?php
			endif;
			?>
			<table class="filtertable table table-striped">
				<thead>
				<tr class="fabrik___heading">
					<th><?php echo FText::_('COM_FABRIK_SEARCH'); ?>:</th>
					<th style="text-align:right">
						<?php if ($d->showClearFilters) : ?>
							<a class="<?php echo $clearFiltersClass; ?>" href="#">
								<?php echo FabrikHelperHTML::icon('icon-refresh', FText::_('COM_FABRIK_CLEAR')); ?>
							</a>
						<?php endif ?>
					</th>
				</tr>
				</thead>
				<tfoot>
				<tr>
					<td colspan="2"></td>
				</tr>
				</tfoot>
				<?php
				$c = 0;
				// $$$ hugh - filterCols stuff isn't operation yet, WiP, just needed to get it committed
				if ($d->filterCols > 1) :
				?>
				<tr>
					<td colspan="2">
						<table class="filtertable_horiz">
							<?php
							endif;
							$filter_count = array_key_exists('all', $d->filters) ? count($d->filters) - 1 : count($d->filters);
							$colHeight    = ceil($filter_count / $d->filterCols);
							foreach ($d->filters as $key => $filter) :
							if ($d->filterCols > 1 && $c >= $colHeight && $c % $colHeight === 0) :
							?>
						</table>
						<table class="filtertable_horiz">
							<?php
							endif;
							if ($key !== 'all') :
								$c++;
								$required = $filter->required == 1 ? ' notempty' : ''; ?>
								<tr data-filter-row="<?php echo $key; ?>"
										class="fabrik_row oddRow<?php echo ($c % 2) . $required; ?>">
									<td><?php echo $filter->label; ?></td>
									<td><?php echo $filter->element; ?></td>
								</tr>
								<?php
							endif;
							endforeach;
							if ($d->filterCols > 1) :
							?>
						</table>
					</td>
				</tr>
			<?php
			endif;
			if ($d->filter_action != 'onchange') :
				?>
				<tr>
					<td colspan="2">
						<input type="button" class="pull-right  btn-info btn fabrik_filter_submit button"
								value="<?php echo FText::_('COM_FABRIK_GO'); ?>" name="filter">
					</td>
				</tr>
				<?php
			endif;
			?>
			</table>
			<?php
			endif;
			?>
			<?php
			if (!($underHeadings)) :
			?>
			<?php
			if ($d->filterCols === 1) :
			?>
		</div>
	<?php
	endif;
	?>
	</div>
<?php endif; ?>
</div>