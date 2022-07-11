<?php
/**
 * Email list plugin template example
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$formModel = $model->getFormModel();

foreach ($data as $name => $value)
{
	if (preg_match('#_raw$#', $name))
	{
		continue;
	}

	$elementModel = $formModel->getElement($name);

	if (empty($elementModel))
	{
		continue;
	}

	$element = $elementModel->getElement();
	$label = $element->label;
	//$fval = $elementModel->renderListData($val, $row);
	echo "$name : $label : $value <br />\n"; 
}
