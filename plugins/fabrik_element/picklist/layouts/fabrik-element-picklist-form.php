<?php

defined('JPATH_BASE') or die;

$d = $displayData;
?>
<div class="<?php echo $d->name; ?>_container" id="<?php echo $d->id; ?>_container">
	<div class="row">
		<div class="span6 <?php echo $d->errorCSS; ?>">

			<?php echo FText::_('PLG_FABRIK_PICKLIST_FROM'); ?>:
            <?php if ($d->scrollfrom->enabled == 1 && count($d->from >= $d->scrollfrom->maxitems)) { ?>
                <style>div.scroll {height: <?php echo $d->scrollfrom->height; ?>px; overflow: auto; border: 1px solid #666; background-color: #fff; }
                ul#<?php echo $d->id; ?>_fromlist {margin-left: 0px; margin-bottom: 0px;}</style>
                <div class="scroll" >
            <?php
            }
            ?>
			<ul id="<?php echo $d->id; ?>_fromlist" class="picklist well well-small fromList">

				<?php
				foreach ($d->from as $value => $label) :
					?>
					<li id="<?php echo $d->id; ?>_value_<?php echo $value;?>" class="picklist">
						<?php echo $label;?>
					</li>
				<?php
				endforeach;
				?>

				<li class="emptypicklist" style="display:none"><?php echo FabrikHelperHTML::icon('icon-move'); ?>
					<?php echo FText::_('PLG_ELEMENT_PICKLIST_DRAG_OPTIONS_HERE'); ?>
				</li>
			</ul>
            <?php if ($d->scrollfrom->enabled == 1 && count($d->from >= $d->scrollfrom->maxitems)) { ?>
            </div>
            <?php } ?>
		</div>
		<div class="span6">
			<?php echo FText::_('PLG_FABRIK_PICKLIST_TO'); ?>:
            <?php if ($d->scrollto->enabled == 1 && count($d->to) >= $d->scrollto->maxitems) { ?>
                <style>div.scroll {height: <?php echo $d->scrollto->height; ?>px; overflow: auto; border: 1px solid #666; background-color: #fff; }
                ul#<?php echo $d->id; ?>_tolist {margin-left: 0px; margin-bottom: 0px;}</style>
                <div class="scroll" >
            <?php
            }
            ?>
			<ul id="<?php echo $d->id; ?>_tolist" class="picklist well well-small toList">

				<?php
				foreach ($d->to as $value => $label) :
					?>
					<li id="<?php echo $d->id; ?>_value_<?php echo $value;?>" class="<?php echo $value;?>">
						<?php echo $label;?>
					</li>
				<?php
				endforeach;
				?>

				<li class="emptypicklist" style="display:none"><?php echo FabrikHelperHTML::icon('icon-move'); ?>
					<?php echo FText::_('PLG_ELEMENT_PICKLIST_DRAG_OPTIONS_HERE'); ?>
				</li>
			</ul>
            <?php if ($d->scrollto->enabled == 1 && count($d->to) >= $d->scrollto->maxitems) { ?>
            </div>
            <?php } ?>
		</div>
	</div>
	<input type="hidden" name="<?php echo $d->name; ?>" value="<?php echo htmlspecialchars($d->value, ENT_QUOTES); ?>" id="<?php echo $d->id; ?>" />
	<?php echo $d->addOptionsUi; ?>
</div>