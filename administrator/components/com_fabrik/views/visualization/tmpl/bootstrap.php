<?php
/**
 * Admin Visualization Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.8
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHTML::stylesheet('administrator/components/com_fabrik/views/fabrikadmin.css');
JHtml::_('behavior.tooltip');
FabrikHelperHTML::formvalidation();
JHtml::_('behavior.keepalive');
?>

<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

	<div class="row-fluid" id="visualizationFormTable">

		<div class="span2">

			<ul class="nav nav-list">
				<li class="active">
			    	<a data-toggle="tab" href="#tab-details">
			    		<?php echo FText::_('COM_FABRIK_DETAILS')?>
			    	</a>
			    </li>
			    <li>
			    	<a data-toggle="tab" href="#tab-publishing">
			    		<?php echo FText::_('COM_FABRIK_PUBLISHING')?>
			    	</a>
			    </li>
			    <li>
			    	<a data-toggle="tab" href="#tab-options">
			    		<?php echo FText::_('COM_FABRIK_OPTIONS')?>
			    	</a>
			    </li>
			</ul>
		</div>

		<div class="span10 tab-content">
			<?php
			echo $this->loadTemplate('details');
			echo $this->loadTemplate('publishing');
			echo $this->loadTemplate('options');
			?>
		</div>
	</div>

	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
