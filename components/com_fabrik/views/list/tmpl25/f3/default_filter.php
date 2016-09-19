<?php
/**
 * Fabrik List Template: F3 Filters
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

if ($this->filter_action != 'onchange') {?>
	<div class="submitfilter">
	<a href="#" name="filter" class="fabrik_filter_submit"></a>
			</div>
	<?php }?>

<?php
	echo "<div class=\"searchall\">";
	//echo $this->filters['all']->label;
	echo $this->clearFliterLink . ' |' ;
	if (array_key_exists('all', $this->filters)) {
		echo $this->filters['all']->element;
	}
	echo "</div>";
?>
<?php if ($this->filter_action == '') {?>
	<input type="button" class="fabrik_filter_submit button" value="<?php //echo FText::_('GO');?>"
			name="filter" />
<?php }?>
