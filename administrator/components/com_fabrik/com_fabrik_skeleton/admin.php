<?php
/**
 * Skeleton Admin Home Page
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$app = JFactory::getApplication();
$input = $app->input;
$option = $input->get('option');

?>
<div style="background:#fff url(components/<?php echo $option; ?>/images/logo.png) no-repeat right bottom;width:100%;height:600px">
	<img src="components/<?php echo $option; ?>/images/builtwith.png" style="padding:50px 0 0 100px">
	<p style="padding:20px 0 0 100px;font-size:1.2em;color:#999195">Create you own Joomla components @ <br />
	<a style="font-size:2em;color:#999195" href="http://fabrikar.com">www.fabrik.com</a></p>
</div>
