<?php
/**
 * Labels Above Articles Template - Group details
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<ul>
<?php foreach ( $this->elements as $element) {
	?>
	<li <?php echo @$element->column;?> class="<?php echo $element->containerClass;?>">
		<?php echo $element->label;?>
		<div class="fabrikElement">
			<?php echo $element->element;?>

		</div>
		<div class="fabrikErrorMessage">
				<?php echo $element->error;?>
			</div>
		<div style="clear:both"></div>
	</li>
	<?php }?>
</ul>
