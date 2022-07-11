<?php
/**
 * FabForm controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

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
	 * JApplication
	 *
	 * @var JApplicationCms
	 */
	protected $app;

	/**
	 * Option
	 *
	 * @var string
	 */
	protected $option = 'com_fabrik';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JControllerLegacy
	 * @since   12.2
	 * @throws  Exception
	 */
	public function __construct($config = array())
	{
		$this->app = ArrayHelper::getValue($config, 'app', JFactory::getApplication());
		parent::__construct($config);
	}
	/**
	 * Copy items
	 *
	 * @throws Exception
	 *
	 * @return  null
	 */
	public function copy()
	{
		$model = $this->getModel();
		$input = $this->input;
		$cid = $input->get('cid', array(), 'array');

		if (empty($cid))
		{
			throw new Exception(FText::_($this->text_prefix . '_NO_ITEM_SELECTED'));
		}
		else
		{
			if ($model->copy())
			{
				$nText = $this->text_prefix . '_N_ITEMS_COPIED';
				$this->setMessage(JText::plural($nText, count($cid)));
			}
		}

		$extension = $input->get('extension');
		$extensionURL = ($extension) ? '&extension=' . $extension : '';
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $extensionURL, false));
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key
	 * (sometimes required to avoid router collisions).
	 *
	 * @since   3.1
	 *
	 * @return  boolean  True if access level check and checkout passes, false otherwise.
	 */
	public function edit($key = null, $urlVar = null)
	{
		$this->option = 'com_fabrik';

		return parent::edit($key, $urlVar);
	}
}
