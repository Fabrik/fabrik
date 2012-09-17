<?php
/**
 * Contacts Custom Form Template: CSS
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
#{$view}_$c .fabrikElement {
	margin-left: 10px;
}



#{$view}_$c .fabrikLabel {
	width: 100px;
	clear: left;
	float: left;
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

.fabrikSubElement {
	display: block;
	margin-top: 10px;
}

.example {
	#float: left;
	#width: 33%;
	margin-top: 10px;
	padding: 5px 10px;
}

.example .fabrikElement {
	#margin-right: 20px;
	#margin-left: 0px;
	#margin-bottom: 15px;
}

.example .fabrikLabel {
	#float: none;
	#clear: none;
}

#{$view}_$c .addGroup:link {
	text-decoration: none;
}

#{$view}_$c .addGroup:link {
	text-decoration: none;
}
";?>


