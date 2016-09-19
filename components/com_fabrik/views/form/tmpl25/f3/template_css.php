<?php
/**
 * F3 Form Template: CSS
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

#{$form} fieldset ul{
	list-style:none;
}

#{$form} {
	border-radius: 40px;
	background: none repeat scroll 0 0 rgba(0, 0, 0, 0.1);
	border-bottom: 1px solid rgba(0, 0, 0, 0.07);
	box-shadow: 0 1px 0 rgba(0, 0, 0, 0.15) inset, 0 1px 4px rgba(0, 0, 0, 0.1) inset, 0 1px 0 rgba(255, 255, 255, 0.05);
	color: #CCCCCC;
	text-shadow: 0 1px 0 rgba(0, 0, 0, 0.5);
	padding: 19px;
	position: relative;
}

#{$form} fieldset{
	border:1px solid;
	border-radius: 10px;
	padding-bottom:20px;
}

#{$form} h1{
	font-family:\"UbuntuRegular\";
	font-size: 2em;
	font-weight: bold;
}

#{$form} legend{
	background: -moz-linear-gradient(center top , #1C1C1C, transparent) repeat scroll 0 0 transparent;
	background-image: -ms-linear-gradient(top, #1C1C1C, transparent);
	border-radius: 10px 10px 10px 10px;
	display: block;
	font-size: 1.5em;
	height: 30px;
	padding: 15px 10px 10px 27px;
	position: absolute;
	width: 873px;
	font-family:\"UbuntuRegular\";
	font-weight:bold;
}

#{$form} ul{
	list-style:none;
	padding:27px 20px;
}
.floating-tip {
	background-color: black;
}

#{$form}  .linkedTables{
	margin:0.6em 0;
}

#{$form}  .related_data_norecords{
	display:inline;
}

#{$form} .fabrikForm .fabrikGroup ul,
#{$form} .fabrikForm .fabrikGroup li{
	padding:0;
	margin:0;
}


#{$form} .fabrikForm .fabrikGroup ul li.fabrikElementContainer,
#{$form} li.fabrikElementContainer{
	padding:5px 10px;
	margin-top:10px;
	background:none !important;
}

#{$form} .fabrikActions{
	padding-top:15px;
	clear:left;
	padding-bottom:15px;
}


#{$form} .fabrikElement{
	clear:left;
}

#{$form} .fabrikLabel{
float:left;
	padding-bottom:5px;
	min-height:1px; /*for elements with no label txt*/

	/*ensures label text doesnt overrun validation icons*/
	padding-right:10px;
	z-index:99999;
}

#{$form} .fabrikValidating{
	color: #476767;
	background: #EFFFFF;
}

#{$form} .fabrikSuccess{
	color: #598F5B;
	background: #DFFFE0;
}

/*** slide out add option
section for dropdowns radio buttons etc**/

#{$form} .addoption dl{
	display:inline;
	width:75%;
}
#{$form} .addoption{
	clear:left;
	padding:8px;
	margin:3px 0;
	background-color:#efefef;
}

#{$form}  a.toggle-addoption, a.toggle-selectoption{
	padding:0 0 0 10px;
}

/*** end slide out add option section **/


#{$form}  .inputbox:focus{
	background-color:#ffffcc;
}

#{$form} .addoption dd, .addoption dt{
	padding:2px;
	display:inline;
}

#{$form} .fabrikSubGroup{
	clear:both;
}

#{$form} .fabrikSubGroupElements{
	width:80%;
	float:left;
}

#{$form} .geo{
	visibility:hidden;
}



#{$form} .fabrikGroup .readonly,
#{$form} .fabrikGroup .disabled{
	background-color:#DFDFDF !important;
	color:#8F8F8F;
}

/*** fileupload folder select css **/
#{$form} ul.folderselect{
	border:1px dotted #eee;
	background-color:#efefef;
	color:#333;
}

#{$form} .folderselect-container{
	border:1px dotted #666;width:350px;
}

#{$form} .fabrikForm .breadcrumbs{
	background: transparent url(../images/folder_open.png) no-repeat center left;
	padding:2px 2px 2px 26px ;
}

#{$form} .fabrikForm .fabrikGroup li.fileupload_folder{
	background: transparent url(../images/folder.png) no-repeat center left;
	padding:2px 2px 2px 26px ;
	margin:2px;
}

#{$form} .fabrik_characters_left{
clear:left;
}

/** bump calendar above mocha window in mootools 1.2**/
#{$form} div.calendar{
	z-index:115 !important;
}

/** special case for 'display' element with 'show label: no' option **/
#{$form} .fabrikPluginElementDisplayLabel {
	width: 100% !important;
}

/** autocomplete container inject in doc body not iin #forn_$c */
.auto-complete-container{
	overflow-y: hidden;
	border:1px solid #ddd;
	z-index:100;
}

.auto-complete-container ul{list-style:none;}

.auto-complete-container li.unselected{
	padding:2px 10px !important;
	background-color:#fff !important;
	margin:0 !important;
	border-top:1px solid #ddd;
	cursor:pointer;
}

.auto-complete-container li:hover,
.auto-complete-container li.selected{
	background-color:#DFFAFF !important;
	cursor:pointer;
}

#{$form} .addbutton {
	background: transparent url(images/plus-sign.png) no-repeat left;
	padding: 2px 5px 0 20px;
	margin-left:7px;
}

#{$form} .fabrikError,#{$form} .fabrikNotice,#{$form} .fabrikValidating,#{$form} .fabrikSuccess{
	margin: 0;
	font-weight: bold;
	margin-bottom: 10px;
	padding:7px;
}

#{$form} .fabrikMainError{
	height:2em;
	line-height:2em;
}

#{$form} .fabrikMainError img{
	padding:0.35em 1em;
	float:left;
}

#{$form} .fabrikNotice{
	color: #009FBF;
	background: #DFFDFF url(images/alert.png) no-repeat center left !important;
}

#{$form} .fabrikError,
#{$form} .fabrikGroup li.fabrikError{
	color: #c00;
	background: #EFE7B8;
}

/** for checkboxes etc with multiple columns, the error was too squashed */
#{$form} .fabrikError .fabrikSubElementContainer{
	margin-top: 20px;
}

#{$form} .fabrikErrorMessage{
	padding-right: 5px;
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

#{$form} .fabrikSubElement {
	display: block;
	margin-top: 10px;
}

#{$form} .addGroup:link {
	text-decoration: none;
}
";
?>