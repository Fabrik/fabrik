<?php
/**
 * Import view
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * View class for importing csv file.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       1.6
 */
class FabrikAdminViewImport extends JViewLegacy
{
	/**
	 * Display the view
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$this->form = $this->get('Form');
		$this->addToolBar();
		FabrikAdminHelper::setViewLayout($this);
		parent::display($tpl);
	}

	/**
	 * CSV file has been uploaded but we need to ask the user what to do with the new fields
	 *
	 * @return  void
	 */
	public function chooseElementTypes()
	{
		$app             = JFactory::getApplication();
		$this->drop_data = 0;
		$this->overwrite = 0;
		$input           = $app->input;
		$input->set('hidemainmenu', true);
		$this->chooseElementTypesToolBar();
		$session               = JFactory::getSession();
		$this->data            = $session->get('com_fabrik.csvdata');
		$this->matchedHeadings = $session->get('com_fabrik.matchedHeadings');
		$model                 = $this->getModel();
		$this->newHeadings     = $model->getNewHeadings();
		$this->headings        = $model->getHeadings();
		$pluginManager         = $this->getModel('pluginmanager');
		$this->table           = $model->getListModel()->getTable();
		$this->elementTypes    = $pluginManager->getElementTypeDd('field', 'plugin[]');
		$this->sample          = $model->getSample();
		$this->selectPKField   = $model->getSelectKey();
		$jform                 = $input->get('jform', array(), 'array');

		foreach ($jform as $key => $val)
		{
			$this->$key = $val;
		}

		parent::display('chooseElementTypes');
	}

	/**
	 * Add the 'choose element type' page toolbar
	 *
	 * @return  void
	 */
	protected function chooseElementTypesToolBar()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_LIST_IMPORT'), 'list');
		$version = new JVersion;
		$icon    = version_compare($version->RELEASE, '3.0') >= 0 ? 'arrow-right-2' : 'forward.png';
		JToolBarHelper::custom('import.makeTableFromCSV', $icon, $icon, 'COM_FABRIK_CONTINUE', false);
		JToolBarHelper::cancel('import.cancel', 'JTOOLBAR_CANCEL');
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since    1.6
	 *
	 * @return  void
	 */
	protected function addToolBar()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_LIST_IMPORT'), 'list');
		$version = new JVersion;
		$icon    = version_compare($version->RELEASE, '3.0') >= 0 ? 'arrow-right-2' : 'forward.png';
		JToolBarHelper::custom('import.doimport', $icon, $icon, 'COM_FABRIK_CONTINUE', false);
		JToolBarHelper::cancel('import.cancel', 'JTOOLBAR_CANCEL');
	}
}
