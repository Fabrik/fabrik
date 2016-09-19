<?php
/**
 * Fabrik Raw Form View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');
require_once JPATH_SITE . '/components/com_fabrik/views/form/view.base.php';

/**
 * Fabrik Raw Form View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikViewForm extends FabrikViewFormBase
{
	/**
	 * Access value
	 *
	 * @var  int
	 */
	public $access = null;

	/**
	 * @var FabrikFEModelOai
	 */
	private $oaiModel;

	/**
	 * Constructor
	 *
	 * @param   array $config A named configuration array for object construction.
	 *
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->oaiModel = JModelLegacy::getInstance('Oai', 'FabrikFEModel');
	}

	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 */
	public function display($tpl = null)
	{
		$this->doc->setMimeEncoding('application/xml');
		$model = $this->getModel('form');
		$model->render();

		// @TODO replace with OAI errors.
		if (!$this->canAccess())
		{
			return false;
		}

		$listModel = $model->getListModel();
		$this->oaiModel->setListModel($listModel);
		$this->oaiModel->setRecord($model->getData());
		$dom = $this->oaiModel->getRecord();
		echo $dom->saveXML();
	}

}
