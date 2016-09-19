<?php
/**
 * Fabrik List Template: AdminModule Filter
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<div class="fabrikFilterContainer">
<?php echo $this->clearFliterLink;?>
<?php if ($this->filter_action != 'onchange') {?>
<input type="button" class="fabrik_filter_submit button" value="<?php echo FText::_('COM_FABRIK_GO');?>"
			name="filter" />
			<?php }?>
</div>
