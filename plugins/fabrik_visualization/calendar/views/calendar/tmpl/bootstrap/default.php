<?php
/**
 * Calendar Viz: Bootstrap template
 *
 * @package		Joomla.Plugin
 * @subpackage	Fabrik.visualization.calendar
 * @copyright	Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

//@TODO if we ever get calendars inside packages then the ids will need to be
// Replaced with classes contained within a distinct id

$row = $this->row;
?>
<div id="<?php echo $this->containerId;?>" class="fabrik_visualization" style="margin:5px;">
	<?php if ($this->params->get('show-title', 0))
	{?>
		<h1><?php echo $row->label;?></h1>
	<?php }?>
	<div class='calendar-message'>

	</div>
	<?php echo $this->loadTemplate('filter'); ?>

	<div class="well well-small monthDisplay">
	</div>
	<div class="row-fluid">
		<div class="span2">

			<?php if ($this->canAdd) :
			?>
			<a href="#" class="btn btn-success addEventButton" title="Add an event"><?php echo FabrikHelperHTML::icon('icon-plus'); ?> <?php echo FText::_('PLG_VISUALIZATION_CALENDAR_ADD') ?></a>
		<?php endif;
		?>
		</div>

		<div class="span3">
			<div class="btn-group">
				<button class="btn previousPage">
					<?php echo FabrikHelperHTML::icon('icon-chevron-left'); ?>
				</button>
				<button class="btn nextPage">
					<?php echo FabrikHelperHTML::icon('icon-chevron-right'); ?>
				</button>
			</div>
		</div>

		<div class="span7">
			<div class="btn-group pull-right">
				<button class="btn centerOnToday"><?php echo FabrikHelperHTML::icon('icon-flag'); ?> <?php echo FText::_('PLG_VISUALIZATION_CALENDAR_TODAY')?></button>
				<?php
				if ($this->params->get('show_day', true)):
				?>
				<button class="btn dayViewLink"><?php echo FabrikHelperHTML::icon('icon-bookmark'); ?> <?php echo FText::_('PLG_VISUALIZATION_CALENDAR_DAY')?></button>
				<?php
				endif;
				if ($this->params->get('show_week', true)):
				?>
				<button class="btn weekViewLink"><?php echo FabrikHelperHTML::icon('icon-list'); ?> <?php echo FText::_('PLG_VISUALIZATION_CALENDAR_WEEK')?></button>
				<?php
				endif;
				if ($this->params->get('show_month', true)):
				?>
				<button class="btn monthViewLink"><?php echo FabrikHelperHTML::icon('icon-calendar'); ?> <?php echo FText::_('PLG_VISUALIZATION_CALENDAR_MONTH')?></button>
				<?php
				endif;
				?>
			</div>
		</div>
	</div>

	<?php if ($row->intro_text != '')
	{?>
	<div><?php echo $row->intro_text;?></div>
	<?php }
	?>

</div>