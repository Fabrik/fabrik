<?php
/**
 * Fabrik List Template: Admin Headings
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<tr class="fabrik___heading">
<?php foreach ($this->headings as $key => $heading) {
	$style = empty($this->headingClass[$key]['style']) ? '' : 'style="'.$this->headingClass[$key]['style'].'"';?>
	<th class="<?php echo $this->headingClass[$key]['class']?>" <?php echo $style?>>
		<div class="heading"><?php echo  $heading; ?></div>
		<?php if (array_key_exists($key, $this->filters) && ($this->filterMode === 3 || $this->filterMode === 4)) {
			$filter = $this->filters[$key];
			$required = $filter->required == 1 ? ' notempty' : '';
			echo '<div class="listfilter' . $required . '">
			<span>'.$filter->element.'</span></div>';
		}?>
	</th>
	<?php }?>
</tr>