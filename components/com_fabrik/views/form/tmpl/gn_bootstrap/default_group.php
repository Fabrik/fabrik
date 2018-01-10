<?php
/**
 * Bootstrap Form Template - Group
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$rowStarted      = false;
$layout          = FabrikHelperHTML::getLayout('form.fabrik-control-group');
$gridStartLayout = FabrikHelperHTML::getLayout('grid.fabrik-grid-start');
$gridEndLayout   = FabrikHelperHTML::getLayout('grid.fabrik-grid-end');

foreach ($this->elements as $element) :
	$this->element = $element;
	$this->class = 'fabrikErrorMessage';

	// Don't display hidden element's as otherwise they wreck multi-column layouts
	if (trim($element->error) !== '') :
		$element->error = $this->errorIcon . ' ' . $element->error;
		$element->containerClass .= ' error';
		$this->class .= ' help-inline text-danger';
	endif;

	if ($element->startRow) :
		echo $gridStartLayout->render(new stdClass);
		$rowStarted = true;
	endif;

	$style = $element->hidden ? 'style="display:none"' : '';
	$span  = $element->hidden ? '' : ' ' . $element->span;

	$displayData = array(
		'class' => $element->containerClass,
		'style' => $style,
		'span' => $span
	);

	$labelsAbove = $element->labels;

	if ($labelsAbove == 1)
	{
		$displayData['row'] = $this->loadTemplate('group_labels_above');
	}
	elseif ($labelsAbove == 2)
	{
		$displayData['row'] = $this->loadTemplate('group_labels_none');
	}
	elseif ($element->span == FabrikHelperHTML::getGridSpan(12) || $element->span == '' || $labelsAbove == 0)
	{
		$displayData['row'] = $this->loadTemplate('group_labels_side');
	}
	else
	{
		// Multi columns - best to use simplified layout with labels above field
		$displayData['row'] = $this->loadTemplate('group_labels_above');
	}

	echo $layout->render((object) $displayData);

	?><?php
	if ($element->endRow) :
		echo $gridEndLayout->render(new stdClass);
		$rowStarted = false;
	endif;
endforeach;

// If the last element was not closing the row add an additional div
if ($rowStarted === true) :
	echo $gridEndLayout->render(new stdClass);
endif;
