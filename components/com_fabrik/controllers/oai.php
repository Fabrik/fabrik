<?php
/**
 * Fabrik Open Archive Initiative Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use \Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.controller');

/**
 * Fabrik Open Archive Initiative Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.4
 */
class FabrikControllerOai extends JControllerLegacy
{
	/**
	 * Display the view
	 *
	 * @param   boolean $cachable  If true, the view output will be cached - NOTE not actually used to control
	 *                             caching!!!
	 * @param   array   $urlparams An array of safe url parameters and their variable types, for valid values see
	 *                             {@link JFilterInput::clean()}.
	 *
	 * @throws Exception
	 * @return  JController|void  A JController object to support chaining.
	 */

	public function display($cachable = false, $urlparams = array())
	{
		$doc = JFactory::getDocument();
		$doc->setMimeEncoding('application/xml');
		$this->app   = JFactory::getApplication();
		$this->input = $this->app->input;
		$verb        = strtolower($this->input->get('verb'));

		/** @var FabrikFEModelOai $model */
		$this->model = $model = $this->getModel('oai', 'FabrikFEModel');

		switch ($verb)
		{
			case 'identify':
				// http://localhost:81/fabrik31x/public_html/index.php?option=com_fabrik&controller=oai&verb=Identify
				$view = $this->getView('identity', 'oai');
				$view->setModel($model, true);
				$view->display();
				break;
			case 'listsets':
				$this->listSets();
				break;
			case 'listmetadataformats':
				$this->listMetaDataFormats();
				break;
			case 'listrecords':
				// E.g. http://localhost:81/fabrik31x/public_html/index.php?option=com_fabrik&controller=oai&verb=ListRecords&set=setname&format=oai&limitstart17=0&from=2012-01-01&until=2015-01-01
				$this->listRecords();
				break;
			case 'getrecord':
				// http://localhost:81/fabrik31x/public_html/index.php?option=com_fabrik&controller=oai&verb=GetRecord&identifier
				$this->getRecord();
				break;
			case 'listidentifiers':
				$this->listRecords();
				break;


			default:
				echo $this->model->generateError(array('code' => 'badVerb',
						'msg' => 'Value of the verb argument is not a legal OAI-PMH verb, the
						verb argument is missing, or the verb argument is repeated.'));
				break;
		}

		return $this;
	}

	/**
	 * Get record
	 */
	private function getRecord()
	{
		$identifier = $this->input->getString('identifier', '');
		$metaDataPrefix = $this->input->getString('metadataPrefix', '');

		if ($identifier === '' || !$this->model->checkIdentifier($identifier))
		{
			echo $this->model->generateError(array('code' => 'idDoesNotExist',
					'msg' => 'Get Record: No matching identifier'));

			return;
		}

		if (!$this->model->supportMetaDataPrefix($metaDataPrefix))
		{
			echo $this->model->generateError(array('code' => 'cannotDisseminateFormat',
					'msg' => 'Cant use the prefix: ' . $metaDataPrefix));

			return;
		}

		$url = 'index.php?option=com_fabrik&view=details&format=oai';
		list($listId, $rowId) = $this->model->getListRowIdFromIdentifier($identifier);

		/** @var FabrikFEModelList $listModel */
		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$listModel->setId($listId);
		$formId = $listModel->getFormModel()->getId();
		$url .= '&formid=' . $formId . '&rowid=' . $rowId;
		$this->app->redirect($url);
	}

	/**
	 * List records
	 * http://localhost:81/fabrik31x/public_html/index.php?option=com_fabrik&controller=oai&format=oai&verb=ListRecords&set=testdata
	 */
	private function listRecords()
	{
		$url    = 'index.php?option=com_fabrik&view=list&format=oai';
		$set = $this->input->get('set');
		$listId = (int) $this->model->listIdFromSetName($set);

		if ($listId === 0)
		{
			echo $this->model->generateError(array('code' => 'badArgument', 'msg' => 'ListRecords - no set found'));

			return;
		}

		$url .= '&listid=' . $listId;

		$resumptionToken = urldecode($this->input->getString('resumptionToken'));
		parse_str($resumptionToken, $token);
		$from  = ArrayHelper::getValue($token, 'from', $this->input->get('from', ''));
		$until = ArrayHelper::getValue($token, 'until', $this->input->get('until', ''));
		$start = ArrayHelper::getValue($token, 'limitstart' . $listId, $this->input->get('limitstart' . $listId));

		if ($from !== '')
		{
			$url .= '&from=' . $from;
		}

		if ($until !== '')
		{
			$url .= '&until=' . $until;
		}

		if ($start !== '')
		{
			$url .= '&limitstart' . $listId . '=' . $start;
		}

		$this->app->redirect($url);
	}

	/**
	 * List sets
	 */
	protected function listSets()
	{
		$listSet = $this->model->listSets();
		echo $listSet->saveXML();
	}

	/**
	 * List Meta data formats.
	 */
	protected function listMetaDataFormats()
	{
		$identifier = $this->input->getString('identifier');
		$xml = $this->model->listMetaDataFormats($identifier);
		echo $xml->saveXML();
	}

}
