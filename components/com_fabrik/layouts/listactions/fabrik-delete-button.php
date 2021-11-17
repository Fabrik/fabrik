<?php
/**
 * Layout: list row buttons - rendered as a Bootstrap dropdown
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
$d = $displayData;

?>
<a href="#" class="<?php echo $d->btnClass;?>delete" data-listRef="list_<?php echo $d->renderContext;?>"
	title="<?php echo FText::_('COM_FABRIK_DELETE'); ?>">
	<?php echo FabrikHelperHTML::image($d->list_delete_icon, 'list', $d->tpl, array('alt' => $d->label))?> <?php echo $d->text;?></a>