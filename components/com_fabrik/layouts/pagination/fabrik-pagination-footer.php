<?php
/**
 * Layout: List Pagination Footer
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.3.3
 */

$d = $displayData;

if ($d->showNav) :
	?>
<div class="list-footer">
	<div class="limit">
		<div class="input-prepend input-append">
			<span class="add-on">
				<label for="<?php echo $d->listName;?>">
					<small>
					<?php echo $d->label; ?>
					</small>
				</label>
			</span>
			<?php echo $d->list; ?>
			<span class="add-on">
				<small>
					<?php echo $d->pagesCounter; ?>
				</small>
			</span>
		</div>
	</div>
	<?php echo $d->links; ?>
	<input type="hidden" name="limitstart<?php echo $d->id; ?>" id="limitstart<?php echo $d->id; ?>" value="<?php echo $d->value; ?>" />
</div>
<?php
else :
	if ($d->showTotal) : ?>
<div class="list-footer">
	<span class="add-on">
			<small>
				<?php echo $d->pagesCounter; ?>
			</small>
	</span>
</div>
<?php
	endif;
endif;