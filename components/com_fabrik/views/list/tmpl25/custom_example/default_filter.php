<?php
/**
 * Fabrik List Template: Custom Example Filter
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<!-- replace this in component code -->
<?php $use = array('downloads___acl','downloads___type', 'downloads___version');?>
<div class="fabrikFilterContainer">
	<div class="filtertable fabrikTable">
		<div class="fabrik_search_all">
		<?php if (array_key_exists('all', $this->filters)) {
			echo str_replace('fabrik_filter', 'fabrik_filter span12', $this->filters['all']->element);
		}?>
		</div>
		<?php
	foreach ($this->filters as $key => $filter) {
		if (in_array($key, $use)) {
			$class = $filter->required == 1 ? ' notempty' : '';
		?>
		<div class="<?php echo $class . ' filter_' . $key.'_label'?> ">
			<?php echo $filter->label;?>
		</div>
		<div data-filter-row="<?php echo $key;?>" class="<?php echo $class . ' filter_' . $key?>">
			<?php echo $filter->element;?>
		</div>
	<?php }
	} ?>
	</div>	<div class="fabrik_search form-actions">
	<a class="clearFilters btn" href="#"><i class="icon-remove"></i> Clear</a>
	<?php if ($this->filter_action != 'onchange') {?>
	<button class="pull-right fabrik_filter_submit button btn btn-info" name="filter">
	<i class="icon-filter icon-white"></i>
	<?php echo FText::_('GO');?></button>
	<?php }?>
	</div>
</div>