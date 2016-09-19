<?php
/**
 * Bootstrap Tabs Form Template - group details
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$rowStarted = false;
foreach ($this->elements as $element) :
	$this->element = $element;
	$this->class = 'fabrikErrorMessage';

	// Don't display hidden element's as otherwise they wreck multi-column layouts
	if (trim($element->error) !== '') :
		$element->error = $this->errorIcon . ' ' . $element->error;
		$element->containerClass .= ' error';
		$this->class .= ' help-inline text-danger';
	endif;

	if ($element->startRow) : ?>
		<div class="row-fluid">
	<?php
		$rowStarted = true;
	endif;
	$style = $element->hidden ? 'style="display:none"' : '';
	$span = $element->hidden ? '' : ' ' . $element->span;
	?>
			<div class="control-group <?php echo $element->containerClass . $span; ?>" <?php echo $style?>>
	<?php
	$labels_above = $element->dlabels;
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
	?></div><!-- end control-group --><?php
	if ($element->endRow) :?>
		</div><!-- end row-fluid -->
	<?php
		$rowStarted = false;
	endif;
endforeach;

// If the last element was not closing the row add an additional div
if ($rowStarted === true) :?>
	</div><!-- end row-fluid for open row -->
<?php endif;
