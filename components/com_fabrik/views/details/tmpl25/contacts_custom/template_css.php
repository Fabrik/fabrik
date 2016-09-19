<?php
/**
 * Contacts Custom Form Template: CSS
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

header('Content-type: text/css');
$c = (int) $_REQUEST['c'];
$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : 'form';
$rowid = isset($_REQUEST['rowid']) ? $_REQUEST['rowid'] : '';
$form = $view . '_' . $c;
$c_row = $c;
if ($rowid !== '')
{
	$form .= '_' . $rowid;
	$c_row .= '_' . $rowid;
}

echo "
#{$form} .fabrikElement {
	margin-left: 10px;
}

#{$form} .fabrikLabel {
	width: 100px;
	clear: left;
	float: left;
}

#{$form} .fabrikActions {
	padding-top: 15px;
	clear: left;
	padding-bottom: 15px;
}

#{$form} .fabrikGroupRepeater {
	float: left;
	width: 19%;
}

/** used by password element */
#{$form} .fabrikSubLabel {
	margin-left: -10px;
	clear: left;
	margin-top: 10px;
	float: left;
}

.fabrikSubElement {
	display: block;
	margin-top: 10px;
}

.example {
	#float: left;
	#width: 33%;
	margin-top: 10px;
	padding: 5px 10px;
}

.example .fabrikElement {
	#margin-right: 20px;
	#margin-left: 0px;
	#margin-bottom: 15px;
}

.example .fabrikLabel {
	#float: none;
	#clear: none;
}

#{$form} .addGroup:link {
	text-decoration: none;
}

#{$form} .addGroup:link {
	text-decoration: none;
}
";?>


