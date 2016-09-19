<?php
/**
 * Fabrik List Template: F3 CSS
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

header('Content-type: text/css');
$c = (int) $_REQUEST['c'];
echo "
#listform_$c{
	border-radius: 7px;
	-moz-border-radius: 7px;
	position:relative;
	background-color: #e1e1e1;
	padding: 19px;
}

#listform_$c .fabrikHeader {
	font-size: 0.9em;
	-moz-box-shadow: 0 1px 0 rgba(0, 0, 0, 0.2);
	background: -moz-linear-gradient(-90deg, #F3F3F3, #C1C3C5, #9B9EA0)
	repeat scroll 0 0 transparent;
	background-image: -ms-linear-gradient(top, #F3F3F3, #C1C3C5, #9B9EA0);
	padding:10px;

	border-radius: 7px 7px 0 0;
	-moz-border-radius: 7px 7px 0 0;
	border:1px solid #999;
}


#listform_$c .fabrik___headings {

	background:	-moz-linear-gradient(-90deg, #e1e1e1, #f1f1f1, #ffffff) repeat scroll 0 0 transparent;
	background-image: -ms-linear-gradient(top, #e1e1e1, #f1f1f1, #ffffff);
	border-bottom:1px solid #a6a6a6;
}

#listform_$c .fabrik___heading.filters > div{
	padding-top:6px;
	float:left'
	padding-bottom:6px;

}

#listform_$c .oddRow1{
	background-color:#f2f5f9;
}

#listform_$c .oddRow0{
	background-color:#ffffff;
}

#listform_$c span.decimal,
#listform_$c span.integer{
	text-align:right;
}

#listform_$c .f3main {
	width: 100%;
	display:box;
	display: -moz-box;
	position:relative;
	background: none repeat scroll 0 0 #FFFFFF;
}

#listform_$c .f3main ul,
#listform_$c ul.list {
	list-style: none;
}

#listform_$c .scroll-x {
	overflow-x: scroll;
	overflow-y: hidden;
	width: 100%;
	border-left: 1px solid rgb(63.1%, 63.9%, 65.1%);
	border-right: 1px solid rgb(63.1%, 63.9%, 65.1%);
}

#listform_$c .scroll-y {
	height: 400px;
	/* scroll now implemented via js as otherwise in
	large width tables you have to scroll to right to see scroll bar*/
	overflow:hidden;
	width: auto;
}

#listform_$c .fabrik_ordercell {
	border-color: #C8C8C8;
	color: #000000;
	font-weight: bold;
}

#listform_$c .fabrik_element,.fabrik_row span {
	padding: 0 5px;
	min-width: 13px;
}

#listform_$c .fabrikList li:nth-child(even) {
	background: none repeat scroll 0 0 #FFF;
}

#listform_$c .fabrikList li:nth-child(odd) {
	background: none repeat scroll 0 0 #F4F7FB;
}

#listform_$c h1 {
	border: 0 !important;
}

#listform_$c .fabrikHeader {
	min-height: 45px;
	padding: 10px;
}

#listform_$c .desc,
#listform_$c .asc {
	background: -moz-linear-gradient(center bottom, #FFFFFF 0%, #EFEFEF 5%, #B4B5B6 100%
		) repeat scroll 0 0 transparent; /*

		-moz-box-shadow: 0 1px 1px rgba(0, 0, 0, 0.25) inset, 0 -1px 1px
		rgba(0, 0, 0, 0.1) inset, 0 1px 0 #DDDDDD;

		*/
	-moz-box-shadow: 0 1px 0px 0px rgba(0, 0, 0, 0.25) inset, 0 4px 0px
		rgba(0, 0, 0, 0.1) inset, 0 1px 0 #DDDDDD;
}

#listform_$c .fabrikHeader .fabrik_filter,
#listform_$c .fabrikFooter .inputbox
	{
	-moz-border-radius: 14px 14px 14px 14px;
	-moz-box-shadow: 0 1px 1px rgba(0, 0, 0, 0.25) inset, 0 -1px 1px
		rgba(0, 0, 0, 0.1) inset, 0 1px 0 #DDDDDD;
	border: 1px solid #5E5E5E;
	color: #888888;
	padding: 1px 9px;
	margin-left: 5px;
}

#listform_$c .inputbox {
	background-color: #DDDDDD;
}

#listform_$c .inputbox:hover,
#listform_$c .inputbox:focus {
	background-color: #fff;
}

#listform_$c .searchall {
	position: absolute;
	right: 55px;
	top: 33px;
}

#listform_$c #search-mode-advanced {
	font-size: 0.9em;
	position: absolute;
	right: 6px;
	top: 19px;
}

#listform_$c .submitfilter {
	position: absolute;
	right: 37px;
	top: 35px;
	z-index: 1;
}

#listform_$c .fabrikHeader a {
	color: #333;
}

#listform_$c .addbutton,
#listform_$c .csvExportButton,
#listform_$c .csvImportButton,
#listform_$c .button
{
	background: -moz-linear-gradient(center top , #FFFFFF 0%, #EFEFEF 5%, #B4B5B6 100%) repeat scroll 0 0 transparent;
	border: 1px solid #888888;
	border-radius: 3px 3px 3px 3px;
	box-shadow: 0 1px 1px rgba(100, 100, 100, 0.2) inset, 0 -1px 1px rgba(0, 0, 0, 0.05) inset, 0 1px 0 #DDDDDD;
	color: #777777;
	display: inline-block;
	float: none;
	margin-right: 5px;
	padding-bottom: 3px;
	padding-top: 3px;
	position: relative;
}

#listform_$c svg {
	position: absolute;
	left: 3px;
	top: 3px;
}

