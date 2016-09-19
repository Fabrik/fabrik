<?php
/**
 * Slideshow vizualization: default template
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.slideshow
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$row = $this->row;
?>
<div id="<?php echo $this->containerId;?>" class="fabrik_visualization">
	<?php if ($this->params->get('show-title', 1)) :?>
	<h1>
		<?php echo $row->label;?>
	</h1>
	<?php endif;?>
	<?php echo $this->loadTemplate('filter'); ?>
	<div>
		<?php echo $row->intro_text;?>
	</div>
	<div class="slideshow" id="slideshow_viz">
		<div class="slideshow-images">
			<a><img /> </a>
			<div class="slideshow-loader"></div>
		</div>
		<div class="slideshow-captions"></div>
		<div class="slideshow-controller"></div>
		<div class="slideshow-thumbnails"></div>
	</div>
</div>
