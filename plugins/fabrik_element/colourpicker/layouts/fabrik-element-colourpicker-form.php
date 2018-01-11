<?php
defined('JPATH_BASE') or die;

$d = $displayData;
?>

<div class="fabrikSubElementContainer">
	<input class="fabrikinput" type="hidden" name="<?php echo $d->name; ?>" id="<?php echo $d->id; ?>" />
	<div class="colourpicker_bgoutput img-rounded " style="border:1px solid #EEEEEE;float:left;width:25px;height:25px;background-color:rgb(<?php echo $d->value; ?>)">
	</div>
<?php
	if ($d->editable) :
        // Add styles to use absolute positioning for swatch color name on mouseover
        $style = '#'.$d->id.'-swatch{position:relative;}#'.$d->id.'-swatch > div > span{position:absolute;top:-17px;left:5px;}';
        $document = JFactory::getDocument();    
        $document->addStyleDeclaration($style);   	
?>
	<div class="colourPickerBackground colourpicker-widget fabrikWindow" style="display:none;min-width:350px;min-height:250px;">
		<div class="draggable modal-header">
			<div class="colourpicker_output img-rounded" style="width:15px;height:15px;float:left;margin-right:10px;"></div> 
			<?php echo FText::_('PLG_FABRIK_COLOURPICKER_COLOUR');?>

			<?php if ($d->j3)
			{?>
			<a class="pull-right" href="#">
				<?php echo FabrikHelperHTML::icon('icon-cancel icon-remove-sign'); ?></a>
			<?php
			}
			else
			{

			echo  FabrikHelperHTML::image("close.gif", 'form', @$this->tmpl, array());
			}

			if ($d->showPicker) :?>
			</div>
		<div class="itemContentPadder">
			<div class="row-fluid">
				  <div class="span7">
					    <ul class="nav nav-tabs">
						      <li class="active"><a href="#<?php echo $d->id; ?>-picker" data-toggle="tab"><?php echo FText::_('PLG_FABRIK_COLOURPICKER_PICKER');?></a></li>
						      <li><a href="#<?php echo $d->id; ?>-swatch" data-toggle="tab"><?php echo FText::_('PLG_FABRIK_COLOURPICKER_SWATCH'); ?></a></li>
						    </ul>
					    <div class="tab-content">
						      <div class="tab-pane active" id="<?php echo $d->id; ?>-picker"></div>
						      <div class="tab-pane" id="<?php echo $d->id; ?>-swatch"></div>
						    </div>
					  </div>
				  <div class="span5 sliders" style="margin-top:50px">
					  </div>
				</div>
			</div>
		<?php
			else :
			?>
		</div><div class="tab-pane" id="<?php echo $d->id; ?>-swatch"></div>
	<?php
		endif;
?>
	</div>
<?php endif;?>

</div>
