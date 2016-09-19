<?php
/**
 * Admin Form Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>

<div class="control-group">
<?php if (!$this->field->hidden) :?>
	<div class="control-label">
		<?php echo $this->field->label; ?>
	</div>
<?php endif;
$style = $this->field->id == 'jform_intro_text' ? 'style="width:75%"': '' ?>
	<div class="controls" <?php echo $style?>>
		<?php echo $this->field->input; ?>
	</div>
</div>