<?php
/**
 * Layout: List Pagination Active Item
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.4.2
 */

$d = $displayData;
$item = $d->item;

?>
<a title="<?php echo $item->text; ?>" href="<?php echo $item->link; ?>" class="pagenav"><?php echo $item->text; ?></a>


