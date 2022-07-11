<?php
/**
 * Fabrik Coverflow Viz - bootstrap tmpl
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.coverflow
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$row = $this->row;
?>

<div id="<?php echo $this->containerId; ?>" class="fabrik_visualization">
	<?php if ($this->params->get('show-title', 1)) : ?>
		<h1><?php echo $row->label; ?></h1>
	<?php endif;
	?>
	<div>
		<?php echo $row->intro_text; ?>
	</div>
	<?php echo $this->loadTemplate('filter'); ?>
	<div id="coverflow" style="height: 400px;"></div>
</div>
