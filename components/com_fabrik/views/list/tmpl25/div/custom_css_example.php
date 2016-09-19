<?php
/**
 * Fabrik List Template: Div Custom CSS Example
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

header('Content-type: text/css');
$c = $_REQUEST['c'];
echo "
/*************
  Record style
*/
#listform_$c div.fabrik_row {
	#border:1px solid;
	#width:200px;
	#height:400px;
	#overflow:hidden;
	#padding:10px;
	#margin:10px;
}
#listform_$c .divlabel {font-weight:bold}

/*Hide 'select' checkbox*/
#listform_$c .fabrikList li.fabrik_select{
	#display:none;
}
/*************
  Filter style
*/
#listform_$c .fabrikFilterContainer li.fabrik_row {
	#float:left;
}

";?>