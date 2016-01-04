<?php
/**
 * Fabrik Admin Plugin Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik Admin Plugin Model
 * Used for loading via ajax form plugins
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0.6
 */
class FabrikAdminModelPlugin extends JModelLegacy
{
	/**
	 * Render the plugins fields
	 *
	 * @return string
	 */
	public function render()
	{
		$app                       = JFactory::getApplication();
		$input                     = $app->input;

		/** @var FabrikFEModelPluginmanager $pluginManager */
		$pluginManager             = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		$plugin                    = $pluginManager->getPlugIn($this->getState('plugin'), $this->getState('type'));
		$feModel                   = $this->getPluginModel();
		$plugin->getJForm()->model = $feModel;

		$data = $this->getData();
		$input->set('view', $this->getState('type'));

		$mode = FabrikWorker::j3() ? 'nav-tabs' : '';
		$str  = $plugin->onRenderAdminSettings($data, $this->getState('c'), $mode);
		$input->set('view', 'plugin');

		return $str;
	}

	/**
	 * Get the plugins data to bind to the form
	 *
	 * @return  array
	 */
	protected function getData()
	{
		$type = $this->getState('type');
		$data = array();

		if ($type === 'validationrule')
		{
			$item = FabTable::getInstance('Element', 'FabrikTable');
			$item->load($this->getState('id'));
		}
		elseif ($type === 'elementjavascript')
		{
			$item = FabTable::getInstance('Jsaction', 'FabrikTable');
			$item->load($this->getState('id'));
			$data = $item->getProperties();
		}
		else
		{
			$feModel = $this->getPluginModel();
			$item    = $feModel->getTable();
		}

		$data                      = $data + (array) json_decode($item->params);
		$data['plugin']            = $this->getState('plugin');
		$data['params']            = (array) FArrayHelper::getValue($data, 'params', array());
		$data['params']['plugins'] = $this->getState('plugin');

		$data['validationrule']['plugin']           = $this->getState('plugin');
		$data['validationrule']['plugin_published'] = $this->getState('plugin_published');
		$data['validationrule']['show_icon']        = $this->getState('show_icon');
		$data['validationrule']['validate_in']      = $this->getState('validate_in');
		$data['validationrule']['validation_on']    = $this->getState('validation_on');

		$c = $this->getState('c') + 1;

		// Add plugin published state, locations, descriptions and events
		$state        = (array) FArrayHelper::getValue($data, 'plugin_state');
		$locations    = (array) FArrayHelper::getValue($data, 'plugin_locations');
		$events       = (array) FArrayHelper::getValue($data, 'plugin_events');
		$descriptions = (array) FArrayHelper::getValue($data, 'plugin_description');

		$data['params']['plugin_state'] = FArrayHelper::getValue($state, $c, 1);
		$data['plugin_locations']       = FArrayHelper::getValue($locations, $c);
		$data['plugin_events']          = FArrayHelper::getValue($events, $c);
		$data['plugin_description']     = FArrayHelper::getValue($descriptions, $c);

		return $data;
	}

	/**
	 * Get the plugin model
	 *
	 * @return  object
	 */
	protected function getPluginModel()
	{
		$feModel = null;
		$type    = $this->getState('type');

		if ($type === 'elementjavascript')
		{
			return null;
		}

		if ($type !== 'validationrule')
		{
			// Set the parent model e.g. form/list
			$feModel = JModelLegacy::getInstance($type, 'FabrikFEModel');
			$feModel->setId($this->getState('id'));
		}

		return $feModel;
	}

	/**
	 * Render the initial plugin options, such as the plugin selector, and whether its rendered in front/back/both etc
	 *
	 * @return  string
	 */
	public function top()
	{
		$data                   = $this->getData();
		$c                      = $this->getState('c') + 1;
		$version                = new JVersion;
		$j3                     = version_compare($version->RELEASE, '3.0') >= 0 ? true : false;
		$class                  = $j3 ? 'form-horizontal ' : 'adminform ';
		$str                    = array();
		$str[]                  = '<div class="pane-slider content pane-down accordion-inner">';
		$str[]                  = '<fieldset class="' . $class . 'pluginContainer" id="formAction_' . $c . '"><ul>';
		$formName               = 'com_fabrik.' . $this->getState('type') . '-plugin';
		$topForm                = new JForm($formName, array('control' => 'jform'));
		$topForm->repeatCounter = $c;
		$xmlFile                = JPATH_SITE . '/administrator/components/com_fabrik/models/forms/' . $this->getState('type') . '-plugin.xml';

		// Add the plugin specific fields to the form.
		$topForm->loadFile($xmlFile, false);

		$topForm->bind($data);

		// Filter the forms fieldsets for those starting with the correct $searchName prefix
		foreach ($topForm->getFieldsets() as $fieldset)
		{
			if ($fieldset->label != '')
			{
				$str[] = '<legend>' . $fieldset->label . '</legend>';
			}

			foreach ($topForm->getFieldset($fieldset->name) as $field)
			{
				if (!$j3)
				{
					$str[] = '<li>' . $field->label . $field->input . '</li>';
				}
				else
				{
					$str[] = '<div class="control-group"><div class="control-label">' . $field->label;
					$str[] = '</div><div class="controls">' . $field->input . '</div></div>';
				}
			}
		}

		$str[] = '</ul>';
		$str[] = '<div class="pluginOpts" style="clear:left"></div>';

		if ($j3)
		{
			$str[] = '<div class="form-actions"><a href="#" class="btn btn-danger" data-button="removeButton">';
			$str[] = '<i class="icon-delete"></i> ' . FText::_('COM_FABRIK_DELETE') . '</a></div>';
		}
		else
		{
			$str[] = '<a href="#" class="delete removeButton">' . FText::_('COM_FABRIK_DELETE') . '</a>';
		}

		$str[] = '</fieldset>';
		$str[] = '</div>';

		return implode("\n", $str);
	}
}
