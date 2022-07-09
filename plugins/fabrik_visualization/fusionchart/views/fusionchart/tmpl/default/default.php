<?php
/**
 * Fusion Chart Viz: default tmpl
 *
 * @package		Joomla.Plugin
 * @subpackage	Fabrik.visualization.fusionchart
 * @copyright	Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$row = $this->row;
?>
<div id="<?php echo $this->containerId;?>" class="fabrik_visualization">
	<?php if ($this->params->get('show-title')) :?>
        <h1>
			<?php echo $row->label;?>
        </h1>
	<?php endif;?>
    <br />
    <p>
		<?php echo $row->intro_text;?>
    </p>
	<?php echo $this->loadTemplate( 'filter'); ?>
    <br />
    <div id="chart-container-<?php echo $this->getModel()->getJSRenderContext();?>"></div>
</div>