<?php
/**
 * Form control group
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.4
 */
defined('JPATH_BASE') or die;
$d = $displayData;
$path = 'components/com_fabrik/views/' . $d->view . '/' . $d->jTmplFolder . '/' . $d->tmpl . '/custom_css.php' . $d->qs;
$path .= '&amp;color=00FF00';
echo $path;

