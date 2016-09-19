<?php
/**
 * Fabrik List Template: F3 Calcs
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<ul class="list">
	<li class="fabrik_calculations">
		<?php
		foreach ($this->calculations as $cal) {
			echo "<span>";
			echo $cal->calc;
			echo  "</span>";
		}
		?>
	</li>
</ul>