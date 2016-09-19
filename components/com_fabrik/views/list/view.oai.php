<?php
/**
 * Open Archive Initiative List Records View
 * http://www.openarchives.org/OAI/2.0/openarchivesprotocol.htm#ListRecords
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/views/list/view.base.php';

/**
 * Open Archive Initiative List Records View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.4
 */
class FabrikViewList extends FabrikViewListBase
{
	/**
	 * Row identifier
	 *
	 * @var string
	 */
	private $rowIdentifier = '';

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
	 * Display the Feed
	 *
	 * @param   sting $tpl template
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
		$this->doc->setMimeEncoding('application/xml');
		$model = $this->getModel();
		$this->oaiModel->setListModel($model);
		$root = $this->oaiModel->root();
		$root->appendChild($this->oaiModel->responseDate());
		$root->appendChild($this->request());

		$this->params = $model->getParams();
		$model->setOutPutFormat('feed');
		$this->app->allowCache(true);

		if (!parent::access($model))
		{
			exit;
		}

		$filter = $this->filter();

		foreach ($filter as $key => $val)
		{
			$_GET[$key] = $val;
		}

		$model->render();
		$rows  = $model->getData();
		$total = $model->getTotalRecords();

		if ($total === 0)
		{
			echo $this->model->generateError(array('code' => 'noRecordsMatch', 'msg' => 'No records matched'));
		}

		$this->rowIdentifier = 'oai:' . $this->oaiModel->repositoryIdentifier() . ':' . $model->getId() . '/';
		$listRecords         = $this->listRecords($rows);

		if ($total > count($rows))
		{
			$listRecords->appendChild($this->oaiModel->resumptionToken($total, $filter));
		}

		$root->appendChild($listRecords);
		$this->oaiModel->appendChild($root);
		print_r($this->oaiModel->dom->saveXML());
	}

	/**
	 * Work out the filters to apply to the list - basically the from and until querystring vars
	 *
	 * @return array
	 */
	private function filter()
	{
		$this->app->input->set('clearfilters', 1);
		$this->app->input->set('fabrik_incsessionfilters', false);
		// Lets support only the Y-m-d OAI format for now (so no time allowed)
		$dateEl = $this->oaiModel->dateElName();
		$from   = DateTime::createFromFormat('Y-m-d', $this->app->input->get('from', ''));
		if ($from !== false)
		{
			$from = $from->setTime(0, 0, 0)->format('Y-m-d H:i:s');
		}

		$until = DateTime::createFromFormat('Y-m-d', $this->app->input->get('until', ''));
		if ($until !== false)
		{
			$until = $until->setTime(0, 0, 0)->format('Y-m-d H:i:s');
		}

		if ($from === false & $until !== false)
		{
			$from = '1970-01-01 00:00:00';
		}

		if ($from !== false & $until === false)
		{
			$until = new DateTime();
			$until = $until->format('Y-m-d H:i:s');
		}

		return array($dateEl => array('condition' => 'BETWEEN',
			'value' => array($from, $until)
		));
	}

	/**
	 * List Records
	 *
	 * @param $rows
	 *
	 * @return DOMElement
	 */
	private function listRecords($rows)
	{
		$listRecords = $this->oaiModel->createElement('ListRecords');

		foreach ($rows as $group)
		{
			foreach ($group as $row)
			{
				$record   = $this->oaiModel->createElement('record');
				$metaData = $this->oaiModel->createElement('metadata');
				$oaiDc    = $this->oaiModel->rowOaiDc($row);
				$header   = $this->rowHeader($row);
				$about    = $this->rowAbout($row);
				$this->oaiModel->dcRow($oaiDc, $row);
				$metaData->appendChild($oaiDc);
				$record->appendChild($header);
				$record->appendChild($metaData);
				$record->appendChild($about);
				$listRecords->appendChild($record);

			}
		}

		return $listRecords;
	}

	/**
	 * Build a rows <header> node
	 *
	 * @param object $row
	 *
	 * @return DOMNode
	 */
	private function rowHeader($row)
	{
		$header       = $this->oaiModel->createElement('header');
		$dateStampKey = $this->oaiModel->dateElName();
		$dateStamp    = JFactory::getDate($row->$dateStampKey)->format('Y-m-d');
		$header->appendChild($this->oaiModel->createElement('identifier', $this->rowIdentifier . $row->__pk_val));
		$header->appendChild($this->oaiModel->createElement('datestamp', $dateStamp));

		return $header;
	}

	/**
	 * Build a row's <about> node
	 *
	 * @param object $row
	 *
	 * @return DOMNode
	 */
	private function rowAbout($row)
	{
		$about = $this->oaiModel->createElement('about');
		//$provenance = $this->rowProvenance($row);
		$rights = $this->rowRights($row);
		$about->appendChild($rights);

		return $about;
	}

	/**
	 * @return DOMElement
	 */
	private function request()
	{
		$model      = $this->getModel();
		$input      = $this->app->input;
		$request    = $this->oaiModel->requestElement();
		$listParams = $model->getParams();
		$attributes = array(
			'verb' => 'ListRecords',
			'set' => $listParams->get('open_archive_set_spec'),
			'metadataPrefix' => $input->get('metadataPrefix', 'oai_dc')

		);

		if ($input->get('from', '') !== '')
		{
			$attributes['from'] = $input->get('from');
		}
		if ($input->get('until', '') !== '')
		{
			$attributes['until'] = $input->get('until');
		}
		$this->oaiModel->nodeAttributes($request, $attributes);

		return $request;

	}

	/**
	 * Not implemented yet - but would describe the provenance of the original data
	 *
	 * @param $row
	 */
	private function rowProvenance($row)
	{
	}

	/**
	 * Describes a records copy right info
	 *
	 * @param $row
	 *
	 * @return DOMElement
	 */
	private function rowRights($row)
	{
		$rights     = $this->oaiModel->createElement('rights');
		$attributes = array(
			"xmlns" => "http://www.openarchives.org/OAI/2.0/rights/",
			"xmlns:xsi" => "http://www.w3.org/2001/XMLSchema-instance",
			"xsi:schemaLocation" => "http://www.openarchives.org/OAI/2.0/rights/
                                   http://www.openarchives.org/OAI/2.0/rights.xsd"
		);
		$this->oaiModel->nodeAttributes($rights, $attributes);

		$rightsReference = $this->oaiModel->createElement('rightsReference');
		$this->oaiModel->nodeAttributes($rightsReference, array('ref' => $this->params->get('open_archive_license')));
		$rights->appendChild($rightsReference);

		return $rights;
	}

}
