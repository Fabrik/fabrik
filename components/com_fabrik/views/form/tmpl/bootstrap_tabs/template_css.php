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

/* color & highlight group with validation errors */
.fabrikErrorGroup a {
    background-color: rgb(242, 222, 222) !important;
  color: #b94a48;
}
 
.active.fabrikErrorGroup a,
.active.fabrikErrorGroup a:hover,
.active.fabrikErrorGroup a:focus {
    border: 1px solid #b94a48 !important;
    border-bottom-color: transparent !important;
  color: #b94a48 !important;
  background-color: rgb(255, 255, 255) !important;
}
 
.fabrikErrorGroup a:hover,
.fabrikErrorGroup a:focus {
    background-color: rgb(222, 173, 173) !important;
  color: #b94a48;
}

";
?>