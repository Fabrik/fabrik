<?php
/**
 * Default Form Template: CSS
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

/*Here is the styling for your table legend - to learn what all the different elements are in a basic form see http://www.w3schools.com/tags/tag_legend.asp*/
#{$form} legend,
#{$form} h3.legend{
	background-color: #c0c0c0;
	-moz-user-select: none;
	border-bottom: 1px solid #B7B7B7;
	/* border-top: 1px solid #B7B7B7; */
	color: #777777;
	font-weight: bold;
	margin: 0;
	padding:0;
	text-shadow: 0 1px 0 #FFFFFF;
	zoom: 1;
	width:100%;
	background: -moz-linear-gradient(center top , #F3F3F3, #D7D7D7) repeat scroll 0 0 #E7E7E7;
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#D7D7D7', endColorstr='#F3F3F3'); /* for IE */
	background: -webkit-gradient(linear, left top, left bottom, from(#F3F3F3),
		to(#D7D7D7) );
	background-image: -ms-linear-gradient(top, #F3F3F3, #D7D7D7);
		position:absolute;
	font-size: 100%;
}

/*Here is the styling for your group intro*/
#{$form} .groupintro{
	margin-top:40px;
	padding:0 20px;
	color:#666;
}

/*Here is the styling for your group outro*/
#{$form} .groupoutro{
	padding:10px 20px 10px 20px;
	color:#666;
}

#{$form} legend span,
#{$form} h3.legend span{
	padding:5px;
	display:block;
}

/*This controls the background color and the outer border of your form*/
#{$form}{
	width:100%;
	background-color:#FAFAFA;
	border:1px solid #DDDDDD;
}

/*This controls the padding of the title of your form*/
#main #{$form} h1{
	padding-left:10px;
	margin:0;
}

/*This controls the margin and border of your form area ie the div - note: if you leave the margin as is, this could be said to control the 'inner border'*/

#{$form} .fabrikGroup{
	margin:5px 10px;
	position:relative;
	padding:0;
	border:1px solid #DDDDDD;
}

/* Needed in detail view */
#details_$c .fabrikGroup{
	clear: both;
}

/*This controls the display of your form elements - note: ul stands for 'unordered list', see: http://www.w3schools.com/tags/tag_ul.asp*/
#{$form} div ul{
	list-style:none;
	margin:0;
}

/*Note that the order of pixel specifications here follows this rule - top, right, bottom, left*/
/* Styling for main element list in form view */
#form_$c div > ul{
	padding:40px 10px 20px 10px;
}

/* Styling for the main element list in detail view*/
#details_$c .fabrikGroup > ul{
	padding:40px 10px 20px 10px;
}

/*This controls the styling of the form and group - this is a bit vague, needs clarification*/
#{$form} .fabrikForm .fabrikGroup ul{
	list-style:none;
}

/*This controls the styling of your gallery - needs clarification*/
#details_$c .fabrikGalleryImage{
	border:1px solid #ccc;
	margin:5px;
	padding:5px;
}

/*This controls styling of your google maps elements - needs clarification*/
/* START: align google map sub elements vertically */

.googlemap .fabrikSubElementContainer{
	-moz-box-orient:vertical;
	-webkit-box-orient:vertical;
	box-orient:vertical;
}

/*This controls styling of your google maps elements - needs clarification*/
.googlemap .fabrikSubElementContainer > div{
	-mox-box-flex: 1;
	-webkit-box-flex: 1;
	box-flex: 1;
}

/* END: align google map sub elements vertically */
/* START : label spacing for chxbox, radios */

#{$form} label span{
	padding:0 4px;
}

/* END : label spacing for chxbox, radios */

/*This controls the styling of the tips box that is associated with any of your elements*/
.floating-tip {
	background-color: #fff;
}

/*This controls the styling of your linked tables - needs clarification*/
#{$form} .linkedTables{
	margin:0.6em 0;
}

/*This controls the styling of your related data - needs clarification*/
#{$form}  .related_data_norecords{
	display:inline;
}

/*This controls ???? - needs clarification*/
#{$form} .fabrikForm .fabrikGroup ul .fabrikElementContainer,
#details_$c .fabrikElementContainer,
#{$form} .fabrikElementContainer{
	padding:5px 10px;
	margin-top:10px;
	background:none !important;
	display:-webkit-box;
	display:-moz-box;
	display:box;
	overflow:visible;
	width:50%;
}

#{$form} table.repeatGroupTable {
	width: 100%;
}

/** Repeat group rendered as a table **/
#{$form} .repeatGroupTable .fabrikElementContainer {
	display:table-cell;
	width: auto;
	padding: 5px;
	margin: 0;
}

#{$form} .repeatGroupTable .fabrikElement {
    margin: 0;
}

#details_$c .fabrikErrorMessage {
    display: none;
}

#{$form} ul.fabrikRepeatData {
	margin: 0;
}

#details_$c .oddRow0 {
	background-color: #FAFAFA;
}

