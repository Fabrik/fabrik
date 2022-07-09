<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.paginate
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;
$start = FText::_('COM_FABRIK_START');
$next = FText::_('COM_FABRIK_NEXT');
$prev = FText::_('COM_FABRIK_PREV');
$end = FText::_('COM_FABRIK_END');
?>
<div class="pagination form-actions">
	<ul class="pagination-list">
		<li data-paginate="first" class="pagination-start <?php echo $displayData['first-active'] ? 'active' : ''?>">
			<a class="pagenav" href="<?php echo $displayData['first'];?>" title="<?php echo $start; ?>">
				<?php echo $start; ?>
			</a>
		</li>
		<li data-paginate="prev" class="pagination-prev <?php echo $displayData['first-active'] ? 'active' : ''?>">
			<a class="pagenav" href="<?php echo $displayData['prev'];?>" title="<?php echo $prev; ?>">
				<?php echo $prev; ?>
			</a>
		</li>
		<li data-paginate="next" class="pagination-next <?php echo $displayData['last-active'] ? 'active' : ''?>">
			<a class="pagenav" href="<?php echo $displayData['next'];?>" title="<?php echo $next; ?>">
				<?php echo $next; ?>
			</a>
		</li>
		<li data-paginate="last" class="pagination-end <?php echo $displayData['last-active'] ? 'active' : ''?>">
			<a class="pagenav" href="<?php echo $displayData['last'];?>" title="<?php echo $end; ?>">
				<?php echo $end; ?>
			</a>
		</li>
	</ul>
</div>
