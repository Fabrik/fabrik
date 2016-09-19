<?php
/**
 * Fabrik List Template: F3 Header
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="fabrikHeader">
	<?php
	echo $this->loadTemplate('buttons');
	if ($this->showFilters) {
		echo $this->loadTemplate('filter');
	}?>
</div>