<?php
/**
 * HTML Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/views/list/view.base.php';

/**
 * HTML Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikViewList extends FabrikViewListBase
{
	/**
	 * Tabbed content
	 *
	 * @var array
	 */
	public $tabs = array();

	/**
	 * Display the template
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
		if (parent::display($tpl) !== false)
		{
			/** @var FabrikFEModelList $model */
			$model = $this->getModel();
			$this->setCanonicalLink();
			$this->tabs = $model->loadTabs();

			if (!$this->app->isClient('administrator') && isset($this->params))
			{
				/** @var JObject $state */
				$state = $model->getState();
				$stateParams = $state->get('params');

				if ($stateParams->get('menu-meta_description'))
				{
					$this->doc->setDescription($stateParams->get('menu-meta_description'));
				}

				if ($stateParams->get('menu-meta_keywords'))
				{
					$this->doc->setMetadata('keywords', $stateParams->get('menu-meta_keywords'));
				}

				if ($stateParams->get('robots'))
				{
					$this->doc->setMetadata('robots', $stateParams->get('robots'));
				}
			}

			$this->output();
		}
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
		$displayData->extra = $this->grouptemplatesExtra[$groupedBy];
		$displayData->count = count($group);
		$displayData->group_by_show_count = $this->params->get('group_by_show_count','1');
		$layout = $this->getModel()->getLayout('list.fabrik-group-by-heading');

		return $layout->render($displayData);
	}

	/**
	 * Create and render layout of the list's filters
	 *
	 * @return string
	 */
	public function layoutFilters()
	{
		$displayData = new stdClass;
		$displayData->filterMode = $this->filterMode;
		$displayData->toggleFilters = $this->toggleFilters;
		$displayData->filterCols = (int)$this->filterCols;
		$displayData->showClearFilters = $this->showClearFilters;
		$displayData->gotOptionalFilters = $this->gotOptionalFilters;
		$displayData->filters = $this->filters;
		$displayData->filter_action = $this->filter_action;
        $displayData->buttons = $this->buttons;

		if ($this->filterMode === 5)
		{
			$layoutFile = 'fabrik-filters-modal';
		}
		else
		{

			$layoutFile = $this->filterCols > 1 ? 'fabrik-filters-bootstrap' : 'fabrik-filters';
		}

		$layout = $this->getModel()->getLayout('list.' . $layoutFile);

		return $layout->render($displayData);
	}

	/**
	 * Set the canonical link - this is the definitive URL that Google et all, will use
	 * to determine if duplicate URLs are the same content
	 *
	 * @throws Exception
	 */
	public function setCanonicalLink()
	{
		if (!$this->app->isClient('administrator') && !$this->isMambot)
		{
			$url = $this->getCanonicalLink();

			// Set a flag so that the system plugin can clear out any other canonical links.
			$this->session->set('fabrik.clearCanonical', true);
			$this->doc->addCustomTag('<link rel="canonical" href="' . htmlspecialchars($url) . '" />');
		}
	}
}
