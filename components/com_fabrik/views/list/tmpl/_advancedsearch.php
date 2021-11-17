<?php
/**
 * Fabrik List Template: Advanced Search
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

$app = JFactory::getApplication();
$input = $app->input;
?>
<form method="post" action="<?php echo $this->action?>" class="advancedSearch_<?php echo $this->listref?>">
	<a class="addbutton advanced-search-add btn-success btn" href="#">
		<?php echo FabrikHelperHTML::image('plus', 'list', $this->tmpl);?>
		<?php echo FText::_('COM_FABRIK_ADD')?>
	</a>
	<div id="advancedSearchContainer">
	<table class="advanced-search-list table table-striped table-condensed">
		<tbody>
			<?php foreach ($this->rows as $row) :?>
			<tr>
				<td><span><?php echo $row['join'];?></span></td>
				<td><?php echo $row['element'] . $row['type'] . $row['grouped'];?>
				</td>
				<td><?php echo $row['condition'];?></td>
				<td class='filtervalue'><?php echo $row['filter'];?></td>
				<td>
				<?php if (FabrikWorker::j3()) : ?>
					<div class="button-group">
						<a class="advanced-search-remove-row btn btn-danger" href="#">
							<?php echo FabrikHelperHTML::image('minus', 'list', $this->tmpl);?>
						</a>
					</div>
				<?php else: ?>
					<ul class="fabrik_action">
					<li>
						<a class="advanced-search-remove-row" href="#">
							<?php echo FabrikHelperHTML::image('minus-sign.png', 'list', $this->tmpl);?>
						</a>
					</li>
					</ul>
				<?php endif;?>

				</td>
			</tr>
			<?php endforeach;?>

		</tbody>
		<thead>
			<tr class="fabrik___heading title">
				<th></th>
				<th><?php echo FText::_('COM_FABRIK_ELEMENT')?></th>
				<th><?php echo FText::_('COM_FABRIK_CONDITION')?></th>
				<th><?php echo FText::_('COM_FABRIK_VALUE')?></th>
				<th><?php echo FText::_('COM_FABRIK_DELETE')?></th>
			</tr>
			</thead>
	</table>
	</div>
	<input type="submit"
		value="<?php echo FText::_('COM_FABRIK_APPLY')?>"
		class="button btn btn-primary fabrikFilter advanced-search-apply"
		name="applyAdvFabrikFilter"
		type="button">

	<input value="<?php echo FText::_('COM_FABRIK_CLEAR')?>" class="button btn advanced-search-clearall" type="button">
	<input type="hidden" name="advanced-search" value="1" />
	<input type="hidden" name="<?php echo $input->get('tkn', 'request')?>" value="1" />

	<?php
	$scope = $input->get('scope', 'com_fabrik');
	if ($scope == 'com_fabrik') :?>
		<input type="hidden" name="option" value="<?php echo $input->get('option')?>" />
		<input type="hidden" name="view" value="<?php echo $input->get('nextview', 'list'); ?>" />
		<input type="hidden" name="listid" value="<?php echo $this->listid?>" />
		<input type="hidden" name="task" value="<?php echo $input->get('nextview', 'list'); ?>.filter" />
	<?php
	endif;
	?>
</form>
