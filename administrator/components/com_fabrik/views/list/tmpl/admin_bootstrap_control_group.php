<?php
/**
 * Admin list Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$dataShowOn = '';
if ($this->field->showon)
{
	JHtml::_('jquery.framework');
	JHtml::_('script', 'jui/cms.js', array('version' => 'auto', 'relative' => true));
                 
	$showOns = JFormHelper::parseShowOnConditions($this->field->showon, $this->field->formControl, $this->field->group);
	$dataShowOn = ' data-showon=\'' . json_encode($showOns) . '\'';
}
?>
<div class="control-group"<?php echo $dataShowOn; ?>>
<?php if (!$this->field->hidden) :?>
	<div class="control-label">
		<?php echo $this->field->label; ?>
	</div>
<?php endif; ?>
	<div class="controls">
		<?php echo $this->field->input; ?>
	</div>
</div>
