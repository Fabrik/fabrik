<?php
/**
 * Fabrik List Template: Default Custom CSS
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 * If you need to make small adjustments or additions to the CSS for a fullcalendar
 * template, you can create a custom_css.php file, which will be loaded after
 * the main template_css.php for the template.
 *
 * This file will be invoked as a PHP file, so the viz ID and Javascript container ID
 * can be used in order to narrow the scope of any style changes.  You do
 * this by prepending #listform_$c to any selectors you use.
 *
 * Don't edit anything outside of the BEGIN and END comments.
 *
 * For more on custom CSS, see the Wiki at:
 *
 * http://www.fabrikar.com/forums/index.php?wiki/form-and-details-templates/#the-custom-css-file
 *
 */

header('Content-type: text/css');
$c = $_REQUEST['c'];
$id = $_REQUEST['id'];
echo <<<EOT
/* BEGIN - Your CSS styling starts here */

/* END - Your CSS styling ends here */
EOT;
