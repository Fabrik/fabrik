<?php
/**
 * * Calendar Viz: Default Tmpl
 *
 * @package        Joomla.Plugin
 * @subpackage     Fabrik.visualization.calendar
 * @copyright      Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license        GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

//@TODO if we ever get calendars inside packages then the ids will need to be
// Replaced with classes contained within a distinct id

$row = $this->row;
echo $this->modalLayout;
?>

<div id="<?php echo $this->containerId; ?>" class="fabrik_visualization" style="border:1px sold;margin:5px;">
	<?php if ($this->params->get('show-title', 0))
	{
		?>
		<h1><?php echo $row->label; ?></h1>
	<?php } ?>

	<?php if ($row->intro_text != '')
	{
		?>
		<div><?php echo $row->intro_text; ?></div>
	<?php }
	?>

	<div class='calendar-message'>

	</div>
	<?php echo $this->loadTemplate('filter'); ?>

	<div class="row-fluid">
		<div class="span2">

			<?php if ($this->canAdd && $this->params->get('add_type', 'both') != 'dblClickOnly') :
				?>
				<div id="addEventButton" style='display:inline;'>
					<a href="#" class="btn btn-success addEventButton" title="Add an event"><i class="icon-plus"></i> <?php echo FText::_('PLG_VISUALIZATION_FULLCALENDAR_ADD') ?>
					</a>
				</div>

			<?php endif;
			?>
		</div>
		<div data-role="calendar">
		</div>
	</div>
	<div class='calendar-legend'>
		<?php if ($this->params->get('show_fullcalendar_legend', 0))
		{
			$legends = $this->getModel()->getLegend();
			echo "<h3>" . JText::_('PLG_VISUALIZATION_FULLCALENDAR_KEY') . "</h3>";
			echo "<ul>";

			foreach ($legends as $legend)
			{
				echo '<li><div style="background-color: ' . $legend['colour'] . ';"></div>';
				echo '<span>' . $legend['label'] . '</span>';
				echo '</li>';
			}
			echo '</ul>';
		} ?>
	</div>
</div>