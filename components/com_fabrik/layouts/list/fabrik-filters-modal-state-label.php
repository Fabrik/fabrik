<?php
/**
 * Layout for modal filter state, per element
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

$d = $displayData;
?>
<span class="label label-inverse">
	<span data-modal-state-label><?php echo $d->label;?></span>:
	<span data-modal-state-value><?php echo $d->displayValue . ' '; ?></span>
	<a data-filter-clear="<?php echo $d->key; ?>" href="#" style="color: white;">
		<?php echo FabrikHelperHTML::icon('icon-cancel', '', 'style="text-align: right; "'); ?>
	</a>
</span>