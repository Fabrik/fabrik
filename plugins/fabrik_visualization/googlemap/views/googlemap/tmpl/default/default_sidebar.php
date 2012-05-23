<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
if ($this->showSidebar) {
?>
	<td>
	<div id="table_map_sidebar" class="fabrik_calendar_sidebar" style="height:<?php echo $this->params->get('fb_gm_mapheight');?>px;">
		<ul id="table_map_sidebar_overlays">
		<?php
		foreach ($this->overlayUrls as $ovk => $url) {
			if (trim($url) !== '') {
			?>
			<li> <input type="checkbox" id="overlay_chbox_<?php echo $ovk;?>" class="fabrik_calendar_overlay_chbox" checked="" /><?php echo $this->overlayLabels[$ovk];?>
			<?php
			}
		}
		?>
		</ul>

		<div class="grouped_sidebar">

</div>


	</div>
	</td>
<?php
}
?>