<?php
/**
 * Custom CSS link
 *
 * In a layout so people can add additional QS args that can then be used to set attributes dynamically
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.4
 */
defined('JPATH_BASE') or die;
$d = $displayData;
$path = 'components/com_fabrik/views/list/' . $d->jTmplFolder . '/' . $d->tmpl . '/custom_css.php' . $d->qs;

echo $path;

