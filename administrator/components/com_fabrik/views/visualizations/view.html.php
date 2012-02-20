<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View class for a list of visualizations.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_fabrik
 * @since		1.6
 */
class FabrikViewVisualizations extends JView
{
	protected $items;
	protected $pagination;
	protected $state;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		// Initialise variables.
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->state		= $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT.'/helpers/fabrik.php';
		$canDo	= FabrikHelper::getActions($this->state->get('filter.category_id'));

		JToolBarHelper::title(JText::_('COM_FABRIK_MANAGER_VISUALIZATIONS'), 'visualizations.png');
		if ($canDo->get('core.create')) {
			JToolBarHelper::addNew('visualization.add','JTOOLBAR_NEW');
		}
		if ($canDo->get('core.edit')) {
			JToolBarHelper::editList('visualization.edit','JTOOLBAR_EDIT');
		}
		if ($canDo->get('core.edit.state')) {
			if ($this->state->get('filter.state') != 2){
				JToolBarHelper::divider();
				JToolBarHelper::custom('visualizations.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('visualizations.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
		}
		if(JFactory::getUser()->authorise('core.manage','com_checkin')) {
			JToolBarHelper::custom('visualizations.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
		}
		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
			JToolBarHelper::deleteList('', 'visualizations.delete','JTOOLBAR_EMPTY_TRASH');
		} else if ($canDo->get('core.edit.state')) {
			JToolBarHelper::trash('visualizations.trash','JTOOLBAR_TRASH');
		}
		if ($canDo->get('core.admin')) {
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_fabrik');
		}
		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_VISUALIZATIONS', false, JText::_('JHELP_COMPONENTS_FABRIK_VISUALIZATIONS'));
	}
}
