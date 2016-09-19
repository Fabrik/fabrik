<?php
/**
 * Bootstrap Details Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if (!empty($this->linkedTables)) {?>
	<ul class='linkedTables'>
		<?php foreach ($this->linkedTables as $a) { ?>
		<li>
			<?php echo implode(" ", $a);?>
			</li>
		<?php }?>
	</ul>
<?php }?>