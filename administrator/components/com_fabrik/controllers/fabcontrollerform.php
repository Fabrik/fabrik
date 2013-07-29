<?php
/**
 * FabForm controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * FabForm controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabControllerForm extends JControllerForm
{

	/**
	 * Option
	 *
	 * @var string
	 */
	protected $option = 'com_fabrik';

	/**
	 * Copy items
	 *
	 * @return  null
	 */

	public function copy()
	{
		$model = $this->getModel();
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(), 'array');
		if (empty($cid))
		{
			JError::raiseWarning(500, JText::_($this->text_prefix . '_NO_ITEM_SELECTED'));
		}
		else
		{
			if ($model->copy())
			{
				$ntext = $this->text_prefix . '_N_ITEMS_COPIED';
				$this->setMessage(JText::plural($ntext, count($cid)));
			}
		}
		$extension = $input->get('extension');
		$extensionURL = ($extension) ? '&extension=' . $extension : '';
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $extensionURL, false));
	}
}