#details_$c .oddRow1,
	background-color: #Efefef;
}


#details_$c .fabrikSubGroup {
    margin-top: 10px;
}

/*This controls the styling of the buttons area at the bottom of your form*/
#{$form} .fabrikActions{
	padding:10px;
	clear:left;
	margin:5px 10px;
	border:1px solid #DDDDDD;
}

/*This controls the spacing between the buttons at the bottom of your form, for more information on the input tag see http://www.w3schools.com/html/html_forms.asp*/
#{$form} .fabrikActions input{
	margin-right:7px;
}

/*This controls the styling of the form field when being validated by ajax*/
#{$form} .fabrikValidating{
	color: #476767;
	background: #EFFFFF no-repeat right 7px !important;
}

/*This controls the styling of the form field when ajax validation has been successful*/
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

#{$form} input,
#{$form} select,
#{$form} textarea{
	border:1px solid #DDDDDD;
	border-radius:3px;
	padding:3px;
}

#{$form}  .inputbox:focus{
	background-color:#ffffcc;
	border:1px solid #aaaaaa;
}

#{$form} .addoption dd, .addoption dt{
	padding:2px;
	display:inline;
}

#{$form} .fabrikSubGroup{
	clear:both;
	margin-top:40px;
	position: relative;
}

#{$form} .fabrikSubGroupElements{
	width:80%;
	border: 1px dotted #ccc;
}

#{$form} tr.fabrikSubGroup .fabrikErrorMessage {
	padding: 0;
}

#{$form} div.fabrikGroupRepeater{
	position: absolute;
	right: 10px;
	top: 0;
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

/** autocomplete container inject in doc body not in #forn_$c */
.auto-complete-container{
	overflow-y: hidden;
	border:1px solid #ddd;
	z-index:99999;
}

.auto-complete-container ul{
	list-style:none;
	padding:0;
	margin:0;
}

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
#{$form} .leftCol,
#details_$c .leftCol,
#{$form} .fabrikSubLabel{
	width: 130px;
}
#details_$c .leftCol{
	color:#999;
}

#{$form} .fabrikElement {
	margin-left: 10px;
	-webkit-box-flex:1;
	-moz-box-flex:1;
	box-flex:1;
}

#{$form} .addbutton {
	background: transparent url(images/plus-sign.png) no-repeat left;
	padding: 2px 5px 0 20px;
	margin-left:7px;
}

#{$form} .fabrikError,
#{$form} .fabrikNotice,
#{$form} .fabrikValidating,
#{$form} .fabrikSuccess{
	font-weight: bold;
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
	background: url('images/alert.png') no-repeat scroll 10px center #DFFDFF !important;
    color: #009FBF;
    padding: 10px 10px 10px 35px;
}

#{$form} .fabrikError,
#{$form} .fabrikGroup .fabrikError{
	color: #c00;
	background: #EFE7B8;
}

#{$form} .fabrikErrorMessage{
	padding-right: 5px;
}

#{$form} .fabrikLabel {
	min-height:1px; /*for elements with no label txt*/
}

#{$form} .fabrikActions {
	padding-top: 15px;
	clear: left;
	padding-bottom: 15px;
}

/* #{$form} .fabrikGroupRepeater {
	padding-top: 50px;
	float: left;
	width: 19%;
} */

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

/*
some fun with fancy buttons not ready for prime time

#{$form} .button{
	background: -moz-linear-gradient(center top , #ccc 0%, #777) repeat scroll 0 0 transparent;
	background-image: -ms-linear-gradient(top, #ccc, #777);
	border: 1px solid #614337;
	border-radius: 6px 6px 6px 6px;
	box-shadow: 0 1px 2px rgba(0, 0, 0, 0.5), 0 0 2px rgba(255, 255, 255, 0.6) inset;
	color: #FFFFFF;
	margin: 10px;
	padding: 5px 20px;
}

#{$form} .button:hover{
	background: -moz-linear-gradient(center top , #E88801 0%, #C93C00) repeat scroll 0 0 transparent; /* orange */
	background: -moz-linear-gradient(center top , #8EC400 0%, #558A01) repeat scroll 0 0 transparent; /* green */
	background-image: -ms-linear-gradient(top, #8EC400, #558A01);
	text-shadow: 0 -1px 0 #000000, 0 1px 0 rgba(255, 255, 255, 0.2);
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.5), 0 0 1px rgba(255, 255, 255, 0.6) inset;
}

#{$form} .button[name=delete]:hover{
	background: -moz-linear-gradient(center top , #E88801 0%, #C93C00) repeat scroll 0 0 transparent;
	background-image: -ms-linear-gradient(top, #E88801, #C93C00);
}

#{$form} .button[name=Reset]:hover{
	background: -moz-linear-gradient(center top , #E3EB01 0%, #B19F01) repeat scroll 0 0 transparent;
	background-image: -ms-linear-gradient(top, #E88801, #B19F01);
} */
";
?>