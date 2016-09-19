<?php
/**
 * Fabrik List Template: F3 Headings
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$filter = JFilterInput::getInstance(array('p'), array(), 1);?>
<div class="fabrik___headings">
<ul class="fabrik___heading list">
	<li class="heading">
		<?php foreach ($this->headings as $key=>$heading) {?>
		<div class="<?php echo $this->headingClass[$key]['class']?> fabrik_element"
		style="<?php echo $this->headingClass[$key]['style']?>">
			<?php echo $filter->clean($heading, 'HTML'); ?>
		</div>
		<?php }?>
	</li>
</ul>

<?php if ($this->showFilters) {?>
<ul class="fabrik___heading list filters">
	<li class="heading">
	<?php
	$this->found_filters = array();
	foreach ($this->headings as $key=>$heading) {?>
		<div class="<?php echo $this->headingClass[$key]['class']?> fabrik_element">
		<?php $filter = FArrayHelper::getValue($this->filters, $key, null);
		if(!is_null($filter)) {
			$this->found_filters[] = $key;
			echo $filter->element;
		} ?></div>
		<?php }?>
	</li>
</ul>
<?php } ?>
</div>
