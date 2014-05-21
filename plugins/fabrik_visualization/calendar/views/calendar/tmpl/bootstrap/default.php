<?php
/**
 * Calendar Viz: Bootstrap template
 *
 * @package		Joomla.Plugin
 * @subpackage	Fabrik.visualization.calendar
 * @copyright	Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

//@TODO if we ever get calendars inside packages then the ids will need to be
// Replaced with classes contained within a distinct id

$row = $this->row;
?>
<div id="<?php echo $this->containerId;?>" class="fabrik_visualization" style="border:1px sold;margin:5px;">
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
			<a href="#" class="btn btn-success addEventButton" title="Add an event"><i class="icon-plus"></i> <?php echo FText::_('PLG_VISUALIZATION_CALENDAR_ADD') ?></a>
		<?php endif;
		?>
		</div>

		<div class="span3">
			<div class="btn-group">
				<button class="btn previousPage">
					<i class="icon-chevron-left"></i>
				</button>
				<button class="btn nextPage">
					<i class="icon-chevron-right"></i>
				</button>
			</div>
		</div>

		<div class="span7">
			<div class="btn-group pull-right">
				<button class="btn centerOnToday"><i class="icon-flag"></i> <?php echo FText::_('PLG_VISUALIZATION_CALENDAR_TODAY')?></button>
				<?php
				if ($this->params->get('show_day', true)):
				?>
				<button class="btn dayViewLink"><i class="icon-bookmark"></i> <?php echo FText::_('PLG_VISUALIZATION_CALENDAR_DAY')?></button>
				<?php
				endif;
				if ($this->params->get('show_week', true)):
				?>
				<button class="btn weekViewLink"><i class="icon-list"></i> <?php echo FText::_('PLG_VISUALIZATION_CALENDAR_WEEK')?></button>
				<?php
				endif;
				if ($this->params->get('show_month', true)):
				?>
				<button class="btn monthViewLink"><i class="icon-calendar"></i> <?php echo FText::_('PLG_VISUALIZATION_CALENDAR_MONTH')?></button>
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