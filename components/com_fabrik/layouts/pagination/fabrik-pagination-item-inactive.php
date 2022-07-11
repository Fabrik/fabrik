<?php
/**
 * Layout: List Pagination Inactive Item
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.4.2
 */

$d    = $displayData;
$item = $d->item;
$app  = JFactory::getApplication();

if ($app->isAdmin()) :
	?>
	<span><?php echo $item->text; ?></span>
	<?php
else :
	?>

	<span class="pagenav"><?php echo $item->text; ?></span>
	<?php
endif;
