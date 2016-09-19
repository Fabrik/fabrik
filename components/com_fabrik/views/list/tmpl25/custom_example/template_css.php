<?php
/**
 * Fabrik List Template: Custom Example CSS
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

header('Content-type: text/css');
$c = $_REQUEST['c'];
$buttonCount = (int)$_REQUEST['buttoncount'];
echo "

.list-footer div.limit {
	float: left;
	height: 22px;
	line-height: 22px;
	margin: 0 10px;
}


.downloads___image{
background:url('http://fabrikar.com/templates/fabrik2/images/downloadsbg.jpg') no-repeat scroll -16px top transparent;
}

.fabrikForm h3{
float:left;
}

.fabrikForm h3 a{
	padding-right: 8px;
}

.fabrikForm div.version {
margin-top:8px;
}

.fabrikForm ul{
list-style:none;
margin: 8px 0;
}

.fabrikForm li > .btn{
	margin-top:20px;
}


.fabrik_filter{
width:100%;
}

";?>