<?php
/**
 * Admin Lists List Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHTML::_('script','system/multiselect.js',false,true);
$user	= JFactory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
?>
<form action="<?php echo JRoute::_('index.php?option=com_fabrik&view=lists'); ?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<div class="filter-search fltlft">
			<label class="filter-search-lbl" for="filter_search"><?php echo FText::_('JSEARCH_FILTER_LABEL'); ?>:</label>
			<input type="text" name="filter_search" id="filter_search" value="<?php echo $this->state->get('filter.search'); ?>" title="<?php echo FText::_('COM_FABRIK_SEARCH_IN_TITLE'); ?>" />
			<button type="submit"><?php echo FText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<button type="button" onclick="document.id('filter_search').value='';this.form.submit();"><?php echo FText::_('JSEARCH_FILTER_CLEAR'); ?></button>
		</div>
		<div class="filter-select fltrt">
			<?php if (!empty($this->packageOptions)) {?>
			<select name="package" class="inputbox" onchange="this.form.submit()">
				<option value="fabrik"><?php echo FText::_('COM_FABRIK_SELECT_PACKAGE');?></option>
				<?php echo JHtml::_('select.options', $this->packageOptions, 'value', 'text', $this->state->get('com_fabrik.package'), true);?>
			</select>
			<?php }?>

			<select name="filter_published" class="inputbox" onchange="this.form.submit()">
				<option value=""><?php echo FText::_('JOPTION_SELECT_PUBLISHED');?></option>
				<?php echo JHtml::_('select.options', JHtml::_('jgrid.publishedOptions', array('archived'=>false)), 'value', 'text', $this->state->get('filter.published'), true);?>
			</select>
		</div>
	</fieldset>
	<div class="clr"> </div>

	<table class="adminlist">
		<thead>
			<tr>
				<th width="2%">
					<?php echo JHTML::_('grid.sort', 'JGRID_HEADING_ID', 'l.id', $listDirn, $listOrder); ?>
				</th>
				<th width="1%">
					<input type="checkbox" name="toggle" value="" onclick="checkAll(this);" />
				</th>
				<th width="16%">
					<?php echo JHTML::_('grid.sort', 'COM_FABRIK_LIST_NAME', 'label', $listDirn, $listOrder); ?>
				</th>
				<th width="17%">
					<?php echo JHTML::_('grid.sort', 'COM_FABRIK_DB_TABLE_NAME', 'db_table_name', $listDirn, $listOrder); ?>
				</th>
				<th width="14%">
					<?php echo FText::_('COM_FABRIK_ELEMENT');?>
				</th>
				<th width="14%">
					<?php echo FText::_('COM_FABRIK_FORM'); ?>
				</th>
				<th width="16%">
					<?php echo FText::_('COM_FABRIK_VIEW_DATA');?>
				</th>
				<th width="5%">
					<?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'published', $listDirn, $listOrder); ?>
				</th>
				<th width="20%">
					<?php echo FText::_('COM_FABRIK_VIEW_DETAILS'); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="9">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php foreach ($this->items as $i => $item) :
				$ordering	= ($listOrder == 'ordering');
				$link	= JRoute::_('index.php?option=com_fabrik&task=list.edit&id='. $item->id);
				$params = new JRegistry($item->params);
				$elementLink = JRoute::_('index.php?option=com_fabrik&task=element.edit&id=0&filter_groupId=' . $this->table_groups[$item->id]->group_id);
 				$formLink = JRoute::_('index.php?option=com_fabrik&task=form.edit&id=' . $item->form_id);
 				$canChange= true;
			?>
			<tr class="row<?php echo $i % 2; ?>">
				<td>
					<?php echo $item->id; ?>
				</td>
				<td><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>
				<td>
					<?php
					if ($item->checked_out && ( $item->checked_out != $user->get('id'))) {?>
					<span class="editlinktip hasTip"
						title="<?php echo $item->label . "::" . $params->get('note'); ?>"> <?php echo $item->label; ?>
					</span>
					<?php } else {?>
					<a href="<?php echo $link;?>">
						<span class="editlinktip hasTip" title="<?php echo $item->label . "::" . $params->get('note'); ?>">
							<?php echo $item->label; ?>
						</span>
					</a>
					<?php } ?>
				</td>
				<td>
					<?php echo $item->db_table_name;?>
				</td>
				<td>
					<a href="<?php echo $elementLink?>">
						<?php echo FText::_('COM_FABRIK_ADD');?>
					</a>
				</td>
				<td>
					<a href="<?php echo $formLink; ?>">
						<?php echo FText::_('COM_FABRIK_EDIT'); ?>
					</a>
				</td>
				<td>
					<a href="index.php?option=com_fabrik&task=list.view&listid=<?php echo $item->id;?>">
						<?php echo FText::_('COM_FABRIK_VIEW_DATA');?>
					</a>
				</td>
				<td>
					<?php echo JHtml::_('jgrid.published', $item->published, $i, 'lists.', $canChange);?>
				</td>
				<td>
					<a href="#showlinkedelements" onclick="return listItemTask('cb<?php echo $i;?>','list.showLinkedElements');">
						<?php echo FText::_('COM_FABRIK_VIEW_DETAILS');?>
					</a>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>
