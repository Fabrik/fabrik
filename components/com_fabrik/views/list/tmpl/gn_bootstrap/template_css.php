<?php
/**
 * Fabrik List Template: Bootstrap
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

header('Content-type: text/css');
$c = $_REQUEST['c'];
$buttonCount = (int) $_REQUEST['buttoncount'];
$buttonTotal = $buttonCount === 0 ? '100%' : 30 * $buttonCount ."px";
echo "

.fabrikDataContainer {
	clear:both;
	/*
		dont use this as it stops dropdowns from showing correctly
		overflow: auto;*/
}

.fabrikDataContainer .pagination a{
	float: left;
}

ul.fabrikRepeatData {
	list-style: none;
	list-style-position:inside;
	margin: 0;
	padding-left: 0;
}
.fabrikRepeatData > li {
	white-space: nowrap;
	max-width:350px;
	overflow:hidden;
	text-overflow: ellipsis;
}
td.repeat-merge div, td.repeat-reduce div,
td.repeat-merge i, td.repeat-reduce i {
padding: 5px !important;
}

.nav li {
list-style: none;
}

.filtertable_horiz {
	display: inline-block;
	vertical-align: top;
}

.fabrikListFilterCheckbox {
	text-align: left;
}

.fabrikDateListFilterRange {
	text-align: left;
	display: inline-block;
}
";?>
