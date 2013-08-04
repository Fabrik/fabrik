<?php
/**
 * Fabrik Vizualization View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

class FabrikViewVisualization
{

	/**
	 * set up the menu when viewing the list of  Visualizations
	 */

	function setVisualizationsToolbar()
	{
		JToolBarHelper::title(JText::_('VISUALIZATIONS'), 'fabrik-visualization.png');
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'Copy');
		JToolBarHelper::deleteList();
		JToolBarHelper::editListX();
		JToolBarHelper::addNewX();
	}

	/**
	 * set up the menu when editing the Visualization
	 *
	 * @return  void
	 */

	function setVisualizationToolbar()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$task = $input->get('task', '');
		JToolBarHelper::title(
			$task == 'add' ? JText::_('VISUALIZATION') . ': <small><small>[ ' . JText::_('NEW') . ' ]</small></small>'
				: JText::_('VISUALIZATION') . ': <small><small>[ ' . JText::_('EDIT') . ' ]</small></small>', 'fabrik-visualization.png');
		JToolBarHelper::save();
		JToolBarHelper::apply();
		JToolBarHelper::cancel();
	}

	/**
	 * Display the form to add or edit a Visualization
	 *
	 * @param   object  &$row           Visualization
	 * @param   object  &$params        parameters from attributes
	 * @param   array   &lists          lists
	 * @param   object  &menus          menus
	 * @param   object  &pluginManager  pluginmanager
	 * @param   object  &form           form - used to render xml form code
	 *
	 * @return  void
	 */

	public function edit(&$row, &$params, &$lists, &$menus, &$pluginManager, &$form)
	{
		$app = JFactory::getApplication();
		$app->input->set('hidemainmenu', true);
		FabrikViewVisualization::setVisualizationToolbar();
		FabrikHelperHTML::tips();
		JFilterOutput::objectHTMLSafe($row);
		jimport('joomla.html.pane');
		$pane = JPane::getInstance();
		$editor = JFactory::getEditor();
		$js =
	"requirejs(['administrator/components/com_fabrik/views/namespace',
	'administrator/components/com_fabrik/views/adminvisualization'],
	function() {
		new adminVisualization({'sel':'" . $row->plugin . "'});
	});";
		$js .= "
		function submitbutton(pressbutton) {
			var form = document.adminForm;
			if (pressbutton == 'cancel') {
				submitform( pressbutton);
				return;
			}
			// do field validation
			if (\$('plugin').getValue() == '') {
				alert('" . JText::_('YOU MUST SELECT A PLUGIN.', true) . "');
			} elseif (\$('label').getValue()  == '') {
				alert('" . JText::_('PLEASE ENTER A LABEL', true) . "');
			} else {
				submitform( pressbutton);
			}
		}";
		FabrikHelperHTML::addScriptDeclaration($js);
?>
		<form action="index.php" method="post" name="adminForm">
			<table style="width:100%;">
		 		<tr>
	 			<td  valign="top" style="width:50%;">
	 			<fieldset class="adminform">
					<legend><?php echo JText::_('DETAILS'); ?></legend>
	 				<table class="admintable">
	 					<tr>
							<td class="key" width="30%"><label for="label"><?php echo JText::_('LABEL'); ?></label></td>
							<td width="70%">
								<input class="inputbox" type="text" name="label" id="label"" size="50" value="<?php echo $row->label; ?>" />
							</td>
						</tr>
					<tr>
						<td class="key">
							<label for="intro_text">
								<?php echo JText::_('INTRO TEXT'); ?>
							</label>
						</td>
						<td>
							<?php
		echo $editor->display('intro_text', $row->intro_text, '100%', '200', '50', '5', false);
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
								$plugins = $pluginManager->getPlugInGroup('visualization');
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
							</td>
						</tr>
	 				</table>
	 				</fieldset>
	 			</td>
	 			<td valign="top">
	 				<?php
		echo $pane->startPane("content-pane");
		echo $pane->startPanel(JText::_('PUBLISHING'), "publish-page");
		echo $form->render('details');
		echo $pane->endPanel();
		echo $pane->endPane();
					 ?>
		 			</td>
		 		</tr>
	 		</table>
	 		<input type="hidden" name="task" value="">
			<input type="hidden" name="option" value="com_fabrik" />
			<input type="hidden" name="c" value="visualization" />
			<input type="hidden" name="id" value="<?php echo $row->id; ?>" />
			<?php echo JHTML::_('form.token');
		echo JHTML::_('behavior.keepalive');
			?>
		</form>
	<?php
	}

	/**
	 * Display all available Visualizations
	 *
	 * @param   array   $visualizations  array of objects
	 * @param   object  $pageNav         page navigation
	 * @param   array   $lists           lists
	 *
	 * @return  void
	 */

	function show($visualizations, $pageNav, $lists)
	{
		FabrikViewVisualization::setVisualizationsToolbar();
		$user = &JFactory::getUser();
		$n = count($visualizations);
	?>

		<form action="index.php" method="post" name="adminForm">
		<?php echo $lists['vizualizations']; ?>
			<table class="adminlist">
				<thead>
				<tr>
					<th width="2%"><?php echo JHTML::_('grid.sort', '#', 'id', @$lists['order_Dir'], @$lists['order']); ?></th>
					<th width="1%">
						<input type="checkbox" id="toggle" name="toggle" value="" onclick="checkAll(<?php echo $n; ?>);" />
					</th>
					<th width="40%"><?php echo JText::_('LABEL'); ?></th>
					<th width="45%"><?php echo JText::_('TYPE'); ?></th>
					<th width="3%"><?php echo JText::_('PUBLISHED'); ?></th>
				</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="5">
						<?php echo $pageNav->getListFooter(); ?>
					</td>
					</tr>
				</tfoot>
				<tbody>
				<?php
		$k = 0;
		for ($i = 0; $i < $n; $i++)
		{
			$row = &$visualizations[$i];
			$checked = JHTML::_('grid.checkedout', $row, $i);
			$link = JRoute::_('index.php?option=com_fabrik&c=visualization&task=edit&cid=' . $row->id);
			$row->published = $row->state;
			$published = JHTML::_('grid.published', $row, $i);
				?>
					<tr class="<?php echo "row$k"; ?>">
						<td width="1%"><?php echo $row->id; ?></td>
						<td width="1%"><?php echo $checked; ?></td>
						<td width="29%"><?php
			if ($row->checked_out && ($row->checked_out != $user->get('id')))
			{
				echo $row->label;
			}
			else
			{
										?> <a href="<?php echo $link; ?>"><?php echo $row->label; ?></a> <?php } ?>
						</td>
						<td>
							<?php echo $row->plugin; ?>
						</td>
						<td>
							<?php echo $published; ?>
						</td>

					</tr>
					<?php $k = 1 - $k;
		}
					?>
				</tbody>
			</table>
			<input type="hidden" name="option" value="com_fabrik" />
			<input type="hidden" name="c" value="visualization" />
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="task" value="visualization" />
			<?php echo JHTML::_('form.token'); ?>
		</form>
	<?php }
}
