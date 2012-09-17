<?php
/**
 * F3 Form Template: CSS
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       3.0
 */
 ?>
<?php
header('Content-type: text/css');
$c = (int) $_REQUEST['c'];
$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : 'form';
echo "

#{$view}_$c fieldset ul{
	list-style:none;
}

#{$view}_$c {
	border-radius: 40px;
	background: none repeat scroll 0 0 rgba(0, 0, 0, 0.1);
	border-bottom: 1px solid rgba(0, 0, 0, 0.07);
	box-shadow: 0 1px 0 rgba(0, 0, 0, 0.15) inset, 0 1px 4px rgba(0, 0, 0, 0.1) inset, 0 1px 0 rgba(255, 255, 255, 0.05);
	color: #CCCCCC;
	text-shadow: 0 1px 0 rgba(0, 0, 0, 0.5);
	padding: 19px;
	position: relative;
}

#{$view}_$c fieldset{
	border:1px solid;
	border-radius: 10px;
	padding-bottom:20px;
}

#{$view}_$c h1{
	font-family:\"UbuntuRegular\";
	font-size: 2em;
	font-weight: bold;
}

#{$view}_$c legend{
	background: -moz-linear-gradient(center top , #1C1C1C, transparent) repeat scroll 0 0 transparent;
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

#{$view}_$c ul{
	list-style:none;
	padding:27px 20px;
}
.floating-tip {
	background-color: black;
}

#{$view}_$c  .linkedTables{
	margin:0.6em 0;
}

#{$view}_$c  .related_data_norecords{
	display:inline;
}

#{$view}_$c .fabrikForm .fabrikGroup ul,
#{$view}_$c .fabrikForm .fabrikGroup li{
	padding:0;
	margin:0;
}


#{$view}_$c .fabrikForm .fabrikGroup ul li.fabrikElementContainer,
#{$view}_$c li.fabrikElementContainer{
	padding:5px 10px;
	margin-top:10px;
	background:none !important;
}

#{$view}_$c .fabrikActions{
	padding-top:15px;
	clear:left;
	padding-bottom:15px;
}


#{$view}_$c .fabrikElement{
	clear:left;
}

#{$view}_$c .fabrikLabel{
float:left;
	padding-bottom:5px;
	min-height:1px; /*for elements with no label txt*/

	/*ensures label text doesnt overrun validation icons*/
	padding-right:10px;
	z-index:99999;
}

#{$view}_$c .fabrikValidating{
	color: #476767;
	background: #EFFFFF;
}

#{$view}_$c .fabrikSuccess{
	color: #598F5B;
	background: #DFFFE0;
}

/*** slide out add option
section for dropdowns radio buttons etc**/

#{$view}_$c .addoption dl{
	display:inline;
	width:75%;
}
#{$view}_$c .addoption{
	clear:left;
	padding:8px;
	margin:3px 0;
	background-color:#efefef;
}

#{$view}_$c  a.toggle-addoption, a.toggle-selectoption{
	padding:0 0 0 10px;
}

/*** end slide out add option section **/


#{$view}_$c  .inputbox:focus{
	background-color:#ffffcc;
}

#{$view}_$c .addoption dd, .addoption dt{
	padding:2px;
	display:inline;
}

#{$view}_$c .fabrikSubGroup{
	clear:both;
}

#{$view}_$c .fabrikSubGroupElements{
	width:80%;
	float:left;
}

#{$view}_$c .geo{
	visibility:hidden;
}



#{$view}_$c .fabrikGroup .readonly,
#{$view}_$c .fabrikGroup .disabled{
	background-color:#DFDFDF !important;
	color:#8F8F8F;
}

/*** fileupload folder select css **/
#{$view}_$c ul.folderselect{
	border:1px dotted #eee;
	background-color:#efefef;
	color:#333;
}

#{$view}_$c .folderselect-container{
	border:1px dotted #666;width:350px;
}

#{$view}_$c .fabrikForm .breadcrumbs{
	background: transparent url(../images/folder_open.png) no-repeat center left;
	padding:2px 2px 2px 26px ;
}

#{$view}_$c .fabrikForm .fabrikGroup li.fileupload_folder{
	background: transparent url(../images/folder.png) no-repeat center left;
	padding:2px 2px 2px 26px ;
	margin:2px;
}

#{$view}_$c .fabrik_characters_left{
clear:left;
}

/** bump calendar above mocha window in mootools 1.2**/
#{$view}_$c div.calendar{
	z-index:115 !important;
}

/** special case for 'display' element with 'show label: no' option **/
#{$view}_$c .fabrikPluginElementDisplayLabel {
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

#{$view}_$c .addbutton {
	background: transparent url(images/add.png) no-repeat left;
	padding: 2px 5px 0 20px;
	margin-left:7px;
}

#{$view}_$c .fabrikError,#{$view}_$c .fabrikNotice,#{$view}_$c .fabrikValidating,#{$view}_$c .fabrikSuccess{
	margin: 0;
	font-weight: bold;
	margin-bottom: 10px;
	padding:7px;
}

#{$view}_$c .fabrikMainError{
	height:2em;
	line-height:2em;
}

#{$view}_$c .fabrikMainError img{
	padding:0.35em 1em;
	float:left;
}

#{$view}_$c .fabrikNotice{
	color: #009FBF;
	background: #DFFDFF url(images/alert.png) no-repeat center left !important;
}

#{$view}_$c .fabrikError,
#{$view}_$c .fabrikGroup li.fabrikError{
	color: #c00;
	background: #EFE7B8;
}

/** for checkboxes etc with multiple columns, the error was too squashed */
#{$view}_$c .fabrikError .fabrikSubElementContainer{
	margin-top: 20px;
}

#{$view}_$c .fabrikErrorMessage{
	padding-right: 5px;
}

#{$view}_$c .fabrikActions {
	padding-top: 15px;
	clear: left;
	padding-bottom: 15px;
}

#{$view}_$c .fabrikGroupRepeater {
	float: left;
	width: 19%;
}

/** used by password element */
#{$view}_$c .fabrikSubLabel {
	margin-left: -10px;
	clear: left;
	margin-top: 10px;
	float: left;
}

#{$view}_$c .fabrikSubElement {
	display: block;
	margin-top: 10px;
}

#{$view}_$c .addGroup:link {
	text-decoration: none;
}
";
?>