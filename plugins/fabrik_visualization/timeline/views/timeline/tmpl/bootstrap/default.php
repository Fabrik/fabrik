<?php
/**
 * Bootstrap timeline viz template
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.timeline
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$row = $this->row;
?>
<div id="<?php echo $this->containerId;?>" class="fabrik_visualization fabrik_timeline">
<?php if ($this->params->get('show-title', 1))
{?>
	<h1><?php echo $row->label;?></h1>
<?php
}
?>
	<div><?php echo $row->intro_text;?></div>
	<?php echo $this->loadTemplate('filter'); ?>
	<div class="datePicker">
	<?php echo FText::_('PLG_VIZ_TIMELINE_JUMP_TO') . ': ' . $this->datePicker; ?>
	</div>
	<div id="my-timeline" style="margin-top:20px;border:1px solid #ccc;width:<?php echo $this->width?>px;height:<?php echo $this->height?>px;"></div>
</div>