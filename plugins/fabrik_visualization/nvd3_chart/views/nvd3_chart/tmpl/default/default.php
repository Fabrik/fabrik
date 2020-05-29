<?php
/**
 * Fabrik nvd3_chart Chart Default Tmpl
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.nvd3_chart
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$row = $this->row;
?>

<div id="<?php echo $this->containerId;?>" class="fabrik_visualization">
	<?php if ($this->params->get('show-title')) :?>
		<h1><?php echo $row->label;?></h1>
	<?php
	endif;
	?>
	<br/>
	<p><?php echo $row->intro_text;?></p>
	<?php echo $this->loadTemplate('filter'); ?>
	<br/>
	<svg style="height:<?php echo $this->params->get('height', 350); ?>px;width=<?php $this->params->get('width', 350)?>px"></svg>
	<?php echo $this->postText?>
</div>