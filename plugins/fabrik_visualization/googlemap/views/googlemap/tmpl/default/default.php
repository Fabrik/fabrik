<?php
/**
 * Default Google Map Viz Template
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$row = $this->row;
$params = $this->params;
$width = $params->get('fb_gm_mapwidth', '');
if ($width !== '') :
	$width = 'width:' . $width . 'px;';
endif;
?>
<div id="<?php echo $this->containerId;?>" class="fabrikGoogleMap fabrik_visualization">
	<?php if ($this->params->get('show-title', 1)) : ?>
		<h1><?php echo $row->label;?></h1>
	<?php endif;
	echo $this->loadTemplate('filter_horiz'); ?>
	<div><?php echo $row->intro_text;?></div>
	<table style="width:100%">
		<tr>
		<?php if ($this->sidebarPosition == '1') :
			echo $this->loadTemplate('sidebar');
		endif; ?>
		<td>
			<div id="table_map" style="<?php echo $width;?>height:<?php echo $params->get('fb_gm_mapheight');?>px"></div>
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
