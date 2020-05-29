<?php
/**
 * Fabrik List Empty Row Template: Div
 *
 * This template is needed in order to wrap the Bootstrap grid wrapper around a row template, for use by the
 * list JS when handling AJAX refreshes that start with an empty list.  You shouldn't need to modify this for
 * your custom div templates, EXCEPT to set $columns to the same as your default_row.php.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// The number of columns to split the list rows into
$columns = 3;

$items = array();

$items[] = $this->loadTemplate('row');

$class = 'fabrik_row well row-striped';
$grid = FabrikHelperHTML::bootstrapGrid($items, $columns, $class, false, $this->_row->id);
array_pop($grid);
array_shift($grid);

echo implode("\n", $grid);

?>