#listform_$c .fabrikHeader a {
	padding-left: 24px;
	padding-right: 7px;
	text-decoration: none;
}

ul.fabrik___heading,
ul.fabrikList{
	margin:0;
	padding:0;
	list-style:none;
	color:#1f1f1f;
}

#listform_$c span.fabrik_element {
	display:table-cell;
	border-right: 1px solid #DDDDDD;
	padding-left:8px;
}

#listform_$c .list {
	padding: 0;
	margin: 0;
	display: table;
}

#listform_$c .list li.heading {
	-moz-box-shadow: 0 1px 0 rgba(0, 0, 0, 0.2);
	background-image: -moz-linear-gradient(-90deg, #F3F3F3, #C1C3C5, #9B9EA0)
		repeat scroll 0 0 transparent;

);
}

#listform_$c .fabrikFooter {
	color:#fff;
	padding: 7px 10px;
	font-size: 0.9em;
	position: relative;
	border-radius: 0 0 7px 7px;
	-moz-border-radius: 0 0 7px 7px;
	background: -moz-linear-gradient(-90deg, #DCDDDD 1px, #BDC0C2 1px, #8B8D8C 100%)
		repeat scroll 0 0 transparent;
	border:1px solid #999;
}

#listform_$c .fabrikFooter .fabrikNav .list-footer{
  /* flexbox, por favor */
  display: -moz-box;
  -moz-box-orient: horizontal;
  -moz-box-pack: center;
  -moz-box-align: center;
	width:100%;

}
listform_$c .fabrikFooter .fabrikNav .list-footer .pagination{
	-moz-box-flex: 0;
}


#listform_$c .fabrikFooter .pagination li {
	-moz-box-shadow: 0 1px 0 #B6B6B6;
	background: -moz-linear-gradient(center top, #FFFFFF 0%, #EFEFEF 5%, #B4B5B6 100%)
		repeat scroll 0 0 transparent;
	border-color: #565759;
	border-style: solid;
	border-width: 1px 0 1px 0;
	color: #000000;
	display: block;
	float: left;
	height: 17px;
	line-height: 17px;
	padding: 3px 4px;
	text-decoration: none;
	vertical-align: middle;
	margin: 0;
	color: #666;
}

#listform_$c .fabrikFooter .pagination {
	border: 0;
	padding: 0;
	margin: 0;
}

#listform_$c .fabrikFooter .counter,.fabrikFooter .limit
	{
	text-align: center;
	text-shadow: 0 1px 0 #C4C5C6;
	clear: both;
}

#listform_$c .fabrikFooter .counter {
	padding-left: 10px;
}

#listform_$c .fabrikFooter .limit {
	width: 145px;
}

#listform_$c .fabrikFooter .pagination li a {
	color: #333;
	text-decoration: none;
}

#listform_$c .fabrikFooter .pagination li.pagination-end,
#listform_$c .fabrikFooter .pagination li.pagination-prev,
#listform_$c .fabrikFooter .pagination li.pagination-next,
#listform_$c .fabrikFooter .pagination li.pagination-start
{
	border-style: solid;
	border-width: 1px 0 1px 0;
}

#listform_$c .fabrikFooter .pagination li.pagination-end {
	border-right-width: 1px;
	margin-right: 0;
	-moz-border-radius-bottomright: 3px;
	-moz-border-radius-topright: 3px;
}

#listform_$c .fabrikFooter .pagination li.pagination-start {
	border-left-width: 1px;
	margin-left: 0;
	padding: 3px 4px;
	-moz-border-radius-bottomleft: 3px;
	-moz-border-radius-topleft: 3px;
}

#listform_$c .fabrikButtons {
	padding-top: 7px;
	position: absolute;
	right: 5px;
	text-align: right;
	top: 0;
}

#listform_$c .fabrikButtons input {
	font-size: 0.9em;
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
/*****************************************************/
/********** default action formatting ****************/
/*****************************************************/

#listform_$c .fabrik_row ul.fabrik_action{
	list-style:none !important;
	border:1px solid #999;
	padding:0;
	text-align:left;
	font-weight: bold;
	font-size: 11px;
	-moz-border-radius: 6px;
	-webkit-border-radius: 6px;
	border-radius: 6px;
	height:23px;
	margin:5px;
}

#listform_$c .fabrik_row ul.fabrik_action span{
	display:none;
}

#listform_$c .fabrik_row .fabrik_action li{
float:left;
	filter: progid : DXImageTransform.Microsoft.gradient ( startColorstr =
		'#cccccc', endColorstr = '#666666' ); /* for IE */
	background: -webkit-gradient(linear, left top, left bottom, from(#ccc),
		to(#666) ); /* for webkit browsers */
	background: -moz-linear-gradient(top, #eee, #ccc);
	padding:2px 3px 0 3px;
	border-left:1px solid #999;
	height:21px;
}

#listform_$c .fabrik_row .fabrik_action li:first-child{
-moz-border-radius: 6px 0 0 6px;
	-webkit-border-radius: 6px 0 0 6px;
	border-radius: 6px 0 0 6px;
	border:0;
}

#listform_$c .fabrik_row .fabrik_action li:last-child{
-moz-border-radius: 0 6px 6px 0;
	-webkit-border-radius: 0 6px 6px 0;
	border-radius: 0 6px 6px 0;
}

";?>