<?php
/**
 * Admin Element Edit:settings Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

echo JHtml::_('tabs.panel', FText::_('COM_FABRIK_LIST_VIEW_SETTINGS'), 'settings');

$panels = array(
	array('heading' => FText::_('COM_FABRIK_ELEMENT_LABEL_LIST_SETTINGS_DETAILS'),
		'id' => 'listsettings',
		'fieldset' => array('listsettings', 'listsettings2')),

	array('heading' => FText::_('COM_FABRIK_ELEMENT_LABEL_ICONS_SETTINGS_DETAILS'),
		'id' => 'icons',
		'fieldset' => array('icons')),

	array('heading' => FText::_('COM_FABRIK_ELEMENT_LABEL_FILTERS_DETAILS'),
		'id' => 'filters',
		'fieldset' => array('filters', 'filters2')),

	array('heading' => FText::_('COM_FABRIK_ELEMENT_LABEL_CSS_DETAILS'),
		'id' => 'viewcss',
		'fieldset' => 'viewcss'),

	array('heading' => FText::_('COM_FABRIK_ELEMENT_LABEL_CALCULATIONS_DETAILS'),
		'id' => 'calculations',
		'fieldset' => array('calculations-sum', 'calculations-avg', 'calculations-median', 'calculations-count', 'calculations-custom'))
);

echo JHtml::_('sliders.start', 'element-sliders-viewsettings-' . $this->item->id, array('useCookie' => 1));

foreach ($panels as $panel) :
	echo JHtml::_('sliders.panel', $panel['heading'], $panel['id'] . '-details');
			?>
			<fieldset class="adminform">
				<ul class="adminformlist">
					<?php
					$fieldsets = (array) $panel['fieldset'];
					foreach ($fieldsets as $fieldset) :
						foreach ($this->form->getFieldset($fieldset) as $field) :?>
						<li>
							<?php echo $field->label;
							echo $field->input; ?>
						</li>
						<?php endforeach;
					endforeach;?>
				</ul>
			</fieldset>
<?php
endforeach;
echo JHtml::_('sliders.end');
