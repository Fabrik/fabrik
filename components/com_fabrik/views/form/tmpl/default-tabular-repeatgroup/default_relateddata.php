<?php
/**
 * Default Form:Related data Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       3.0
 */

?>
<?php if (!empty($this->linkedTables)) : ?>
	<ul class='linkedTables'>
		<?php foreach ($this->linkedTables as $a) : ?>
		<li>
			<?php echo implode(" ", $a);?>
		</li>
		<?php endforeach;?>
	</ul>
<?php endif; ?>