<?php
/**
 * Default sidebar tmpl
 *
* @package     Joomla.Plugin
* @subpackage  Fabrik.visualization.googlemap
* @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
* @license     GNU General Public License version 2 or later; see LICENSE.txt
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
if ($this->showSidebar) :
?>
	<td>
		<div id="table_map_sidebar" class="fabrik_calendar_sidebar" style="height:<?php echo $this->params->get('fb_gm_mapheight');?>px;">
			<?php if ($this->showOverLays) : ?>
				<ul id="table_map_sidebar_overlays">
				<?php
				foreach ($this->overlayUrls as $ovk => $url) :
					if (trim($url) !== '') :
					?>
					<li> <input type="checkbox" id="overlay_chbox_<?php echo $ovk;?>" class="fabrik_calendar_overlay_chbox" checked="" /><?php echo $this->overlayLabels[$ovk];?>
					<?php
					endif;
				endforeach;
				?>
				</ul>
			<?php endif; ?>
			<button class="clear-grouped" class="btn button"><?php echo JText::_('COM_FABRIK_CLEAR')?></button>
			<div class="grouped_sidebar">
			</div>
		</div>
	</td>
<?php
endif;
