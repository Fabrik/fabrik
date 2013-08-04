<?php
/**
 * Fabrik Admin Package View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

class FabrikViewPackage
{

	/**
	 * set up the menu when viewing the list of  packages
	 */

	function setPackagesToolbar()
	{
		JToolBarHelper::title(JText::_('PACKAGES'), 'fabrik-package.png');
		JToolBarHelper::custom('startExport', 'export.png', 'export_f2.png', 'Export');
		JToolBarHelper::custom('import', 'copy.png', 'copy_f2.png', 'Import', false);
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'Copy');
		JToolBarHelper::deleteList();
		JToolBarHelper::editListX();
		JToolBarHelper::addNewX();
	}

	/**
	 * set up the menu when editing the package
	 */

	function setPackageToolbar()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$task = $input->get('task', '');
		JToolBarHelper::title(
			$task == 'add' ? JText::_('PACKAGE') . ': <small><small>[ ' . JText::_('NEW') . ' ]</small></small>'
				: JText::_('PACKAGE') . ': <small><small>[ ' . JText::_('EDIT') . ' ]</small></small>', 'fabrik-package.png');
		JToolBarHelper::save();
		JToolBarHelper::apply();
		JToolBarHelper::cancel();
	}

	/**
	 * toolbar for export
	 */

	function setPackageExportToolbar()
	{
		JToolBarHelper::custom('export', 'forward.png', 'forward_f2.png', 'Export', false);
	}

	/**
	 * show export settings
	 */

	/**
	 * export table settings
	 */

	function exportSettings($rows)
	{
		FabrikViewPackage::setPackageExportToolbar();
		echo "<h1 class=\"sectionname\">" . JText::_('EXPORT') . "</h1>";
		echo "<p>" . JText::_('FOR THE FOLLOWING PACKAGES') . ":</p>";
		echo "<ul>";
		foreach ($rows as $package)
		{
			echo "<li>" . $package->label . "</li>";
		}
		echo "</ul>";
		echo "<p>" . JText::_('CHOOSE EXPORT FILE TYPE') . "</p>";
?>
	 	<form action="index3.php" method="post" name="adminForm">
	 	<?php
		foreach ($rows as $oTable)
		{
			echo "<input type='hidden' name='cid[]' value='" . $oTable->id . "' />";
		}
		 ?>
	 	<table cellpadding="4" cellspacing="0" border="0" width="100%"  class="adminform">
	 		<tr>
	 			<th colspan="2"><?php echo JText::_('FORMAT'); ?></th>
	 		</tr>
	 		<tr>
	 			<td><?php echo JText::_('FORMAT'); ?></td>
	 			<td>
	 				<label><input class="inputbox" checked="checked" type="radio" name="format" value="xml" />xml</label>
	 			</td>
	 		</tr>
	 		<tr>
	 			<td><?php echo JText::_('LABEL'); ?></td>
	 			<td>
	 				<label><input class="inputbox" name="label" value="" /></label>
	 			</td>
	 		</tr>

	 		<tr>
	 			<th colspan="2"><?php echo JText::_('OPTIONS'); ?></th>
	 		</tr>

	 		<!--<tr>
	 			<td><label for="joins"><?php //echo _EXPORT_JOINS ; ?></label></td>
	 			<td><input class="inputbox" type="checkbox" id="joins" name="joins" value="1" /></td>
	 		</tr>-->
	 		<tr>
	 			<td><label for="fabrikfields"><?php echo JText::_('EXPORT FABRIK STRUCTURE'); ?></label></td>
	 			<td><input class="inputbox" type="checkbox" name="fabrikfields" id="fabrikfields" value="1" /></td>
	 		</tr>
	 		<tr>
	 			<td><label for="tabledata"><?php echo JText::_('EXPORT TABLE STRUCTURE'); ?></label></td>
	 			<td><input class="inputbox" type="checkbox" id="tablestructure" name="tablestructure" value="1" /></td>
	 		</tr>
	 		<tr>
	 			<td><label for="tabledata"><?php echo JText::_('EXPORT TABLE DATA'); ?></label></td>
		<td><input class="inputbox" type="checkbox" id="tabledata"
			name="tabledata" value="1" /></td>
	</tr>
	 	</table>
	 	<input type="hidden" name="option" value="com_fabrik" />
	 	<input type="hidden" name="c" value="package" />
		<input type="hidden" name="task" value="doexportTable" />
		<input type="hidden" name="no_html" value="1" />
		<?php echo JHTML::_('form.token'); ?>
	</form>
	 	<?php
	}

	/**
	 * Display the form to add or edit a package
	 *
	 * @param   object  $row     package
	 * @param   object  $tables  parameters from attributes
	 * @param   array   $lists   lists
	 */

	function edit($row, $tables, $lists)
	{
		JHtml::_('behavior.framework');
		JHTML::stylesheet('administrator/components/com_fabrik/views/fabrikadmin.css');
		$app = JFactory::getApplication();
		$app->input->set('hidemainmenu', true);
		jimport('joomla.html.pane');
		$pane = JPane::getInstance();
		FabrikViewPackage::setPackageToolbar();
		FabrikHelperHTML::script('administrator/components/com_fabrik/views/adminpackage.js');
		 ?>
		<form action="index.php" method="post" name="adminForm">
		<table style="width:100%;">
		 	<tr>
	 			<td style="width:50%;" valign="top">
	 			<fieldset class="adminform">
				<legend><?php echo JText::_('DETAILS'); ?></legend>
				<table class="admintable">
					<tr>
						<td class="key">
						<label for="label"><?php echo JText::_('LABEL'); ?></label>
						</td>
						<td><input class="inputbox" type="text" id="label" name="label" size="30" value="<?php echo $row->label; ?>" /></td>
					</tr>
					<tr>
						<td class="key" valign="top"  style="text-align:right">
						<label for="state"><?php echo JText::_('PUBLISHED'); ?></label>
						</td>
						<td>
						<input type="checkbox" id="state" name="state" value="1" <?php echo $row->state ? 'checked="checked"' : ''; ?> />
						</td>
					</tr>
					<tr>
						<td class="key" valign="top"  style="text-align:right">
							<label for="template"><?php echo JText::_('TEMPLATE'); ?></label>
						</td>
						<td>
						<?php echo $lists['template']; ?>
						</td>
					</tr>
				</table>
				</fieldset>
				<fieldset class="adminform">
				<legend><?php echo JText::_('TABLES'); ?></legend>

				<table class="admintable">

					<?php foreach ($tables as $table)
		{ ?>
					<tr class="packageTable" >
						<td><?php echo $table; ?></td>
						<td style="width:5em"><a href="#" class="addButton"><?php echo JText::_('COM_FABRIK_ADD'); ?></a></td>
						<td><a href="#" class="removeButton"><?php echo JText::_('DELETE'); ?></a></td>
					</tr>
					<?php } ?>
				</table>
				</fieldset>
				</td>
				<td style="width:50%;"  valign="top">

				<?php echo $pane->endPanel();
		echo $pane->endPane();
				?>
				</td>
			</tr>
		</table>
		<input type="hidden" name="option" value="com_fabrik" />
		<input type="hidden" name="c" value="package" />
		<input type="hidden" name="task" value="savePackage" />
		<input type="hidden" name="id" value="<?php echo $row->id; ?>" />
		<input type="hidden" name="cid[]" value="<?php echo $row->id; ?>" />
		<?php echo JHTML::_('form.token');
		echo JHTML::_('behavior.keepalive');
		?>
	</form>
	<?php }

	/**
	 * Display all available packages
	 *
	 * @param   array  $packages  array of package_rule objects
	 * @param   object $pageNav   page navigation
	 *
	 * @return  void
	 */

	function show($packages, $pageNav)
	{
		FabrikViewPackage::setPackagesToolbar();
		$user = JFactory::getUser();
		$n = count($packages);
	?>

		<form action="index.php" method="post" name="adminForm">
			<table class="adminlist">
				<thead>
				<tr>
					<th width="2%">#</th>
					<th width="1%">
						<input type="checkbox" id="toggle" name="toggle" value="" onclick="checkAll(<?php echo $n; ?>);" />
					</th>
					<th width="95%" ><?php echo JText::_('LABEL'); ?></th>
					<th width="3%"><?php echo JText::_('PUBLISHED'); ?></th>
				</tr>
				</thead>
				<?php
		$k = 0;
		for ($i = 0; $i < $n; $i++)
		{
			$row = &$packages[$i];
			$checked = JHTML::_('grid.checkedout', $row, $i);
			$link = JRoute::_('index.php?option=com_fabrik&c=package&task=edit&cid=' . $row->id);
			$row->published = $row->state;
			$published = JHTML::_('grid.published', $row, $i);
				?>
					<tr class="<?php echo "row$k"; ?>">
					<td><?php echo $row->id; ?></td>
					<td width="1%"><?php echo $checked; ?></td>
					<td width="35%">
						<?php
			if ($row->checked_out && ($row->checked_out != $user->get('id')))
			{
				echo $row->label;
			}
			else
			{
						?>
						<a href="<?php echo $link; ?>">
							<?php echo $row->label; ?>
						</a>
					<?php } ?>
					</td>

					<td width="5%">
						<?php echo $published; ?>
					</td>
				</tr>

				<?php $k = 1 - $k;
		}
				?>
				<tfoot>
					<tr><td colspan="4">
						<?php echo $pageNav->getListFooter(); ?>
					</td></tr>
				</tfoot>
			</table>
			<input type="hidden" name="option" value="com_fabrik" />
			<input type="hidden" name="c" value="package" />
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="task" value="package" />
			<?php echo JHTML::_('form.token'); ?>
		</form>
	<?php }
}
