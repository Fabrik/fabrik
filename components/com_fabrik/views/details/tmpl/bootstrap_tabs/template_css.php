<?php
/**
 * Fabrik Form View Template: Bootstrap Tab CSS
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

header('Content-type: text/css');
$c = (int) $_REQUEST['c'];
$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : 'form';
echo "

.fabrikGroup {
clear: left;
}

/* missing from some bootstrap templates (like JoomlArt) */

.row-fluid:before,
.row-fluid:after {
	display: table;
	content: \"\";
	line-height: 0;
}

.row-fluid:after {
	clear: both;
}
";
?>