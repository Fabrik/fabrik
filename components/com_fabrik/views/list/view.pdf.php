<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0.5
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');
require_once COM_FABRIK_FRONTEND . '/views/list/view.base.php';

/**
 * PDF List view
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0.5
 */
class FabrikViewList extends FabrikViewListBase
{
	/**
	 * Display the template
	 *
	 * @param   sting  $tpl  Template
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		FabrikWorker::canPdf(true);

		if (parent::display($tpl) !== false)
		{
			$model = $this->getModel();
			$params = $model->getParams();
			$size        = $this->app->input->get('pdf_size', $params->get('pdf_size', 'A4'));
			$orientation = $this->app->input->get('pdf_orientation', $params->get('pdf_orientation', 'portrait'));
			$this->doc->setPaper($size, $orientation);
			$this->nav = '';
			$this->showPDF = false;
			$this->showRSS = false;
			$this->emptyLink = false;
			//$this->filters = array();
			$this->showFilters = false;
			$this->hasButtons = false;

			if ($this->app->input->get('pdf_include_bootstrap', $params->get('pdf_include_bootstrap', '0')) === '1')
			{
				FabrikhelperHTML::loadBootstrapCSS(true);
			}

			$this->output();
		}
	}

	/**
	 * Build an object with the button icons based on the current tmpl
	 *
	 * @return  void
	 */
	protected function buttons()
	{
		// Don't add buttons as pdf is not interactive
		$this->buttons = new stdClass;
	}

	/**
	 * Set page title
	 *
	 * @param   object  $w        Fabrikworker
	 * @param   object  &$params  list params
	 * @param   object  $model    list model
	 *
	 * @return  void
	 */
	protected function setTitle($w, &$params, $model)
	{
		parent::setTitle($w, $params, $model);

		// Set the download file name based on the document title
		$this->doc->setName($this->doc->getTitle());
	}
		/**
	 * Render the group by heading as a JLayout list.fabrik-group-by-heading

	 *
	 * @param   string  $groupedBy  Group by key for $this->grouptemplates
	 * @param   array   $group      Group data



	 *
	 * @return string
	 */
	public function layoutGroupHeading($groupedBy, $group)

	{
		$displayData = new stdClass;
		$displayData->emptyDataMessage = $this->emptyDataMessage;
		$displayData->tmpl = $this->tmpl;
		$displayData->title = $this->grouptemplates[$groupedBy];
		$displayData->count = count($group);
		$layout = $this->getModel()->getLayout('list.fabrik-group-by-heading');


		return $layout->render($displayData);


	}
}
