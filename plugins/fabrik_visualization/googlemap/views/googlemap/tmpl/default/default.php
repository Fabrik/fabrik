<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
$row = $this->row;
$params = $this->params;
?>
<div id="<?php echo $this->containerId;?>" class="fabrikGoogleMap fabrik_visualization">
	<?php if ($this->params->get('show-title', 1)) {?>
		<h1><?php echo $row->label;?></h1>
	<?php }?>
	<?php echo $this->loadTemplate('filter'); ?>
	<div><?php echo $row->intro_text;?></div>
	<table>
	<tr>
	<?php if ($this->sidebarPosition == '1') {
		echo $this->loadTemplate('sidebar');
	} ?>
	<td>
	<div id="table_map" style="width:<?php echo $params->get('fb_gm_mapwidth');?>px; height:<?php echo $params->get('fb_gm_mapheight');?>px"></div>
	</td>
	<?php if ($this->sidebarPosition == '2') {
		echo $this->loadTemplate('sidebar');
	} ?>
	</tr>
	</table>
</div>

<?php foreach ($this->grouptemplates as $table => $templates) {
	foreach ($templates as $label => $content) {
		?>
		<div style="display:none" class="groupedContent groupedContent<?php echo $table . $label?>"><?php echo $content?></div>
		<?php
	}
} ?>