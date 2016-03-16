<?php
/**
 * Fabrik Form Template: Bootstrap CSS
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

header('Content-type: text/css');
$c = (int) $_REQUEST['c'];
$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : 'form';
echo "

.fabrikGroup {
clear: left;
}
";
?>

label .fabrikgrid_PayPal_Express {
	background: url(https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg) no-repeat;
	display: block;
	height: 100px;
	width: 200px;
	background-size: contain;
}

label .fabrikgrid_PayPal_Express span {
display: none;
}
