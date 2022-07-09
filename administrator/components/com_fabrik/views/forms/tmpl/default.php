<?php
/**
 * Admin Forms List Tmpl
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
<form action="<?php echo JRoute::_('index.php?option=com_fabrik&view=forms'); ?>" method="post" name="adminForm" id="adminForm">

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
				<th width="2%"><?php echo JHTML::_( 'grid.sort',  '#', 'f.id', $listDirn, $listOrder); ?></th>
				<th width="1%"> <input type="checkbox" name="toggle" value="" onclick="checkAll(this);" /> </th>
				<th width="35%" >
					<?php echo JHTML::_( 'grid.sort',  'COM_FABRIK_LABEL', 'f.label', $listDirn, $listOrder); ?>
				</th>
				<th width="5%">
				<?php echo JHTML::_( 'grid.sort',  'JPUBLISHED', 'f.published', $listDirn, $listOrder); ?>
				</th>
				<th></th>
				<th></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="6">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php foreach ($this->items as $i => $item) :
			$ordering	= ($listOrder == 'ordering');
			$link = JRoute::_('index.php?option=com_fabrik&task=form.edit&id='.(int) $item->id);
			$canCreate	= $user->authorise('core.create',		'com_fabrik.form.1');
			$canEdit	= $user->authorise('core.edit',			'com_fabrik.form.1');
			$canCheckin	= $user->authorise('core.manage',		'com_checkin') || $item->checked_out==$user->get('id') || $item->checked_out==0;
			$canChange	= $user->authorise('core.edit.state',	'com_fabrik.form.1') && $canCheckin;
			?>
			<tr class="row<?php echo $i % 2; ?>">
					<td><?php echo $item->id; ?></td>
					<td><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>
					<td>
						<?php
						if ($item->checked_out && ( $item->checked_out != $user->get('id'))) {
							echo  $item->label;
						} else {
						?>
						<a href="<?php echo $link; ?>">
							<?php echo $item->label; ?>
						</a>
					<?php } ?>
					</td>
					<td>
						<?php echo JHtml::_('jgrid.published', $item->published, $i, 'forms.', $canChange);?>
					</td>
					<td>
						<a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>','forms.updateDatabase')">
						<?php echo FText::_('COM_FABRIK_UPDATE_DATABASE')?>
						</a>
					</td>
					<td>
						<a href="index.php?option=com_fabrik&task=list.view&listid=<?php echo $item->list_id?>">
						<?php echo FText::_('COM_FABRIK_VIEW_DATA')?>
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
