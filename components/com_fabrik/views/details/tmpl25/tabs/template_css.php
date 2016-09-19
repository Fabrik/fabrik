<?php
/**
 * Tabs Form Template: CSS
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

#{$form} legend{
	display:none;
}

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

#{$form} legend span{
	padding:5px;
	display:block;
}

#{$form}{
	width:100%;
}

#main #{$form} h1{
	paddiing-left:10px;
	margin:0;
}

#{$form} div.fabrikGroup{
	margin:5px 10px;
	position:relative;
	padding:0;
	border:1px solid #DDDDDD;
	border-radius:3px;
}


#{$form} div.fabrikGroup ul,
#details_$c div.fabrikGroup ul{
	padding:0;
	list-style:none;
	margin:0;
}

#{$form} div.fabrikGroup > ul,
#details_$c div.fabrikGroup > ul{
	padding:40px 10px 20px 10px;
}

#{$form} div.fabrikGroup > ul,
#details_$c div.fabrikGroup > ul{
	padding:40px 10px 20px 10px;
}

#{$form} .fabrikForm .fabrikGroup ul{
	list-style:none;
}

#details_$c .fabrikGalleryImage{
	border:1px solid #ccc;
	margin:5px;
	padding:5px;
}

/** link sub elements commented **/

.fabrikSubElementContainer > div {
	clear: left;
}

/** But dbjoin as radios etc can be floated **/
.fabrikSubElementContainer.databasejoin > div {
	clear: none;
}

/* START: align google map sub elements vertically */

.googlemap .fabrikSubElementContainer{
	-moz-box-orient:vertical;
	-webkit-box-orient:vertical;
	box-orient:vertical;
}

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

.floating-tip {
	background-color: #fff;
}

#{$form} .linkedTables{
	margin:0.6em 0;
}

#{$form}  .related_data_norecords{
	display:inline;
}

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

#{$form} .repeatGroupTable tr {
	float: none;
	width:100%;
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

#{$form} .fabrikActions input{
	margin-right:7px;
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
	border-radius:3px;
}

#{$form} .fabrikSubGroup{
	clear:both;
	margin-top:40px;
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

/** autocomplete container inject in doc body not in #forn_$c */
.auto-complete-container{
	overflow-y: hidden;
	border:1px solid #ddd;
	z-index:100;
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
	color: #009FBF;
	background: #DFFDFF url(images/alert.png) no-repeat center left !important;
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

#{$form} .fabrikGroupRepeater {
	padding-top: 50px;
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

/* tabs */
#{$form} dl.tabs {
    float: left;
    margin: 10px 0 -1px 0;
    z-index: 50;
}

#{$form} dl.tabs dt {
    float: left;
    padding: 4px 10px;
    border-left: 1px solid #ccc;
    border-right: 1px solid #ccc;
    border-top: 1px solid #ccc;
    border-bottom: 1px solid #ccc;
    margin-left: 3px;
    background: #f0f0f0;
    color: #666;
}

#{$form} dl.tabs dt.open {
    background: #F9F9F9;
    border-bottom: 1px solid #F9F9F9;
    z-index: 100;
    color: #000;
}

#{$form} div.current {
    clear: both;
    border: 1px solid #ccc;
    padding: 10px 10px;
    border-radius:3px;
}

#{$form} div.current dd {
    padding: 0;
    margin: 0;
}

#{$form} dd {
    border: 1px solid transparent !important;
}
#{$form} dt {
border-radius:3px;
}

#{$form} dd ul{
	list-style:none;
}

#{$form} .fabrikLabel{
	display:block;
	width:100px;
}

#{$form} .addGroup:link {
	text-decoration: none;
}
";?>
