<?php
/**
 * Fabrik List Template: Bluesky CSS
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
#listform_$c table.fabrikList {
	clear:right;
	border-collapse: collapse;
	margin-top: 10px;
	color: #444444;
	/* seem to remember an issue with this,
	but it seems nicer to have the table full width
	- then you can right align the top buttons against it */
	width:100%;
}

#listform_$c table.fabrikList .groupdataMsg{
	padding:0;
}

#listform_$c .fabrikDataContainer{
	overflow-y: auto;
	clear: both;
}

#listform_$c a,
#listform_$c .fabrikDataContainer  a:hover{
	border:0;
	background:transparent;
}

#listform_$c .list-footer{
	display:-moz-box;
	display:-webkit-box;
	display:box;
}

#listform_$c td.decimal,
#listform_$c td.integer{
	text-align:right;
}

#listform_$c .list-footer div.limit{
	margin-top:5px;
	margin-left:10px;
}

#listform_$c .list-footer div.counter{
	margin-top:8px;
	margin-left:10px;
}

#listform_$c .list-footer .pagination{
	margin-left:20px;
}

#listform_$c .list-footer .pagination li{
	display: inline;
	margin:0 2px;
	 line-height: 1.7em;
}

#listform_$c .list-footer .pagination li .pagenav {
    padding: 2px;
}


#listform_$c .fabrik_ordercell a{
	text-decoration:none;
	color:#333;
}

#listform_$c .fabrik_ordercell a:hover{
	color:#333;
}


#listform_$c .fabrikElementContainer{
	/** for inline edit **/
	position:relative;
}

.advancedSearch_$c table {
	border-collapse: collapse;
	margin-top: 10px;
}

#listform_$c table.fabrikList th{
	vertical-align:top;
}

#listform_$c table.fabrikList td,
#listform_$c table.fabrikList th,
.advancedSearch_$c td, .advancedSearch_$c th {
	padding: 5px;
	border: 1px solid #BDD0D5;
}

/** bump calendar above mocha window in mootools 1.2**/
div.calendar{
	z-index:10000 !important;
}

/** autocomplete container inject in doc body not iin #forn_$c */
.auto-complete-container{
	overflow-y: hidden;
	border:1px solid #ddd;
}

.auto-complete-container ul{
	list-style:none;
	background-color:#fff;
	margin:0;
	padding:0;
}

.auto-complete-container li{
	text-align:left;
	padding:2px 10px !important;
	background:#fff;
	margin:0 !important;
	border-top:1px solid #ddd;
	cursor:hand;
}

.auto-complete-container li:hover,
.auto-complete-container li.selected{
	background-color:#DFFAFF !important;
	cursor:pointer;
}

#listform_$c .fabrikForm {
	margin-top: 15px;
}

#listform_$c .fabrik_groupheading,
#listform_$c .fabrik___heading,
.advancedSearch_$c .fabrik___heading{
	background-color: #c0c0c0;
	border-bottom: 1px solid #B7B7B7;
	border-top: 1px solid #FFFFFF;
	color: #325773;
	font-weight: bold;
	min-height: 20px;
	line-height: 19px;
	margin: 0;
	text-shadow: 0 1px 0 #FFFFFF;
  zoom: 1;

}

#listform_$c th,
#listform_$c .fabrik_groupheading,
#listform_$c tfoot td{
	background: -moz-linear-gradient(center top , #DCECF4, #BECED2) repeat scroll 0 0 #E7E7E7;
	background: -webkit-gradient(linear, left top, left bottom, from(#DCECF4),
		to(#BECED2) );
	background-image: -ms-linear-gradient(top, #DCECF4, #BECED2);
}

#listform_$c .fabrik_groupheading td{
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#BECED2', endColorstr='#DCECF4'); /* for IE */
}

#listform_$c .fabrik_groupheading a{
	color: #777777;
	text-decoration:none;
}

#listform_$c table.fabrikList tr.fabrik_calculations td {
	border: 0 !important;
}

#listform_$c .oddRow0 {
	background-color: #F6F9FA;
}

#listform_$c .oddRow1,
.advancedSearch_$c .oddRow1 {
	background-color: #EAF5F9;
}


#listform_$c table.fabrikList tr.fabrik_calculations td {
	border: 0 !important;
}

#listform_$c .firstPage,.previousPage,.aPage,.nextPage,.lastPage {
	display: inline;
	padding: 3px;
}

