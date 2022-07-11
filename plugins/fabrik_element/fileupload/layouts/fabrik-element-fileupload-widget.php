<?php
defined('JPATH_BASE') or die;

$d = $displayData;
$id = $d->id;
$winWidth = $d->winWidth;
$winHeight = $d->winHeight;
$canCrop = $d->canCrop;
$canvasSupport = $d->canvasSupport;
$dropBoxStyle = $d->dropBoxStyle;
$j3 = $d->j3;
$field = $d->field;
?>

<span id="<?php echo $id; ?>"></span>


<div class="plupload_container fabrikHide" id="<?php echo $id; ?>_container" style="<?php echo $dropBoxStyle; ?>">
	<div class="plupload" id="<?php echo $id; ?>_dropList_container">
<?php
if ($j3) :
?>
		<table class="table table-striped table-condensed">
			<thead style="display:none">
				<tr>
					<th class="<?php echo FabrikHelperHTML::getGridSpan('4'); ?>"><?php echo FText::_('PLG_ELEMENT_FILEUPLOAD_FILENAME'); ?></th>
					<th class="<?php echo FabrikHelperHTML::getGridSpan('2'); ?> plupload_crop">&nbsp;</th>
					<th class="<?php echo FabrikHelperHTML::getGridSpan('5'); ?> plupload_file_status"></th>
					<th class="<?php echo FabrikHelperHTML::getGridSpan('1'); ?> plupload_file_action">&nbsp;</th>
				</tr>
			</thead>
			<tbody class="plupload_filelist" id="<?php echo $id; ?>_dropList">
			</tbody>
			<tfoot>
				<tr>
					<td colspan="4">
								<a id="<?php echo $id; ?>_browseButton" class="btn btn-mini" href="#"><?php echo FabrikHelperHTML::icon('icon-plus-sign icon-plus'); ?>
						<?php echo FText::_('PLG_ELEMENT_FILEUPLOAD_ADD_FILES'); ?></a>
							<span class="plupload_upload_status"></span>
					</td>
				</tr>
			</tfoot>
		</table>
<?php
else :
	FabrikHelperHTML::stylesheet(COM_FABRIK_LIVESITE . 'plugins/fabrik_element/fileupload/lib/plupload/css/plupload.queue.css');
?>
		<div class="plupload_header">
			<div class="plupload_header_content">
				<div class="plupload_header_title"><?php echo FText::_('PLG_ELEMENT_FILEUPLOAD_PLUP_HEADING'); ?></div>
				<div class="plupload_header_text"><?php echo FText::_('PLG_ELEMENT_FILEUPLOAD_PLUP_SUB_HEADING'); ?></div>
			</div>
		</div>
		<div class="plupload_content">
			<div class="plupload_filelist_header">
				<div class="plupload_file_name"><?php echo FText::_('PLG_ELEMENT_FILEUPLOAD_FILENAME'); ?></div>
				<div class="plupload_file_action">&nbsp;</div>
				<div class="plupload_file_status"><span><?php echo FText::_('PLG_ELEMENT_FILEUPLOAD_STATUS'); ?></span></div>
				<div class="plupload_file_size"><?php echo FText::_('PLG_ELEMENT_FILEUPLOAD_SIZE'); ?></div>
				<div class="plupload_clearer">&nbsp;</div>
			</div>
			<ul class="plupload_filelist" id="<?php echo $id; ?>_dropList">
			</ul>
			<div class="plupload_filelist_footer">
				<div class="plupload_file_name">
					<div class="plupload_buttons">
						<a id="<?php echo $id; ?>_browseButton" class="plupload_button plupload_add" href="#">'
				<?php echo FText::_('PLG_ELEMENT_FILEUPLOAD_ADD_FILES'); ?></a>
						<a id="<?php echo $id; ?>_startButton" class="plupload_button plupload_start plupload_disabled" href="#">'
				<?php echo FText::_('PLG_ELEMENT_FILEUPLOAD_START_UPLOAD'); ?></a>
					</div>
					<span class="plupload_upload_status"></span>
				</div>
				<div class="plupload_file_action"></div>
				<div class="plupload_file_status">
					<span class="plupload_total_status"></span>
				</div>
				<div class="plupload_file_size">
					<span class="plupload_total_file_size"></span>
				</div>
				<div class="plupload_progress">
					<div class="plupload_progress_container">
					<div class="plupload_progress_bar"></div>
				</div>
			</div>
			<div class="plupload_clearer">&nbsp;</div>
			</div>
		</div>
<?php
endif;
?>
	</div>
	<!-- FALLBACK; SHOULD LOADING OF PLUPLOAD FAIL -->
	<div class="plupload_fallback"><?php echo FText::_('PLG_ELEMENT_FILEUPLOAD_FALLBACK_MESSAGE'); ?>
	<br />
	<?php
	echo $field;
	?>
	</div>
</div>
