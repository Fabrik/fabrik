<?php
/**
 * Bootstrap Details Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="row-striped">
	<?php
	$rowStarted = false;
	foreach ($this->elements as $element) :
		$this->element = $element;
		if ($element->startRow) : ?>
			<div class="row-fluid">
			$rowStarted = true;
		<?php
		endif;
		$style = $element->hidden ? 'style="display:none"' : '';
		$labels_above = $this->params->get('labels_above_details', 0);
		if ($labels_above == 1)
		{
			echo $this->loadTemplate('group_labels_above');
		}
		elseif ($labels_above == 2)
		{
			echo $this->loadTemplate('group_labels_none');
		}
		elseif ($element->span == 'span12' || $element->span == '' || $labels_above == 0)
			{
				echo $this->loadTemplate('group_labels_side');
			}
		else
		{
			// Multi columns - best to use simplified layout with labels above field
			echo $this->loadTemplate('group_labels_above');
		}
		if ($element->endRow) :?>
			</div><!-- end row-fluid -->
			$rowStarted = false;
		<?php endif;
	endforeach;
	// If the last element was not closing the row add an additional div
	if ($rowStarted === true) :?>
	</div><!-- end row-fluid for open row -->
	<?php endif;?>
</div>