#listform_$c table.filtertable {
	width: 50%;
	float: right;
}

#listform_$c .fabrikHover,
#advancedSearchContainer tr:hover {
	background-color: #ffffff;
}

/** highlight the last row that was clicked */
#listform_$c .fabrikRowClick {
	background-color: #ffffff;
}

/** highlight the loaded row - package only */
#listform_$c .activeRow {
	background-color: #FFFFCC;
}

#listform_$c .emptyDataMessage {
	background-color: #EFE7B8;
	border-color: #EFD859;
	border-width: 2px 0;
	border-style: solid;
	padding: 5px;
	margin: 10px 0;
	font-size: 1em;
	color: #CC0000;
	font-weight: bold;
}

#listform_$c ul.fabrikRepeatData {
	padding: 0;
	margin: 0;
	list-style: none;
}

#listform_$c ul.fabrikRepeatData li {
	background-image: none;
	padding: 0;
	margin: 0;
	min-height: 20px;
}

.advancedSearch_$c {
	padding:10px;
}

/********************************************/
/ ****** start: action buttons **************/
/********************************************/

#listform_$c .fabrik_buttons {
	height:25px;
}

#listform_$c .fabrik_buttons{
	/* remove this if you want the top menu bar to be on the right hand side*/
	float:left !important;
}

#listform_$c ul.fabrik_action {
	list-style:none;
	background:none;
	list-style:none;
	min-height:25px;
	border-radius: 6px;
	float:right;
	margin:5px;
	padding:0;
	border:1px solid #999;
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#DCECF4', endColorstr='#BECED2'); /* for IE */

	background: -webkit-gradient(linear, left top, left bottom, from(#DCECF4),
		to(#BECED2) ); /* for webkit browsers */
	background: -moz-linear-gradient(top, #DCECF4, #BECED2);
	background: -o-linear-gradient(top, #DCECF4 0%, #BECED2 100%);
  background: -ms-linear-gradient(top, #DCECF4 0%, #BECED2 100%);

}

#listform_$c .fabrik_row ul.fabrik_action{
	width:$buttonTotal;
}

/* $$$ hugh - separated pagination from fabrik_action, 'cos float right makes pagination disappear in Chrome! */
#listform_$c ul.pagination {
	list-style:none;
	background:none;
	list-style:none;
	min-height:25px;
	border-radius: 6px;
	/* float:right; */
	margin:5px;
	padding:0;
	border:1px solid #999;
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#DCECF4', endColorstr='#BECED2'); /* for IE */

	background: -webkit-gradient(linear, left top, left bottom, from(#DCECF4),
		to(#BECED2) ); /* for webkit browsers */
	background: -moz-linear-gradient(top, #DCECF4, #BECED2);
	background: -o-linear-gradient(top, #DCECF4 0%, #BECED2 100%);
  background: -ms-linear-gradient(top, #DCECF4 0%, #BECED2 100%);

}

#listform_$c ul.fabrik_action span{
	display:none;
}

#listform_$c .fabrik_action li,
.advancedSearch_$c .fabrik_action li{
	float:left;
	border-left:1px solid #999;
	min-height:17px;
	min-width:25px;
	text-align:center;
	margin:0;
	padding:0;
}


#listform_$c .fabrik_action li:first-child,
.advancedSearch_$c .fabrik_action li:first-child{
	-moz-border-radius: 6px 0 0 6px;
	-webkit-border-radius: 6px 0 0 6px;
	border-radius: 6px 0 0 6px;
	border:0;
}

#listform_$c .fabrik_action li a{
	display:block;
	padding:4px 6px 2px 6px;
}


/********************************************/
/ ****** end: action buttons ****************/
/********************************************/

/********************************************/
/ ****** start: search all   ****************/
/********************************************/

#listform_$c .searchall li{
	line-height:1.1em;
}
#listform_$c .searchall li input,
#listform_$c .searchall li select,
#listform_$c .fabrik___heading button,
#listform_$c .fabrik___heading input,
#listform_$c .fabrik___heading select{
	margin:3px 3px 0 3px;
	border:1px solid #999;
	border-radius:3px;
}

#listform_$c button.fabrik_filter_submit{
height:23px;
margin-top:2px;
}

#listform_$c .searchall li input[type=button]{
	background-image:none;
	padding:0px;
}

.webkit #listform_$c .searchall li input[type=button]{
	padding:1px;
}

/********************************************/
/ ****** end: search all     ****************/
/********************************************/

";?>