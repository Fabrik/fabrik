<?php
/**
 * Layout: list edit button
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.3.3
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
$d = $displayData;

?>
<a data-loadmethod="<?php echo $d->loadMethod;?>"
	class="<?php echo $d->class;?> btn-default" <?php echo $d->detailsAttributes; ?>
	data-list="<?php echo $d->dataList; ?>"
	data-isajax="<?php echo $d->isAjax; ?>"
	data-rowid="<?php echo $d->rowId; ?>"
	data-iscustom="<?php if ($d->isCustom) echo '1'; else echo '0'; ?>"
	href="<?php echo $d->link; ?>" 
	title="<?php echo $d->viewLabel;?>" 
	target="<?php echo $d->viewLinkTarget; ?>">
<?php echo FabrikHelperHTML::image($d->list_detail_link_icon, 'list', '', array('alt' => $d->viewLabel));?> <?php echo $d->viewText; ?></a>