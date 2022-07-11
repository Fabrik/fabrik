<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

/**
 * Helper for mod_fabrik_list
 *
 * @package     Joomla.Administrator
 * @subpackage  mod_fabrik_list
 * @since       3.1.1
 */
class ModFabrikListHelper
{
	/**
	 * Assign module settings to the list model
	 *
	 * @param   JRegistry     $params  Module parameters
	 * @param   JModelLegacy  &$model  List model
	 *
	 * @return  $model
	 */
	public static function applyParams($params, &$model)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$useajax = $params->get('useajax', '');
		$showTitle = $params->get('show-title', '');
		$limit = (int) $params->get('limit', 0);
		$listParams = $model->getParams();
		$listParams->set('show-table-picker', false);
		$random = intval($params->get('radomizerecords', 0));

		if ($limit !== 0)
		{
			$app->setUserState('com_fabrik.list' . $model->getRenderContext() . '.limitlength', $limit);
			$input->set('limit', $limit);
		}

		if ($useajax !== '')
		{
			$model->set('ajax', $useajax);
		}

		if ($params->get('ajax_links') !== '')
		{
			$listParams->set('list_ajax_links', $params->get('ajax_links'));
		}

		$links = array('addurl', 'editurl', 'detailurl');

		foreach ($links as $link)
		{
			if ($params->get($link, '') !== '')
			{
				$listParams->set($link, $params->get($link));
			}
		}

		if ($showTitle !== '')
		{
			$listParams->set('show-title', $showTitle);
		}

		$model->randomRecords = $random;

		// Set up prefilters - will overwrite ones defined in the list!
		$prefilters = JArrayHelper::fromObject(json_decode($params->get('prefilters')));
		$conditions = (array) $prefilters['filter-conditions'];

		if (!empty($conditions))
		{
			$joins = FArrayHelper::getValue($prefilters, 'filter-join', array());
			$listParams->set('filter-join', $joins);
			$listParams->set('filter-fields', $prefilters['filter-fields']);
			$listParams->set('filter-conditions', $prefilters['filter-conditions']);
			$listParams->set('filter-value', $prefilters['filter-value']);
			$listParams->set('filter-access', $prefilters['filter-access']);
			$listParams->set('filter-eval', FArrayHelper::getValue($prefilters, 'filter-eval'));
		}

		return $model;
	}
}
