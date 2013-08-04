<?php
/**
 * Connections View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

class FabrikViewConenction
{

	/**
	 * set up the menu when viewing the list of connections
	 */

	function setConnectionsToolbar()
	{
		JToolBarHelper::title(JText::_('CONNECTIONS'), 'fabrik-connection.png');
		JToolBarHelper::makeDefault('setdefault');
		JToolBarHelper::publishList();
		JToolBarHelper::unpublishList();
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'Copy');
		JToolBarHelper::deleteList();
		JToolBarHelper::editListX();
		JToolBarHelper::addNewX();
	}

	/**
	 * Set up the menu when editing the connection
	 *
	 * @return  void
	 */

	function setConnectionToolbar()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$task = $input->get('task');
		JToolBarHelper::title(
			$task == 'add' ? JText::_('CONNECTION') . ': <small><small>[ ' . JText::_('NEW') . ' ]</small></small>'
				: JText::_('CONNECTION') . ': <small><small>[ ' . JText::_('EDIT') . ' ]</small></small>', 'fabrik-connection.png');
		JToolBarHelper::save('save');
		JToolBarHelper::apply('apply');
		JToolBarHelper::cancel('cancel');
	}

	/**
	 * Show a list of all the connections
	 *
	 * @param   array  $rows     connection objects
	 * @param   object $pageNav  page navigation
	 *
	 * @return  void
	 */

	function show($rows, $pageNav)
	{
		FabrikViewConenction::setConnectionsToolbar();
		$user = JFactory::getUser();
?>
		<form action="index.php" method="post" name="adminForm">
		<table class="adminlist">
			<thead>
			<tr>
			<th width="2%">#</th>
				<th width="1%" >
					<input type="checkbox" name="toggle" value=""  onclick="checkAll(<?php echo count($rows); ?>);" />
				</th>
				<th width="29%" align="center">
					<?php echo JText::_('LABEL'); ?>
				</th>
				<th width="20%" align="center"><?php echo JText::_('HOST'); ?></th>
				<th width="5%"><?php echo JText::_('DEFAULT'); ?></th>
				<th width="5%" align ="center"><?php echo JText::_('PUBLISHED'); ?></th>
				<th width="20%" ><?php echo JText::_('DATABASE'); ?></th>
				<th width="20%" ><?php echo JText::_('TEST CONNECTION'); ?></th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="8">
					<?php echo $pageNav->getListFooter(); ?>
				</td>
				</tr>
			</tfoot>
			<tbody>
			<?php
		$k = 0;
		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row = &$rows[$i];
			$checked = JHTML::_('grid.checkedout', $row, $i);
			$link = JRoute::_('index.php?option=com_fabrik&c=connection&task=edit&cid=' . $row->id);
			$row->published = $row->state;
			$published = JHTML::_('grid.published', $row, $i);
			?>
			<tr class="<?php echo "row$k"; ?>">
				<td width="1%"><?php echo $row->id; ?></td>
				<td width="1%"><?php echo $checked; ?></td>
				<td width="29%">
					<?php
			if ($row->checked_out && ($row->checked_out != $user->get('id')))
			{
				echo $row->description;
			}
			else
			{
					?>
						<a href="<?php echo $link; ?>" >
						<?php
				echo $row->description;
			}
						?>
					</a>
				</td>
				<td width="25%">
					<?php echo $row->host; ?>
				</td>
				<td align="center">
				<?php if ($row->default == 1)
			{ ?>
					<img src="templates/khepri/images/menu/icon-16-default.png" alt="<?php echo JText::_('DEFAULT'); ?>" />
				<?php }
			else
			{ ?>
				&nbsp;
				<?php } ?>
			</td>
				<td>
					<?php echo $published; ?>
				</td>
				<td width="20%" >
					<?php echo $row->database; ?>
				</td>
				<td width="20%" >
					<a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>','test')">
						<?php echo JText::_('TEST CONNECTION'); ?>
					</a>
				</td>
			</tr>
			<?php
			$k = 1 - $k;
		}
			?>
			</tbody>
		</table>
		<input type="hidden" name="option" value="com_fabrik" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="c" value="connection" />
		<input type="hidden" name="task" value="" />
		<?php echo JHTML::_('form.token'); ?>
	</form>
	<?php
	}

	/**
	 * Edits a database connection
	 *
	 * @param   object  $row  connection
	 *
	 * @return  void
	 */

	function edit($row)
	{
		JHtml::_('behavior.framework');
		FabrikViewConenction::setConnectionToolbar();
		$app = JFactory::getApplication();
		$app->input->set('hidemainmenu', true);
		$config = JFactory::getConfig();
		if ($row->id == 1)
		{
			$app->enqueueMessage(JText::_('THIS IS THE ORIGINAL CONNECTION'));
			if (!($config->get('host') == $row->host && $config->get('user') == $row->user && $config->get('password') == $row->password
				&& $config->get('db') == $row->database))
			{
				JError::raiseWarning(E_WARNING, JText::_('YOUMUSTSAVETHISCNN'));
			}
			$row->host = $config->get('host');
			$row->user = $config->get('user');
			$row->password = $config->get('password');
			$row->database = $config->get('db');

		}
	?>
		<form action="index.php" method="post" name="adminForm">
		<div class="col100">
			<fieldset class="adminform">
				<legend><?php echo JText::_('DETAILS'); ?></legend>
		<table class="admintable">
		<tbody>
			<tr>
				<td valign="top" class="key">
					<label for="description"><?php echo JText::_('DESCRIPTION'); ?></label>
				</td>
				<td><input class="inputbox" type="text" id="description" name="description" size="75" value="<?php echo $row->description; ?>" /></td>
			</tr>
			<tr>
				<td valign="top" class="key">
					<label for="host"><?php echo JText::_('HOST'); ?></label>
				</td>
				<td><input class="inputbox" type="text" id="host" name="host" size="75" value="<?php echo $row->host; ?>" /></td>
			</tr>
			<tr>
		<td valign="top" class="key">
			<label for="database"><?php echo JText::_('DATABASE'); ?></label>
		</td>
		<td><input class="inputbox" type="text" id="database" name="database" size="75" value="<?php echo $row->database; ?>" /></td>
			</tr>
			<tr>
				<td valign="top" class="key">
					<label for="user"><?php echo JText::_('USER'); ?></label>
				</td>
				<td><input class="inputbox" type="text" name="user" id="user" size="75" value="<?php echo $row->user; ?>" /></td>
			</tr>
			<?php if ($row->host != "")
		{ ?>
				<tr>
				<td valign="top" class="key"><?php echo JText::_('ENTER PASSWORD OR LEAVE AS IS'); ?></td>
				<td></td>
			</tr>
			<?php } ?>
			<tr>
				<td valign="top" class="key">
					<label for="password"><?php echo JText::_('PASSWORD'); ?></label>
				</td>
				<td><input class="inputbox" type="password" id="password" name="password" size="20" value="<?php echo $row->password; ?>" /></td>
			</tr>
			<tr>
				<td valign="top" class="key">
					<label for="passwordConf"><?php echo JText::_('CONFIRM PASSWORD'); ?></label>
				</td>
				<td><input class="inputbox" type="password" id="passwordConf" name="passwordConf" size="20" value="<?php echo $row->password; ?>" /></td>
			</tr>
			<tr>
				<td valign="top" class="key"><label for="state"><?php echo JText::_('PUBLISHED'); ?></label></td>
				<td>
					<input type="checkbox" id="state" name="state" value="1" <?php echo $row->state ? 'checked="checked"' : ''; ?> />
				</td>
			</tr>
			<tbody>
		</table>
		<input type="hidden" name="option" value="com_fabrik" />
		<input type="hidden" name="task" value="save" />
		<input type="hidden" name="c" value="connection" />
		<input type="hidden" name="id" value="<?php echo $row->id; ?>" />
		</fieldset>
		</div>
		<?php echo JHTML::_('form.token');
		echo JHTML::_('behavior.keepalive');
		?>
	</form>
<?php
	}
}
