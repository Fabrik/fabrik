<?php
/**
 * Fabrik Form View Template CSS
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

header('Content-type: text/css');
$c = (int) $_REQUEST['c'];
$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : 'form';
echo "

#{$view}_$c fieldset ul{
	list-style:none;
}

#{$view}_$c .fabrikElement{
	margin-left:112px;
}

#{$view}_$c .fabrikLabel{
	width:100px;
	clear:left;
	float:left;
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