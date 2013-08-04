<?php
/**
 * Cron View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

class FabrikViewCron
{

	/**
	 * Set up the menu when viewing the list of cron jobs
	 *
	 * @return  void
	 */

	function setCronsToolbar()
	{
		JToolBarHelper::title(JText::_('SCHEDULED TASKS'), 'fabrik-schedule.png');
		JToolBarHelper::custom('run', 'upload.png', 'upload_f2.png', 'Run');
		JToolBarHelper::publishList();
		JToolBarHelper::unpublishList();
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'Copy');
		JToolBarHelper::deleteList();
		JToolBarHelper::editListX();
		JToolBarHelper::addNewX();
	}

	/**
	 * Set up the menu when editing the cron job
	 *
	 * @return  void
	 */

	function setCronToolbar()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$task = $input->get('task', '');
		JToolBarHelper::title(
			$task == 'add' ? JText::_('SCHEDULED TASK') . ': <small><small>[ ' . JText::_('NEW') . ' ]</small></small>'
				: JText::_('SCHEDULED TASK') . ': <small><small>[ ' . JText::_('EDIT') . ' ]</small></small>', 'fabrik-schedule.png');
		JToolBarHelper::save();
		JToolBarHelper::apply();
		JToolBarHelper::cancel();
	}

	/**
	 * Display the form to add or edit a cronjob
	 *
	 * @param   object  $row            cronjob
	 * @param   object  $params         parameters from attributes
	 * @param   array   $lists          lists
	 * @param   object  &pluginManager  pluginmanager
	 *
	 * @return  void
	 */

	function edit($row, $params, $lists, &$pluginManager)
	{
		$app = JFactory::getApplication();
		$app->input->set('hidemainmenu', true);
		// FabrikHelperHTML::script('administrator/components/com_fabrik/views/namespace.js');
		// FabrikHelperHTML::script('administrator/components/com_fabrik/views/admincron.js');
		FabrikHelperHTML::tips();
		$document = JFactory::getDocument();
		FabrikHelperHTML::addScriptDeclaration(
			"
				requirejs(['administrator/components/com_fabrik/views/namespace',
				'administrator/components/com_fabrik/views/admincron'],
			function() {
				new adminCron({'sel':'" . $row->plugin . "'});
			});

			function submitbutton(pressbutton) {
				var form = document.adminForm;
				if (pressbutton == 'cancel') {
					submitform( pressbutton);
					return;
				}

				/* do field validation */
				if (form.label.value == '') {
					alert('" . JText::_('PLEASE ENTER A LABEL', true) . "');
				} else {
					submitform( pressbutton);
				}
			}
			");
		FabrikViewCron::setCronToolbar();
?>
		<form action="index.php" method="post" name="adminForm">
		<div class="col100">
			<fieldset class="adminform">
				<legend><?php echo JText::_('DETAILS'); ?></legend>
			<table class="admintable">

				<tr>
					<td class="key"><label for="label"><?php echo JText::_('LABEL'); ?></label></td>
					<td><input class="inputbox" type="text" id="label" name="label" size="75" value="<?php echo $row->label; ?>" /></td>
				</tr>

				<tr>
					<td class="key"><label for="frequency"><?php echo JText::_('EVERY'); ?></label></td>
					<td><input class="inputbox" type="text" id="frequency" name="frequency" size="4" value="<?php echo $row->frequency; ?>" /></td>
				</tr>

				<tr>
					<td class="key"><label for="unit"><?php echo JText::_('UNIT'); ?></label></td>
					<td><?php echo $lists['unit']; ?></td>
				</tr>

				<tr>
					<td class="key"><label for="lastrun"><?php echo JText::_('STARTING FROM'); ?></label></td>
					<td><?php echo JHTML::calendar($row->lastrun, 'lastrun', 'lastrun', '%Y-%m-%d %H:%M:%S', array('size' => 23)) ?></td>
				</tr>

				<tr>
					<td class="key"><label for="state"><?php echo JText::_('PUBLISHED'); ?></label></td>
					<td>
					<input type="checkbox" id="state" name="state" value="1" <?php echo $row->state ? 'checked="checked"' : ''; ?> />
					</td>
				</tr>
				<tr>
					<td colspan="2">
					<?php
		echo stripslashes($params->render());
					?>
					</td>
				</tr>
				<tr>
					<td class="key">
						<label for=""><?php echo JText::_('PLUGIN'); ?></label>
					</td>
					<td>
						<?php echo $lists['plugins']; ?>
					</td>
				</tr>
				<?php
		$plugins = $pluginManager->getPlugInGroup('cron');
		foreach ($plugins as $plugin)
		{
			$plugin->setId($row->id);
				?>
					<tr>
					<td colspan="2">
						<?php
			$plugin->renderAdminSettings();
						?>
						</td>
					</tr>
					<?php }
					?>
			</table>
			</fieldset>
				<input type="hidden" name="option" value="com_fabrik" />
				<input type="hidden" name="c" value="cron" />
				<input type="hidden" name="task" />
				<input type="hidden" name="id" value="<?php echo $row->id; ?>" />
			</div>
			<?php echo JHTML::_('form.token');
		echo JHTML::_('behavior.keepalive');
			?>
		</form>
	<?php }

	/**
	 * Display all available cron tasks
	 *
	 * @param   array   $rows     array of cron objects
	 * @param   object  $pageNav  page navigation
	 * @param   array   $lists    lists
	 *
	 * @return  void
	 */

	function show($rows, $pageNav, $lists)
	{
		FabrikViewCron::setCronsToolbar();
		$user = JFactory::getUser();
	?>
		<form action="index.php" method="post" name="adminForm">
		<table class="adminlist">
			<thead>
			<tr>
				<th width="2%"><?php echo JHTML::_('grid.sort', '#', 'g.id', @$lists['order_Dir'], @$lists['order']); ?></th>
				<th width="1%"> <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($rows); ?>);" /> </th>
				<th width="35%">
					<?php echo JHTML::_('grid.sort', 'Label', 'label', @$lists['order_Dir'], @$lists['order']); ?>
				</th>
				<th width="5%">
				<?php echo JHTML::_('grid.sort', 'Published', 'g.state', @$lists['order_Dir'], @$lists['order']); ?>
				</th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="6">
					<?php echo $pageNav->getListFooter(); ?>
				</td>
				</tr>
			</tfoot>
			<tbody>
			<?php $k = 0;
		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row = &$rows[$i];
			$checked = JHTML::_('grid.checkedout', $row, $i);
			$link = JRoute::_('index.php?option=com_fabrik&c=cron&task=edit&cid=' . $row->id);
			$row->published = $row->state;
			$published = JHTML::_('grid.published', $row, $i);
			?>
				<tr class="<?php echo "row$k"; ?>">
					<td width="2%"><?php echo $row->id; ?></td>
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
			</tbody>
		</table>
		<input type="hidden" name="option" value="com_fabrik" />
		<input type="hidden" name="c" value="cron" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $lists['order_Dir']; ?>" />
		<?php echo JHTML::_('form.token'); ?>
	</form>
	<?php }
}
