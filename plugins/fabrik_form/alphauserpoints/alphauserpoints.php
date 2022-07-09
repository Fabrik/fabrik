<?php
/**
 * Alpha User Points
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.alphauserpoints
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Insert points into the Alpha User Points http://www.alphaplug.com component
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.alphauserpoints
 * @since       3.0.7
 */

class PlgFabrik_FormAlphaUserPoints extends PlgFabrik_Form
{
	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @throws Exception
	 *
	 * @return	bool
	 */

	public function onAfterProcess()
	{
		$params = $this->getParams();
		$api_AUP = JPATH_SITE . '/components/com_altauserpoints/helper.php';

		if (JFile::exists($api_AUP))
		{
			$w = new FabrikWorker;
			$this->data = $this->getProcessData();

			if (!$this->shouldProcess('aup_conditon', null, $params))
			{
				return true;
			}

			require_once $api_AUP;
			$aup = new AltaUserPointsHelper;

			// Define which user will receive the points.
			$userId = $params->get('user_id', '');
			$userId = (int) $w->parseMessageForPlaceholder($userId, $this->data, false);

			$aupId = $aup->getAnyUserReferreID($userId);

			// Replace these if you want to show a specific reference for the attributed points - doesn't seem to effect anything
			$keyReference = '';

			// Shown in the user details page - description of what the point is for
			$dataReference = $params->get('data_reference', '');
			$dataReference = $w->parseMessageForPlaceholder($dataReference, $this->data, false);

			// Override the plugin default points
			$randomPoints = $params->get('random_points', 0);

			if ($params->get('random_points_eval', '0') == '1')
			{
				if (!empty($randomPoints))
				{
					$randomPoints = $w->parseMessageForPlaceholder($randomPoints, $this->data, false);
					$randomPoints = @eval($randomPoints);
					FabrikWorker::logEval($randomPoints, 'Caught exception on eval in aup plugin : %s');
				}

				$randomPoints = (float) $randomPoints;
			}
			else
			{
				$randomPoints = (float) $w->parseMessageForPlaceholder($randomPoints, $this->data, false);
			}

			// If set to be greater than $randompoints then this is the # of points assigned (not sure when this would be used - commenting out for now)
			$referralUserPoints = 0;

			$aupPlugin = $params->get('aup_plugin', 'plgaup_fabrik');
			$aupPlugin = $w->parseMessageForPlaceholder($aupPlugin, $this->data, false);

			if (!$aup->checkRuleEnabled($aupPlugin, 0, $aupId))
			{
				throw new Exception('Alpha User Points plugin not published');
			}

			$aup->userpoints($aupPlugin, $aupId, $referralUserPoints, $keyReference, $dataReference, $randomPoints);
		}
	}
}
