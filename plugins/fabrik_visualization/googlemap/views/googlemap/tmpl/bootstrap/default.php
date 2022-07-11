<?php
/**
 * Bootstrap Google Map Viz Template
 *
 * @package      Joomla.Plugin
 * @subpackage   Fabrik.visualization.googlemap
 * @copyright    Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license      GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$row = $this->row;
$params = $this->params;
$width = $params->get('fb_gm_mapwidth', '0');
if (!empty($width) && !strstr($width, '%')) :
	$width .= 'px';
endif;
$width =  $width == '0' ? '' : 'width:' . $width . ';';
?>
<div id="<?php echo $this->containerId;?>" class="fabrikGoogleMap fabrik_visualization">
	<?php if ($this->params->get('show-title', 1)) : ?>
		<h1><?php echo $row->label;?></h1>
	<?php endif;
	echo $this->loadTemplate('filter'); ?>
	<div><?php echo $row->intro_text;?></div>
	<table id="<?php echo $this->containerId . '_sub';?>" style="width:100%">
		<tr>
		<?php if ($this->sidebarPosition == '1') :
			echo $this->loadTemplate('sidebar');
		endif; ?>
		<td>
			<div id="table_map_<?php echo $this->containerId;?>" style="<?php echo $width;?> height:<?php echo $params->get('fb_gm_mapheight');?>px"></div>
		</td>
		<?php if ($this->sidebarPosition == '2') :
			echo $this->loadTemplate('sidebar');
		endif; ?>
		</tr>
	</table>
</div>

<?php foreach ($this->groupTemplates as $table => $templates) :
	foreach ($templates as $label => $content) :
		?>
		<div style="display:none" class="groupedContent groupedContent<?php echo $table . $label?>"><?php echo $content?></div>
		<?php
	endforeach;
endforeach;
