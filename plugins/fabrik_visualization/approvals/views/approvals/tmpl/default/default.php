<?php
/**
 * Approval Viz: Default Tmpl
 *
 * @package		Joomla.Plugin
 * @subpackage	Fabrik.visualization.approvals
 * @copyright	Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

//@TODO if we ever get calendars inside packages then the ids will need to be
// Replaced with classes contained within a distinct id

$app = JFactory::getApplication();
$package = $app->getUserState('com_fabrik.package', 'fabrik');
$row = $this->row;
?>

<div id="<?php echo $this->containerId; ?>" class="fabrik_visualization">
	<?php if ($this->params->get('show-title', 0))
{ ?>
		<h1><?php echo $row->label; ?></h1>
	<?php } ?>
	<table class="fabrikList">
		<thead>
			<tr class="fabrik___heading">
				<th><?php echo 'Type';//FText::_('PLG_VIZ_APPROVALS_TYPE') ?></th>
				<th><?php echo 'Title';//FText::_('PLG_VIZ_APPROVALS_TITLE') ?></th>
				<th><?php echo 'User';//FText::_('PLG_VIZ_APPROVALS_USER') ?></th>
				<th style="width:15%;text-align:center"><?php echo 'View';//FText::_('PLG_VIZ_APPROVALS_VIEW') ?></th>
				<th style="width:15%;text-align:center"><?php echo 'Approve';//FText::_('PLG_VIZ_APPROVALS_APPROVE') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
$i = 0;
foreach ($this->rows as $row)
{
			   ?>
				<tr class="fabrik_row oddRow<?php echo $i % 2 ?>">
					<td><?php echo $row->type ?></td>
					<td><?php echo $row->title ?></td>
					<td><?php echo $row->user ?></td>
					<td  style="text-align:center">
					<a href="<?php echo $row->view ?>">
					<a class="fabrikTip" opts="{position:'right'}" title="<?php echo FabrikString::truncate($row->content,
		array('tip' => false, 'wordcount' => 200)) ?>" >
						<?php echo FabrikHelperHTML::image('view.png', 'list', ''); ?>
					</a></td>
					<td style="text-align:center">
						<a href="#" class="approvalTip">
							<?php echo FabrikHelperHTML::image('attention2.png', 'list', ''); ?>
						</a>
						<div class="floating-tip" style="display:none">
						<ul class="view approvals">
							<li>
								<a class="approve" href="index.php?option=com_<?php echo $package?>&format=raw&task=visualization.display&visualizationid=<?php echo $this
		->id ?>&plugintask=approve&listid=<?php echo $row->listid ?>&rowid=<?php echo $row->rowid ?>">
									<?php echo FabrikHelperHTML::image('approve.png', 'visualization', ''); ?><span>approve</span>
								</a>
							</li>
							<li>
								<a class="disapprove"  href="index.php?option=com_<?php echo $package?>&format=raw&task=visualization.display&visualizationid=<?php echo $this
		->id ?>&plugintask=disapprove&listid=<?php echo $row->listid ?>&rowid=<?php echo $row->rowid ?>">
									<?php echo FabrikHelperHTML::image('disapprove.png', 'visualization', ''); ?><span>disapprove</span>
									</a>
							</li>
						</ul>
						</div>
					</td>
				</tr>
			<?php $i++;
}
					 ?>
		</tbody>
	</table>
</div>
