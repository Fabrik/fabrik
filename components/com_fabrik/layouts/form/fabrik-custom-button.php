<?php
/**
 * Do-nothing layout, which can be overridden to create custom button(s) that will be rendered
 * next to the Submit button on a form.  Just a convenience so custom buttons can be added without
 * using a custom template.
 *
 * $displayData is:
 *
 * 'formModel' => $model,
 * 'row' => $row,
 * 'rowid' => $thisRowId,
 * 'itemid' => $itemId
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.6
 */
defined('JPATH_BASE') or die;
$d = $displayData;
?>